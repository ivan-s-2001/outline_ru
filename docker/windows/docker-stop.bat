@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

echo Остановка контейнеров Outline RU без удаления данных...
docker compose -f "%OUTLINE_COMPOSE%" down --remove-orphans
if errorlevel 1 goto :error

echo Контейнеры остановлены. Docker volumes сохранены.
pause
exit /b 0

:error
echo ОШИБКА: не удалось остановить контейнеры.
pause
exit /b 1
