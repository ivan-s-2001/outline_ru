@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "%OUTLINE_ENV%" (
    echo ОШИБКА: docker.env не найден. Сначала запустите docker-init.bat.
    goto :error
)

set "ADMIN_EMAIL=%~1"
if not defined ADMIN_EMAIL set /p "ADMIN_EMAIL=Email первого администратора: "
if not defined ADMIN_EMAIL goto :error

echo Создание рабочего пространства и администратора %ADMIN_EMAIL%...
docker compose -f "%OUTLINE_COMPOSE%" run --rm outline node build/server/scripts/seed.js "%ADMIN_EMAIL%"
if errorlevel 1 goto :error

echo Команда выполнена. Проверьте вывод выше и почтовый ящик администратора.
pause
exit /b 0

:error
echo ОШИБКА: администратор не создан.
pause
exit /b 1
