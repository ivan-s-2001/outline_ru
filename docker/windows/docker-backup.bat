@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "backups" mkdir "backups"
for /f "usebackq delims=" %%T in (`powershell -NoProfile -Command "Get-Date -Format yyyy-MM-dd_HH-mm-ss"`) do set "STAMP=%%T"
set "BACKUP_FILE=backups\outline_%STAMP%.sql"

echo Создание резервной копии PostgreSQL...
docker compose -f "%OUTLINE_COMPOSE%" exec -T postgres pg_dump -U outline -d outline --clean --if-exists > "%BACKUP_FILE%"
if errorlevel 1 (
    if exist "%BACKUP_FILE%" del /q "%BACKUP_FILE%"
    goto :error
)

for %%F in ("%BACKUP_FILE%") do set "BACKUP_SIZE=%%~zF"
if "%BACKUP_SIZE%"=="0" (
    del /q "%BACKUP_FILE%"
    echo ОШИБКА: создан пустой файл резервной копии.
    goto :error
)

echo Резервная копия создана:
echo %OUTLINE_ROOT%\%BACKUP_FILE%
pause
exit /b 0

:error
echo ОШИБКА: резервное копирование не завершено.
pause
exit /b 1
