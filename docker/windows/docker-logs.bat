@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

echo Логи Outline RU. Для выхода нажмите Ctrl+C.
docker compose -f "%OUTLINE_COMPOSE%" logs --follow --tail=200 outline
exit /b %errorlevel%

:error
pause
exit /b 1
