@echo off
rem ============================================================================
rem  TEX-GIANG RECRUITMENT - Chạy website ở máy local (Windows)
rem  Double-click file này để chạy. Không cần mở PowerShell gõ lệnh.
rem
rem  Cách dùng:
rem    - Double-click RUN.bat            → chạy cổng 8000, tự mở trình duyệt
rem    - RUN.bat 8080                    → chạy cổng khác nếu 8000 bận
rem    - Đặt biến môi trường NO_BROWSER=1 nếu không muốn tự mở trình duyệt
rem    - Đặt PHP_EXE nếu PHP không nằm trong PATH (vd. C:\php\php.exe)
rem ============================================================================
setlocal EnableExtensions
title TEX-GIANG Recruitment - Dev Server
cd /d "%~dp0"

set "PORT=8000"
if not "%~1"=="" set "PORT=%~1"
if defined PORT_OVERRIDE set "PORT=%PORT_OVERRIDE%"

rem ---------------------------------------------------------------------------
rem 1. Tìm PHP: ưu tiên PATH, sau đó các đường dẫn cài phổ biến
rem ---------------------------------------------------------------------------
set "PHP_EXE="
for %%P in (php.exe) do if not "%%~$PATH:P"=="" set "PHP_EXE=%%~$PATH:P"

if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\laragon\bin\php\php.exe" set "PHP_EXE=C:\laragon\bin\php\php.exe"
if not defined PHP_EXE if exist "C:\php\php.exe" set "PHP_EXE=C:\php\php.exe"
if not defined PHP_EXE if exist "C:\php\php-8.5.5-nts-Win32-vs17-x64\php.exe" set "PHP_EXE=C:\php\php-8.5.5-nts-Win32-vs17-x64\php.exe"
if not defined PHP_EXE if exist "C:\laragon\bin\php\php-8.5.5-nts-Win32-vs17-x64\php.exe" set "PHP_EXE=C:\laragon\bin\php\php-8.5.5-nts-Win32-vs17-x64\php.exe"

if not defined PHP_EXE (
  echo.
  echo [LOI] Khong tim thay PHP.
  echo   - Cai PHP 8.3+ roi them vao PATH, hoac
  echo   - Dat bien PHP_EXE = duong dan php.exe truoc khi chay file nay.
  echo.
  pause
  exit /b 1
)

echo.
echo   TEX-GIANG RECRUITMENT
echo   ---------------------------------------------
echo   PHP    : %PHP_EXE%
echo   Thu muc: %cd%
echo   Web    : http://127.0.0.1:%PORT%
echo   Admin  : http://127.0.0.1:%PORT%/admin/login
echo   ---------------------------------------------
echo   Nhan Ctrl + C de dung server.
echo.

rem ---------------------------------------------------------------------------
rem 2. Kiểm tra cổng có đang bận không
rem ---------------------------------------------------------------------------
netstat -ano | findstr /r /c:":%PORT% .*LISTENING" >nul 2>&1
if not errorlevel 1 (
  echo [LOI] Cong %PORT% dang bi chiem.
  echo       Chay lai bang:  RUN.bat 8080
  echo.
  pause
  exit /b 1
)

rem ---------------------------------------------------------------------------
rem 3. Kiểm tra extension bắt buộc trước khi chạy
rem ---------------------------------------------------------------------------
"%PHP_EXE%" -d extension=pdo_mysql -d extension=mbstring -r "exit(in_array('pdo_mysql', get_loaded_extensions()) && in_array('mbstring', get_loaded_extensions()) ? 0 : 1);" >nul 2>&1
if errorlevel 1 (
  echo [CANH BAO] Khong bat duoc extension pdo_mysql/mbstring bang dong lenh.
  echo             Kiem tra lai file php.ini hoac ban cai PHP thieu thu muc ext.
  echo.
  pause
)

rem ---------------------------------------------------------------------------
rem 4. Mở trình duyệt (trừ khi đặt NO_BROWSER=1)
rem ---------------------------------------------------------------------------
if not "%NO_BROWSER%"=="1" (
  start "" "http://127.0.0.1:%PORT%/"
  timeout /t 1 /nobreak >nul
  start "" "http://127.0.0.1:%PORT%/admin/login"
)

rem ---------------------------------------------------------------------------
rem 5. Chạy server (giữ nguyên cửa sổ này)
rem ---------------------------------------------------------------------------
"%PHP_EXE%" -d extension=pdo_mysql -d extension=mbstring -S 127.0.0.1:%PORT% -t public

echo.
echo [Server da dung]
pause
