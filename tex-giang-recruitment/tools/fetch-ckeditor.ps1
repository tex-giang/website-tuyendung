# Tải CKEditor 4.22.1 vào public/assets/vendor/ckeditor4
# (thư mục này KHÔNG nằm trong git để repo nhẹ — chạy script này sau khi clone)
#
# Cách dùng (PowerShell, tại thư mục gốc project):
#   powershell -ExecutionPolicy Bypass -File tools/fetch-ckeditor.ps1

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$dest = Join-Path $projectRoot 'public/assets/vendor/ckeditor4'
$tarball = Join-Path $env:TEMP 'ckeditor4-4.22.1.tgz'
$url = 'https://registry.npmjs.org/ckeditor4/-/ckeditor4-4.22.1.tgz'

Write-Host "→ Tải CKEditor 4.22.1 từ npm..."
Invoke-WebRequest -Uri $url -OutFile $tarball -UseBasicParsing

if (Test-Path -LiteralPath $dest) {
    Write-Host "→ Xoá thư mục cũ: $dest"
    Remove-Item -LiteralPath $dest -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $dest | Out-Null

Write-Host "→ Giải nén..."
tar -xzf $tarball -C $dest --strip-components=1

if (-not (Test-Path -LiteralPath (Join-Path $dest 'ckeditor.js'))) {
    throw "Giải nén thất bại: không thấy ckeditor.js trong $dest"
}

Remove-Item -LiteralPath $tarball -Force -ErrorAction SilentlyContinue
Write-Host "✓ CKEditor 4.22.1 đã sẵn sàng tại public/assets/vendor/ckeditor4"
