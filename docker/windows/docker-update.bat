@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "%OUTLINE_ENV%" (
    echo ОШИБКА: docker.env не найден. Сначала запустите docker-init.bat.
    goto :error
)

if not exist "backups" mkdir "backups"
for /f "usebackq delims=" %%T in (`powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"`) do set "STAMP=%%T"
set "BACKUP_FILE=backups\before_update_%STAMP%.sql"

echo 1/4. Резервное копирование базы...
docker compose -f "%OUTLINE_COMPOSE%" exec -T postgres pg_dump -U outline -d outline --clean --if-exists > "%BACKUP_FILE%"
if errorlevel 1 (
    if exist "%BACKUP_FILE%" del /q "%BACKUP_FILE%"
    goto :error
)

echo 2/4. Получение изменений репозитория...
where git >nul 2>&1
if errorlevel 1 (
    echo Git не найден, получение изменений пропущено.
) else (
    git pull --ff-only
    if errorlevel 1 goto :error
)

echo 3/4. Пересборка русского образа...
docker compose -f "%OUTLINE_COMPOSE%" build --pull outline
if errorlevel 1 goto :error

echo 4/4. Обновление контейнеров...
docker compose -f "%OUTLINE_COMPOSE%" up -d --remove-orphans
if errorlevel 1 goto :error

docker compose -f "%OUTLINE_COMPOSE%" ps
echo.
echo Обновление завершено.
echo Резервная копия: %OUTLINE_ROOT%\%BACKUP_FILE%
pause
exit /b 0

:error
echo ОШИБКА: обновление не завершено. Проверьте логи и сохранённую резервную копию.
pause
exit /b 1
