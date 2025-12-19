@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

set "PROJECT_DIR=%~dp0"
cd /d "%PROJECT_DIR%"

echo ===============================
echo Starting Laravel Cleanup
echo ===============================

:: Check PHP availability
where php >nul 2>&1
if errorlevel 1 (
    echo PHP not found in PATH.
    echo Please install PHP or add it to PATH.
    pause
    exit /b
)

echo Clearing Laravel caches...

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan optimize:clear

echo ===============================
echo Cleanup Completed Successfully!
echo ===============================

cmd /k
