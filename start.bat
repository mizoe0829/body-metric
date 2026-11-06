@echo off
title BodyMetric Launcher
cd /d "%~dp0"

echo [1/3] Starting Go AI Worker on port 8086...
cd ai-service
start /b "" body-metric-ai.exe
cd ..

ping 127.0.0.1 -n 2 > nul

echo [2/3] Starting Laravel Web Server on port 8000...
start /b "" php artisan serve --port=8000

ping 127.0.0.1 -n 3 > nul

echo [3/3] Opening BodyMetric in default browser...
start http://localhost:8000

echo.
echo ========================================================
echo   BodyMetric is running! (by mizoe)
echo   Dashboard: http://localhost:8000
echo   To stop the application, run stop.bat
echo ========================================================
