@echo off
title Noir Live Demo Tunnel
echo ====================================================
echo   Starting Noir Live Demo Server & Public Tunnel
echo ====================================================
echo.

:: Check if MySQL port 3306 is already active
netstat -ano | findstr :3306 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo Starting MySQL database daemon...
    if exist "c:\xamp\mysql\bin\mysqld.exe" (
        start /B "" "c:\xamp\mysql\bin\mysqld.exe" --defaults-file="c:\xamp\mysql\bin\my.ini"
        timeout /t 3 /nobreak >nul
    )
) else (
    echo MySQL database is already active.
)

:: Check if PHP port 8088 is already running
netstat -ano | findstr :8088 | findstr LISTENING >nul
if %errorlevel% neq 0 (
    echo Starting local PHP server on port 8088...
    start /B c:\xamp\php\php.exe -S 127.0.0.1:8088 -t public
    timeout /t 2 /nobreak >nul
) else (
    echo Local PHP server on port 8088 is already active.
)

echo.
echo Launching Cloudflare Public HTTPS Tunnel (HTTP2)...
echo Look for your https://*.trycloudflare.com link below:
echo.
.\cloudflared.exe tunnel --edge-ip-version 4 --protocol http2 --url http://127.0.0.1:8088
pause
