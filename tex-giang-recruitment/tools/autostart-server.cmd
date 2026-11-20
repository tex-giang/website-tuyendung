@echo off
rem ============================================================
rem  TEX-GIANG Recruitment — tu khoi dong server dev (cong 8000)
rem  Duoc Task Scheduler "TexGiangRecruitmentServer" goi khi dang nhap Windows.
rem  Quan ly task: taskschd.msc  |  Log: storage\logs\dev-server.log
rem  Tat vinh vien: schtasks /Delete /TN TexGiangRecruitmentServer /F
rem ============================================================
cd /d "E:\websitetuyendung\tex-giang-recruitment"

rem Neu cong 8000 da co server chay thi khong mo them nua.
netstat -ano | findstr /C:":8000 " | findstr /C:"LISTENING" >nul 2>&1
if not errorlevel 1 exit /b 0

echo [%date% %time%] Khoi dong server dev tai 127.0.0.1:8000 >> "storage\logs\dev-server.log"
"C:\php\php-8.5.5-nts-Win32-vs17-x64\php.exe" -d extension=pdo_mysql -d extension=mbstring -S 127.0.0.1:8000 -t public public\router.php >> "storage\logs\dev-server.log" 2>&1
