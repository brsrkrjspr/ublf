@echo off
title PHP Server - Lost & Found
color 0A
echo ========================================
echo   UB Lost & Found - PHP Server
echo ========================================
echo.
echo Starting server on http://localhost:8000
echo.
echo Keep this window open while using the server.
echo Press Ctrl+C to stop the server.
echo.
echo ========================================
echo.

cd /d "%~dp0htdocs\public"

if exist "C:\xampp\php\php.exe" (
    C:\xampp\php\php.exe -S localhost:8000
) else if exist "D:\xampp\php\php.exe" (
    D:\xampp\php\php.exe -S localhost:8000
) else (
    echo ERROR: PHP not found at C:\xampp\php\php.exe
    echo.
    echo Please install XAMPP or update the path in this file.
    echo.
    pause
)

