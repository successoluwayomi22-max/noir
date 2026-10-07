@echo off
title Noir Cloudflare Tunnel
echo ==============================================
echo    Starting Cloudflare Quick Tunnel for Noir
echo    Target: http://127.0.0.1:8088
echo ==============================================
echo.
.\cloudflared.exe tunnel --edge-ip-version 4 --protocol http2 --url http://127.0.0.1:8088
pause
