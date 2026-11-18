$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$env:PYINSTALLER_CONFIG_DIR = Join-Path $PSScriptRoot 'build\cache'
$taskTcl = Join-Path $PSScriptRoot 'vendor\tcl'
if (Test-Path -LiteralPath (Join-Path $taskTcl 'tcl8.6\init.tcl')) {
    $env:TCL_LIBRARY = Join-Path $taskTcl 'tcl8.6'
    $env:TK_LIBRARY = Join-Path $taskTcl 'tk8.6'
}
python -m PyInstaller --noconfirm --clean --onefile --windowed --name BangKeRunner --exclude-module numpy --exclude-module pandas --exclude-module win32com --exclude-module pythoncom --exclude-module openpyxl --exclude-module PIL --exclude-module matplotlib --distpath dist --workpath build app.py
if ($LASTEXITCODE -ne 0) { throw 'Đóng gói không thành công.' }
Write-Host 'Đã tạo dist\BangKeRunner.exe'
