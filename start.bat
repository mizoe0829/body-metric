@echo off
chcp 65001 > nul
echo ===================================================
echo   BodyMetric 起動スクリプト (by mizoe)
echo ===================================================
cd /d "%~dp0"

echo [1/3] Go AI マイクロサービス (ポート 8086) を起動しています...
cd ai-service
start /b "" body-metric-ai.exe
cd ..

timeout /t 1 /nobreak > nul

echo [2/3] Laravel Web サーバー (ポート 8000) を起動しています...
start /b "" php artisan serve --port=8000

timeout /t 2 /nobreak > nul

echo [3/3] ブラウザで BodyMetric を開いています...
start http://localhost:8000

echo ===================================================
echo   🚀 BodyMetric が起動しました！
echo   終了するときは stop.bat を実行してください。
echo ===================================================
