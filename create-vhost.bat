@echo off
setlocal enabledelayedexpansion

:: ======================================================
:: XAMPP Virtual Host Manager (Interactive)
:: Features: List, Add, Remove vhosts
:: ======================================================

:: Variables
set vhost_file=C:\xampp\apache\conf\extra\httpd-vhosts.conf
set hosts_file=C:\Windows\System32\drivers\etc\hosts

:: Check for admin rights
>nul 2>&1 "%SYSTEMROOT%\system32\cacls.exe" "%SYSTEMROOT%\system32\config\system"
if '%errorlevel%' NEQ '0' (
    echo ❌ This script requires administrative privileges.
    echo.
    echo 👉 Please right-click and run as Administrator.
    pause
    exit /b
)

:menu
cls
echo ================================
echo   XAMPP Virtual Host Manager
echo ================================
echo 1. List all vhosts
echo 2. Add a new vhost
echo 3. Remove a vhost
echo 4. Exit
echo ================================
set /p choice=Choose an option [1-4]: 

if "%choice%"=="1" goto list
if "%choice%"=="2" goto add
if "%choice%"=="3" goto remove
if "%choice%"=="4" exit
goto menu

:list
cls
echo === Current Virtual Hosts ===
echo.
for /f "tokens=2 delims= " %%a in ('findstr /R "ServerName " "%vhost_file%"') do (
    echo - %%a
)
echo.
pause
goto menu

:add
cls
echo === Add New Virtual Host ===
set /p project_name=Enter project name: 
set /p project_dir=Enter full project directory (e.g. C:\xampp\htdocs\myproject): 
set domain=app.%project_name%.com

:: Create project folder if it doesn't exist
if not exist "%project_dir%" (
    mkdir "%project_dir%"
    echo ^<h1^>Welcome to %domain%!^</h1^> > "%project_dir%\index.php"
    echo Project folder created at %project_dir%
) else (
    echo Project folder already exists.
)

:: Add VirtualHost to Apache config
echo.>> "%vhost_file%"
echo ^<VirtualHost *:80^> >> "%vhost_file%"
echo     ServerAdmin webmaster@%domain% >> "%vhost_file%"
echo     DocumentRoot "%project_dir%" >> "%vhost_file%"
echo     ServerName %domain% >> "%vhost_file%"
echo     ErrorLog "logs/%project_name%-error.log" >> "%vhost_file%"
echo     CustomLog "logs/%project_name%-access.log" common >> "%vhost_file%"
echo     ^<Directory "%project_dir%"^> >> "%vhost_file%"
echo         Options Indexes FollowSymLinks Includes ExecCGI >> "%vhost_file%"
echo         AllowOverride All >> "%vhost_file%"
echo         Require all granted >> "%vhost_file%"
echo     ^</Directory^> >> "%vhost_file%"
echo ^</VirtualHost^> >> "%vhost_file%"

echo VirtualHost added to Apache config.

:: Add to hosts file if not already there
findstr /C:"%domain%" "%hosts_file%" >nul
if errorlevel 1 (
    echo 127.0.0.1 %domain% >> "%hosts_file%"
    echo %domain% mapped to 127.0.0.1 in hosts file.
) else (
    echo %domain% already exists in hosts file.
)

:: Restart Apache
echo Restarting Apache...
net stop Apache2.4 >nul 2>&1
net start Apache2.4 >nul 2>&1

echo ✅ Vhost created: http://%domain%
pause
goto menu

:remove
cls
echo === Remove Virtual Host ===
set /p rdomain=Enter domain to remove (e.g. app.test.com): 

:: Remove from httpd-vhosts.conf
(for /f "usebackq delims=" %%i in ("%vhost_file%") do (
    echo %%i | findstr /C:"ServerName %rdomain%" >nul
    if errorlevel 1 (
        echo %%i>>"%vhost_file%.tmp"
    ) else (
        set skip=1
    )
    if defined skip (
        echo %%i | findstr /C:"</VirtualHost>" >nul && set skip=
    )
)) 

move /y "%vhost_file%.tmp" "%vhost_file%" >nul

:: Remove from hosts file
findstr /V "%rdomain%" "%hosts_file%" > "%hosts_file%.tmp"
move /y "%hosts_file%.tmp" "%hosts_file%" >nul

:: Restart Apache
echo Restarting Apache...
net stop Apache2.4 >nul 2>&1
net start Apache2.4 >nul 2>&1

echo ✅ Removed vhost: %rdomain%
pause
goto menu
