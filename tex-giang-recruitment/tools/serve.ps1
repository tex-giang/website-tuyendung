# Chạy server phát triển với extension cần thiết của dự án.
# Windows: bấm chuột phải > Run with PowerShell, hoặc: powershell -ExecutionPolicy Bypass -File tools/serve.ps1
# Trên Linux/macOS/hosting: chỉ cần `php -S 127.0.0.1:8000 -t public` vì extension đã bật sẵn.

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location -LiteralPath $projectRoot

$port = if ($env:PORT) { $env:PORT } else { '8000' }

Write-Host "TEX-GIANG Recruitment — http://127.0.0.1:$port" -ForegroundColor Green
Write-Host "Admin: http://127.0.0.1:$port/admin/login" -ForegroundColor DarkGray

php -d extension=pdo_mysql -d extension=mbstring -S "127.0.0.1:$port" -t public
