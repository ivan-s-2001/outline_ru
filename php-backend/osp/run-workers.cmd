@echo off
setlocal
cd /d "%~dp0.."

echo Outline background workers started.
echo Press Ctrl+C to stop.

:loop
php yii mail/run 50
php yii webhook/run 50
timeout /t 30 /nobreak >nul
goto loop
