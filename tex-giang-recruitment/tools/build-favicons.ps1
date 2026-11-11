<#
  Sinh bộ favicon từ logo gốc 512x512 (public/assets/images/logo-tgi.png).
  Dùng: pwsh -File tools/build-favicons.ps1
  Kết quả:
    public/favicon.ico                                   (16 + 32 + 48, PNG-in-ICO)
    public/assets/images/favicons/favicon-16x16.png
    public/assets/images/favicons/favicon-32x32.png
    public/assets/images/favicons/favicon-48x48.png
    public/assets/images/favicons/apple-touch-icon.png   (180, nền trắng đục)
    public/assets/images/favicons/icon-192.png
    public/assets/images/favicons/icon-512.png

  Vì sao không dùng thẳng logo-tgi.png: file 220 KB, chữ "TEX-GIANG" ở 16px thành
  vệt mờ. Script crop lấy khối "TGI" rồi pad vuông nên tab 16px vẫn đọc được.
#>
[CmdletBinding()]
param(
    [string] $Source,
    [string] $Root
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

if (-not $Root) { $Root = Split-Path -Parent $PSScriptRoot }
if (-not $Source) { $Source = Join-Path $Root 'public/assets/images/logo-tgi.png' }
if (-not (Test-Path $Source)) { throw "Khong tim thay logo goc: $Source" }

$outDir = Join-Path $Root 'public/assets/images/favicons'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$icoPath = Join-Path $Root 'public/favicon.ico'

# ---------------------------------------------------------------------------
# 1. Nạp logo vào bitmap 32bpp để đọc điểm ảnh
# ---------------------------------------------------------------------------
$img = [System.Drawing.Image]::FromFile($Source)
$srcBmp = New-Object System.Drawing.Bitmap $img.Width, $img.Height, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$g = [System.Drawing.Graphics]::FromImage($srcBmp)
$g.DrawImage($img, 0, 0, $img.Width, $img.Height)
$g.Dispose()
$img.Dispose()

$W = $srcBmp.Width
$H = $srcBmp.Height

$rect = New-Object System.Drawing.Rectangle 0, 0, $W, $H
$bits = $srcBmp.LockBits($rect, [System.Drawing.Imaging.ImageLockMode]::ReadOnly, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
$stride = $bits.Stride
$pixels = New-Object byte[] ($stride * $H)
[System.Runtime.InteropServices.Marshal]::Copy($bits.Scan0, $pixels, 0, $pixels.Length)
$srcBmp.UnlockBits($bits)

# Nền logo gốc là trắng ngà (#F5F5F5) chứ không phải trắng tuyệt đối: ở 16-32px
# nó thành mảng xám bẩn trên tab trình duyệt. Đẩy mọi điểm ảnh sáng về trắng
# đục, giữ nguyên chữ navy và cạnh khử răng cưa.
for ($i = 0; $i -lt $pixels.Length; $i += 4) {
    $lum = ($pixels[$i + 2] * 299 + $pixels[$i + 1] * 587 + $pixels[$i] * 114) / 1000
    if ($lum -ge 236) {
        $pixels[$i] = 255; $pixels[$i + 1] = 255; $pixels[$i + 2] = 255; $pixels[$i + 3] = 255
    } elseif ($pixels[$i + 3] -lt 40) {
        $pixels[$i] = 255; $pixels[$i + 1] = 255; $pixels[$i + 2] = 255; $pixels[$i + 3] = 255
    }
}

$bitsW = $srcBmp.LockBits($rect, [System.Drawing.Imaging.ImageLockMode]::WriteOnly, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
[System.Runtime.InteropServices.Marshal]::Copy($pixels, 0, $bitsW.Scan0, $pixels.Length)
$srcBmp.UnlockBits($bitsW)

# "Mực" = điểm ảnh tối (chữ navy). Nền logo là trắng đục.
function Test-Ink([int] $x, [int] $y) {
    $i = $y * $stride + $x * 4
    $b = $pixels[$i]; $gg = $pixels[$i + 1]; $r = $pixels[$i + 2]; $a = $pixels[$i + 3]
    if ($a -lt 40) { return $false }
    return (($r * 299 + $gg * 587 + $b * 114) / 1000) -lt 170
}

# ---------------------------------------------------------------------------
# 2. Tìm các dải hàng có mực -> dải đầu là khối "TGI", dải sau là "TEX-GIANG"
# ---------------------------------------------------------------------------
$rowInk = New-Object int[] $H
for ($y = 0; $y -lt $H; $y++) {
    $c = 0
    for ($x = 0; $x -lt $W; $x++) { if (Test-Ink $x $y) { $c++ } }
    $rowInk[$y] = $c
}

$threshold = [Math]::Max(2, [int]($W * 0.004))
$bands = New-Object System.Collections.ArrayList
$start = -1
for ($y = 0; $y -lt $H; $y++) {
    if ($rowInk[$y] -ge $threshold) {
        if ($start -lt 0) { $start = $y }
    } elseif ($start -ge 0) {
        [void]$bands.Add([pscustomobject]@{ Top = $start; Bottom = $y - 1 })
        $start = -1
    }
}
if ($start -ge 0) { [void]$bands.Add([pscustomobject]@{ Top = $start; Bottom = $H - 1 }) }

if ($bands.Count -eq 0) { throw 'Khong tim thay noi dung trong logo (anh trong?).' }

# Gộp dải quá mỏng vào dải trước để tránh dấu chấm/cạnh nhiễu tách rời.
$merged = New-Object System.Collections.ArrayList
foreach ($band in $bands) {
    if ($merged.Count -gt 0 -and ($band.Top - $merged[$merged.Count - 1].Bottom) -le [Math]::Max(2, [int]($H * 0.01))) {
        $merged[$merged.Count - 1].Bottom = $band.Bottom
    } else {
        [void]$merged.Add([pscustomobject]@{ Top = $band.Top; Bottom = $band.Bottom })
    }
}

# Khối chữ lớn nhất = "TGI" (chiều cao dải lớn nhất).
$main = $merged | Sort-Object { $_.Bottom - $_.Top } -Descending | Select-Object -First 1

$top = [Math]::Max(0, $main.Top)
$bottom = [Math]::Min($H - 1, $main.Bottom)

$left = $W; $right = 0
for ($y = $top; $y -le $bottom; $y++) {
    for ($x = 0; $x -lt $W; $x++) {
        if (Test-Ink $x $y) {
            if ($x -lt $left) { $left = $x }
            if ($x -gt $right) { $right = $x }
        }
    }
}

$cropW = $right - $left + 1
$cropH = $bottom - $top + 1
if ($cropW -le 0 -or $cropH -le 0) { throw 'Bounding box rong.' }

Write-Host ("[favicon] dai chinh: top={0} bottom={1} -> crop {2}x{3} tai ({4},{5})" -f $top, $bottom, $cropW, $cropH, $left, $top)

# ---------------------------------------------------------------------------
# 3. Nền vuông: cạnh = max(crop) / 0.80  (chừa ~10% mỗi bên)
# ---------------------------------------------------------------------------
$side = [int][Math]::Ceiling([Math]::Max($cropW, $cropH) / 0.80)
$padX = [int][Math]::Floor(($side - $cropW) / 2)
$padY = [int][Math]::Floor(($side - $cropH) / 2)

function New-Square([int] $size) {
    $bmp = New-Object System.Drawing.Bitmap $size, $size, ([System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $gr = [System.Drawing.Graphics]::FromImage($bmp)
    $gr.Clear([System.Drawing.Color]::White)
    $gr.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $gr.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
    $gr.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
    $gr.CompositingQuality = [System.Drawing.Drawing2D.CompositingQuality]::HighQuality

    $scale = $size / $side
    $dstRect = New-Object System.Drawing.RectangleF ($padX * $scale), ($padY * $scale), ($cropW * $scale), ($cropH * $scale)
    $srcRect = New-Object System.Drawing.RectangleF $left, $top, $cropW, $cropH
    $gr.DrawImage($srcBmp, $dstRect, $srcRect, [System.Drawing.GraphicsUnit]::Pixel)
    $gr.Dispose()
    return $bmp
}

function Save-Png($bitmap, [string] $path) {
    $bitmap.Save($path, [System.Drawing.Imaging.ImageFormat]::Png)
    Write-Host ("  -> {0} ({1} bytes)" -f (Split-Path -Leaf $path), (Get-Item $path).Length)
}

$pngSizes = @(16, 32, 48, 180, 192, 512)
$built = @{}
foreach ($s in $pngSizes) {
    $bmp = New-Square $s
    $built[$s] = $bmp
    switch ($s) {
        16  { Save-Png $bmp (Join-Path $outDir 'favicon-16x16.png') }
        32  { Save-Png $bmp (Join-Path $outDir 'favicon-32x32.png') }
        48  { Save-Png $bmp (Join-Path $outDir 'favicon-48x48.png') }
        180 { Save-Png $bmp (Join-Path $outDir 'apple-touch-icon.png') }
        192 { Save-Png $bmp (Join-Path $outDir 'icon-192.png') }
        512 { Save-Png $bmp (Join-Path $outDir 'icon-512.png') }
    }
}

# ---------------------------------------------------------------------------
# 4. favicon.ico — nhiều kích thước, mỗi entry là một PNG (Vista+ và mọi
#    trình duyệt hiện đại đều đọc được).
# ---------------------------------------------------------------------------
$icoSizes = @(16, 32, 48)
$payloads = @()
foreach ($s in $icoSizes) {
    $ms = New-Object System.IO.MemoryStream
    $built[$s].Save($ms, [System.Drawing.Imaging.ImageFormat]::Png)
    $payloads += , $ms.ToArray()
    $ms.Dispose()
}

$fs = [System.IO.File]::Create($icoPath)
$bw = New-Object System.IO.BinaryWriter $fs
$bw.Write([uint16]0)                  # reserved
$bw.Write([uint16]1)                  # type = icon
$bw.Write([uint16]$icoSizes.Count)    # count

$offset = 6 + 16 * $icoSizes.Count
for ($i = 0; $i -lt $icoSizes.Count; $i++) {
    $d = $icoSizes[$i]
    $bw.Write([byte]($(if ($d -ge 256) { 0 } else { $d })))   # width
    $bw.Write([byte]($(if ($d -ge 256) { 0 } else { $d })))   # height
    $bw.Write([byte]0)                                        # palette
    $bw.Write([byte]0)                                        # reserved
    $bw.Write([uint16]1)                                      # planes
    $bw.Write([uint16]32)                                     # bpp
    $bw.Write([uint32]$payloads[$i].Length)
    $bw.Write([uint32]$offset)
    $offset += $payloads[$i].Length
}
foreach ($p in $payloads) { $bw.Write($p) }
$bw.Flush(); $bw.Dispose(); $fs.Dispose()
Write-Host ("  -> favicon.ico ({0} bytes, {1} kich thuoc)" -f (Get-Item $icoPath).Length, $icoSizes.Count)

foreach ($bmp in $built.Values) { $bmp.Dispose() }
$srcBmp.Dispose()

Write-Host '[favicon] xong.'
