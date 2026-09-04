@echo off
setlocal enabledelayedexpansion

:: 1. SET ENVIRONMENT PATH & ROOT WORKING DIRECTORY
set "PATH=C:\xampp\php;C:\xampp\apache\bin;C:\xampp\mysql\bin;%PATH%"
cd /d "C:\xampp"

:: 2. START APACHE & MYSQL (IF NOT RUNNING)
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="1" (
    start /B "" "C:\xampp\apache\bin\httpd.exe"
)

tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="1" (
    start /B "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone
)

:: 3. WAIT 2 SECONDS FOR DATABASE & SERVER
timeout /t 2 /nobreak >nul

:: 4. LAUNCH PROJECT ROOT (OPENS INDEX / LOGIN SCREEN)
set TARGET_URL=http://localhost/weighbridge-printS/

if exist "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe" --app=%TARGET_URL% --start-maximized --disable-http-cache --allow-running-insecure-content
) else if exist "C:\Program Files\Microsoft\Edge\Application\msedge.exe" (
    start "" "C:\Program Files\Microsoft\Edge\Application\msedge.exe" --app=%TARGET_URL% --start-maximized --disable-http-cache --allow-running-insecure-content
) else if exist "C:\Program Files\Google\Chrome\Application\chrome.exe" (
    start "" "C:\Program Files\Google\Chrome\Application\chrome.exe" --app=%TARGET_URL% --start-maximized --disable-http-cache --allow-running-insecure-content
) else (
    start "" %TARGET_URL%
)

exit