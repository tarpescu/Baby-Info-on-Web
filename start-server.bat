@echo off
REM Porneste serverul de dev cu limitele de upload ridicate (necesare pentru video).
cd /d "%~dp0"
php -S localhost:8000 -d upload_max_filesize=50M -d post_max_size=52M router.php
