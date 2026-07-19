@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "%OUTLINE_ENV%" (
    echo ОШИБКА: docker.env не найден. Сначала запустите docker-init.bat.
    goto :error
)

echo Перезапуск Outline RU...
docker compose -f "%OUTLINE_COMPOSE%" up -d --build --force-recreate outline
if errorlevel 1 goto :error

docker compose -f "%OUTLINE_COMPOSE%" ps
echo Перезапуск завершён.
pause
exit /b 0

:error
echo ОШИБКА: перезапуск не завершён.
pause
exit /b 1
