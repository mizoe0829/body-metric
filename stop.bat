@echo off
title BodyMetric Stopper
cd /d "%~dp0"

echo [*] Stopping body-metric-ai.exe...
taskkill /F /IM body-metric-ai.exe 2>nul

echo [*] Stopping PHP server on port 8000...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a 2>nul
)

echo.
echo ========================================================
echo   BodyMetric stopped successfully.
echo ========================================================
ping 127.0.0.1 -n 2 > nul
