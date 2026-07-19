@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "%OUTLINE_ENV%" (
    echo Файл docker.env не найден.
    call "%~dp0docker-init.bat"
    if errorlevel 1 goto :error
    echo.
    echo Настройте авторизацию в docker.env и повторите запуск.
    pause
    exit /b 0
)

echo Сборка и запуск Outline RU...
docker compose -f "%OUTLINE_COMPOSE%" up -d --build --remove-orphans
if errorlevel 1 goto :error

echo.
docker compose -f "%OUTLINE_COMPOSE%" ps
echo.
echo Outline: http://localhost:3000
echo Для просмотра запуска используйте docker-logs.bat.
pause
exit /b 0

:error
echo ОШИБКА: Outline RU не запущен.
pause
exit /b 1
