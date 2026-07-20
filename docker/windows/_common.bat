@echo off
chcp 65001 >nul

cd /d "%~dp0\..\.."
set "OUTLINE_ROOT=%CD%"
set "OUTLINE_COMPOSE=docker-compose.ru.yml"
set "OUTLINE_ENV=docker.env"

where docker >nul 2>&1
if errorlevel 1 (
    echo ОШИБКА: Docker не найден в PATH.
    echo Установите и запустите Docker Desktop.
    exit /b 1
)

docker info >nul 2>&1
if errorlevel 1 (
    echo ОШИБКА: Docker Desktop не запущен или недоступен.
    exit /b 1
)

docker compose version >nul 2>&1
if errorlevel 1 (
    echo ОШИБКА: Docker Compose V2 недоступен.
    exit /b 1
)

if not exist "%OUTLINE_COMPOSE%" (
    echo ОШИБКА: не найден %OUTLINE_COMPOSE%.
    exit /b 1
)

exit /b 0
