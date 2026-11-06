@echo off
chcp 65001 > nul
echo ===================================================
echo   BodyMetric 停止スクリプト
echo ===================================================

echo [*] body-metric-ai.exe を停止しています...
taskkill /F /IM body-metric-ai.exe 2>nul

echo [*] ポート 8000 の Web サーバー (php) を停止しています...
for /f "tokens=5" %%a in ('netstat -aon ^| findstr ":8000" ^| findstr "LISTENING"') do (
    taskkill /F /PID %%a 2>nul
)

echo ===================================================
echo   ✅ BodyMetric を正常に停止しました。
echo ===================================================
timeout /t 2 /nobreak > nul
