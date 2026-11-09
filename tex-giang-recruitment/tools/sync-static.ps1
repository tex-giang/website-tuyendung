# ============================================================================
#  ĐỒNG BỘ GIAO DIỆN:  PHP (nguồn)  →  WEB TĨNH (bản sao thiết kế)
#
#  Quy trình: sửa giao diện ở PHP trước → chạy script này → kiểm tra website
#  tĩnh. Script chỉ đồng bộ phần DÙNG CHUNG (CSS/JS nền tảng), không đụng vào
#  JS demo riêng của web tĩnh (apply-form, jobs-data, news-data...).
#
#  Cách dùng:
#    powershell -ExecutionPolicy Bypass -File tools/sync-static.ps1
#    powershell -ExecutionPolicy Bypass -File tools/sync-static.ps1 -Check
#    powershell -ExecutionPolicy Bypass -File tools/sync-static.ps1 -Images
#
#    -Check   : chỉ báo cáo file nào lệch, KHÔNG ghi
#    -Images  : copy thêm ảnh mới/sửa trong public/assets/images → assets/images
#    -NoBuild : không build lại Tailwind của web tĩnh
# ============================================================================
param(
    [switch]$Check,
    [switch]$Images,
    [switch]$NoBuild
)

$ErrorActionPreference = 'Stop'

$phpRoot    = Split-Path -Parent $PSScriptRoot
$staticRoot = Split-Path -Parent $phpRoot          # E:\websitetuyendung
if (-not (Test-Path (Join-Path $staticRoot 'index.html'))) {
    throw "Không tìm thấy web tĩnh ở: $staticRoot"
}

# ---------------------------------------------------------------------------
# Danh sách file DÙNG CHUNG được đồng bộ tự động (PHP → static).
# Các file JS demo riêng của static (apply-form.js, jobs.js, jobs-data.js,
# news-data.js, admin.js, admin-data.js, apply-dialog.js, home-*-dialog.js...)
# KHÔNG nằm trong danh sách này vì bản PHP đã viết lại cho dữ liệu thật.
# ---------------------------------------------------------------------------
$sharedFiles = @(
    @{ php = 'public/assets/css/public.css';    static = 'assets/css/public.css';    desc = 'CSS trang công khai' },
    @{ php = 'public/assets/css/app.css';       static = 'assets/css/app.css';       desc = 'CSS component nền' },
    @{ php = 'public/assets/css/custom.css';    static = 'assets/css/custom.css';    desc = 'CSS tuỳ chỉnh' },
    @{ php = 'public/assets/js/main.js';        static = 'assets/js/main.js';        desc = 'JS dùng chung' },
    @{ php = 'public/assets/js/ui.js';          static = 'assets/js/ui.js';          desc = 'Tiện ích UI (header, bộ lọc, reveal)' },
    @{ php = 'public/assets/js/home-slider.js'; static = 'assets/js/home-slider.js'; desc = 'Slider trang chủ' }
)

Write-Host ''
Write-Host '  ĐỒNG BỘ GIAO DIỆN  PHP → WEB TĨNH' -ForegroundColor Green
Write-Host '  ------------------------------------------------' -ForegroundColor DarkGray
Write-Host "  PHP    : $phpRoot"
Write-Host "  Static : $staticRoot"
if ($Check) { Write-Host '  Chế độ : CHỈ KIỂM TRA (không ghi file)' -ForegroundColor Yellow }
Write-Host ''

$changed = 0
$same = 0

