@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

echo ВНИМАНИЕ: будут удалены контейнеры и ВСЕ Docker volumes Outline RU.
echo Будут потеряны база данных, настройки и загруженные файлы.
echo Сначала рекомендуется запустить docker-backup.bat.
echo.
set /p "CONFIRM=Для подтверждения введите УДАЛИТЬ: "
if /I not "%CONFIRM%"=="УДАЛИТЬ" (
    echo Сброс отменён.
    exit /b 0
)

docker compose -f "%OUTLINE_COMPOSE%" down --volumes --remove-orphans
if errorlevel 1 goto :error

echo Все контейнеры и volumes Outline RU удалены.
pause
exit /b 0

:error
echo ОШИБКА: сброс не завершён.
pause
exit /b 1
