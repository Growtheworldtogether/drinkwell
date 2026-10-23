@echo off
rem Makes drinkwell-release.zip for uploading to cPanel. Double-click it, or run scripts\make-release.bat
rem from the project folder. Your own .env and local database are never put in the zip.
cd /d "%~dp0.."

echo Building the site styles and scripts...
if exist public\hot del public\hot
call npm run build || goto :fail

echo Installing the PHP packages the live site needs (no testing tools)...
call composer install --no-dev --optimize-autoloader --no-interaction || goto :fail

echo Packing the zip...
php scripts\make-release.php
set RESULT=%ERRORLEVEL%

echo Putting the testing tools back for your computer...
call composer install --no-interaction >nul

if not "%RESULT%"=="0" goto :fail
echo.
echo Done. Upload drinkwell-release.zip from this folder to cPanel (see docs\DEPLOY-CPANEL.md).
pause
exit /b 0

:fail
echo.
echo Something went wrong above. Nothing was uploaded.
pause
exit /b 1
