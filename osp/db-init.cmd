@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0\.."

where mariadb >nul 2>&1
if errorlevel 1 (
  echo ОШИБКА: клиент MariaDB не найден. Включите модуль MariaDB в Open Server Panel.
  exit /b 1
)

echo Создание базы outline и локального пользователя outline...
mariadb --host=127.0.0.1 --port=3306 --user=root %* < "osp\init-db.sql"
if errorlevel 1 (
  echo.
  echo Если у root задан пароль, повторите: osp\db-init.cmd -p
  exit /b 1
)

echo База MariaDB подготовлена.
endlocal
