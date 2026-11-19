$ErrorActionPreference = 'Stop'
Set-Location -LiteralPath $PSScriptRoot
$env:PYINSTALLER_CONFIG_DIR = Join-Path $PSScriptRoot 'build\cache'
$taskTemplate = Join-Path $PSScriptRoot 'assets\template.xlsx'
if (-not (Test-Path -LiteralPath $taskTemplate -PathType Leaf)) { throw 'Missing assets\template.xlsx' }
$templateMetadata = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'assets\template_info.json') -Raw | ConvertFrom-Json
if ($templateMetadata.preservation -ne 'verbatim') { throw 'The supplied template must be embedded verbatim.' }
$templateHash = (Get-FileHash -LiteralPath $taskTemplate -Algorithm SHA256).Hash
if ($templateHash -ine $templateMetadata.sha256 -or $templateHash -ine $templateMetadata.source_sha256) { throw 'Template must match the original supplied file byte for byte.' }
# Build with Python and Windows DLL search paths only. An unrelated Poppler ICU
# on PATH has suffixed exports incompatible with the Windows ICU used by Qt.
$taskPython = (Get-Command python).Source
$taskPythonFolder = Split-Path -Parent $taskPython
$taskOriginalPath = $env:PATH
try {
    $env:PATH = "$taskPythonFolder;$taskPythonFolder\Scripts;$env:SystemRoot\System32;$env:SystemRoot"
    python -m PyInstaller --noconfirm --clean --onefile --windowed --name BangKeRunner --icon assets\app.ico --add-data 'assets\template.xlsx;assets' --exclude-module win32com --exclude-module pythoncom --exclude-module openpyxl --exclude-module PIL --exclude-module matplotlib --exclude-module scipy --exclude-module pyarrow --exclude-module numba --exclude-module numexpr --exclude-module bottleneck --exclude-module IPython --exclude-module sqlalchemy --exclude-module xlsxwriter --exclude-module tables --exclude-module PyQt5 --exclude-module tkinter --exclude-module PyQt6 --exclude-module PySide2 --exclude-module PySide6.QtQml --exclude-module PySide6.QtQuick --exclude-module PySide6.QtWebEngineCore --exclude-module PySide6.QtWebEngineWidgets --exclude-module PySide6.QtNetwork --exclude-module PySide6.Qt3DCore --exclude-module pytest --exclude-module bs4 --exclude-module html5lib --exclude-module odf --exclude-module fsspec --exclude-module jinja2 --distpath dist --workpath build app.py
    if ($LASTEXITCODE -ne 0) { throw 'Đóng gói không thành công.' }
} finally {
    $env:PATH = $taskOriginalPath
}
Write-Host 'Đã tạo dist\BangKeRunner.exe (original template.xlsx embedded)'
