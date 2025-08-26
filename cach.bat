@echo off
chcp 65001 >nul

set PROJECT_DIR=%~dp0
cd /d "%PROJECT_DIR%"

echo 🚀 Laravel Artisan...

php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan optimize:clear
php artisan livewire:discover

echo ✅ Cache cleared successfully!

cmd /k