foreach ($f in $sharedFiles) {
    $src = Join-Path $phpRoot    $f.php
    $dst = Join-Path $staticRoot $f.static

    if (-not (Test-Path $src)) {
        Write-Host ("  [THIẾU] {0} — không thấy file nguồn {1}" -f $f.desc, $f.php) -ForegroundColor Red
        continue
    }
    if (-not (Test-Path $dst)) {
        Write-Host ("  [THIẾU] {0} — chưa có ở web tĩnh: {1}" -f $f.desc, $f.static) -ForegroundColor Yellow
        if (-not $Check) {
            $dir = Split-Path $dst -Parent
            if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
            Copy-Item $src $dst -Force
        }
        $changed++
        continue
    }

    $h1 = (Get-FileHash $src -Algorithm SHA256).Hash
    $h2 = (Get-FileHash $dst -Algorithm SHA256).Hash
    if ($h1 -eq $h2) {
        Write-Host ("  [OK]    {0}" -f $f.desc) -ForegroundColor DarkGray
        $same++
    } else {
        $size1 = (Get-Item $src).Length
        $size2 = (Get-Item $dst).Length
        if ($Check) {
            Write-Host ("  [LỆCH]  {0}  (php {1:N0} B / static {2:N0} B)" -f $f.desc, $size1, $size2) -ForegroundColor Yellow
        } else {
            Copy-Item $src $dst -Force
            Write-Host ("  [SYNC]  {0}  (php {1:N0} B / static {2:N0} B)" -f $f.desc, $size1, $size2) -ForegroundColor Cyan
        }
        $changed++
    }
}

# ---------------------------------------------------------------------------
# Ảnh (tuỳ chọn): copy ảnh có trong PHP nhưng thiếu/khác ở web tĩnh.
# Không đụng tới ảnh upload động (assets/uploads).
# ---------------------------------------------------------------------------
if ($Images) {
    Write-Host ''
    Write-Host '  Đồng bộ ảnh public/assets/images → assets/images' -ForegroundColor Green
    $imgSrc = Join-Path $phpRoot 'public/assets/images'
    $imgDst = Join-Path $staticRoot 'assets/images'
    $imgChanged = 0
    Get-ChildItem -LiteralPath $imgSrc -Recurse -File | ForEach-Object {
        $rel = $_.FullName.Substring($imgSrc.Length).TrimStart('\')
        $target = Join-Path $imgDst $rel
        if (-not (Test-Path $target) -or ((Get-FileHash $_.FullName).Hash -ne (Get-FileHash $target).Hash)) {
            $imgChanged++
            if (-not $Check) {
                $dir = Split-Path $target -Parent
                if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Force -Path $dir | Out-Null }
                Copy-Item $_.FullName $target -Force
            }
        }
    }
    Write-Host ("  {0} ảnh {1}" -f $imgChanged, $(if ($Check) { 'cần copy' } else { 'đã copy' })) -ForegroundColor Cyan
}

# ---------------------------------------------------------------------------
# Build lại Tailwind cho web tĩnh (glob quét *.html + assets/js nên khác PHP).
# ---------------------------------------------------------------------------
if (-not $NoBuild -and -not $Check) {
    $staticPkg = Join-Path $staticRoot 'package.json'
    $tw = Join-Path $staticRoot 'node_modules/tailwindcss'
    Write-Host ''
    if ((Test-Path $staticPkg) -and (Test-Path $tw)) {
        Write-Host '  Build lại Tailwind cho web tĩnh...' -ForegroundColor Green
        Push-Location $staticRoot
        try {
            & npm run build:css 2>&1 | Select-Object -Last 2 | ForEach-Object { "  " + $_ }
        } finally {
            Pop-Location
        }
    } else {
        Write-Host '  [BỎ QUA] Không có node_modules ở web tĩnh — chưa build lại Tailwind.' -ForegroundColor Yellow
        Write-Host '           Cần thì chạy: cd E:\websitetuyendung; npm ci; npm run build:css' -ForegroundColor Yellow
    }
}

Write-Host ''
Write-Host '  ------------------------------------------------' -ForegroundColor DarkGray
if ($Check) {
    Write-Host ("  Kết quả: {0} file lệch, {1} file khớp. Chạy lại không có -Check để đồng bộ." -f $changed, $same) -ForegroundColor Yellow
} else {
    Write-Host ("  Đã đồng bộ {0} file, {1} file đã khớp sẵn." -f $changed, $same) -ForegroundColor Green
}
Write-Host ''
Write-Host '  NHỚ: phần HTML/markup phải port thủ công (xem docs/06-dong-bo-giao-dien.md).'
Write-Host '  Kiểm tra web tĩnh:  cd E:\websitetuyendung; npm run preview → http://127.0.0.1:4173/'
Write-Host ''
