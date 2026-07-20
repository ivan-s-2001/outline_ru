@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

echo Состояние контейнеров Outline RU:
echo.
docker compose -f "%OUTLINE_COMPOSE%" ps
echo.
echo Проверка HTTP:
powershell -NoProfile -Command "try { $r=Invoke-WebRequest -UseBasicParsing -TimeoutSec 5 http://localhost:3000/_health; Write-Host ('HTTP ' + [int]$r.StatusCode + ': ' + $r.Content) } catch { Write-Host ('Сервис недоступен: ' + $_.Exception.Message); exit 1 }"
echo.
pause
exit /b 0

:error
pause
exit /b 1
