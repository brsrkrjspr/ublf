@echo off
echo Starting PHP Server on http://localhost:8000
echo.
cd /d "%~dp0htdocs\public"

REM Try XAMPP first
if exist "C:\xampp\php\php.exe" (
    C:\xampp\php\php.exe -S localhost:8000
    goto :end
)

REM Try PHP in PATH
where php >nul 2>&1
if %errorlevel% == 0 (
    php -S localhost:8000
    goto :end
)

REM Try other common locations
if exist "D:\xampp\php\php.exe" (
    D:\xampp\php\php.exe -S localhost:8000
    goto :end
)

echo ERROR: PHP not found!
echo Please install XAMPP or add PHP to your PATH.
pause
:end

