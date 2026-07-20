@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

set "BACKUP_FILE=%~1"
if not defined BACKUP_FILE (
    echo Укажите путь к SQL-файлу резервной копии.
    set /p "BACKUP_FILE=Путь: "
)

if not exist "%BACKUP_FILE%" (
    echo ОШИБКА: файл не найден: %BACKUP_FILE%
    goto :error
)

echo.
echo ВНИМАНИЕ: текущая база Outline будет полностью заменена.
choice /C YN /N /M "Продолжить восстановление? [Y/N]: "
if errorlevel 2 (
    echo Восстановление отменено.
    exit /b 0
)

echo Остановка приложения...
docker compose -f "%OUTLINE_COMPOSE%" stop outline >nul
if errorlevel 1 goto :error

echo Пересоздание базы данных...
docker compose -f "%OUTLINE_COMPOSE%" exec -T postgres dropdb -U outline --if-exists --force outline
if errorlevel 1 goto :restart_error
docker compose -f "%OUTLINE_COMPOSE%" exec -T postgres createdb -U outline -O outline outline
if errorlevel 1 goto :restart_error

echo Импорт резервной копии...
type "%BACKUP_FILE%" | docker compose -f "%OUTLINE_COMPOSE%" exec -T postgres psql -v ON_ERROR_STOP=1 -U outline -d outline
if errorlevel 1 goto :restart_error

echo Запуск приложения...
docker compose -f "%OUTLINE_COMPOSE%" start outline
if errorlevel 1 goto :error

echo Восстановление завершено.
pause
exit /b 0

:restart_error
echo ОШИБКА: восстановление прервано. Приложение будет запущено для диагностики.
docker compose -f "%OUTLINE_COMPOSE%" start outline >nul 2>&1
goto :error

:error
echo Операция не завершена.
pause
exit /b 1
