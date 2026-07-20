@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0.."

where php >nul 2>nul
if errorlevel 1 (
  echo PHP не найден в PATH. Включите модуль PHP 8.2+ в OSPanel.
  exit /b 1
)

if not exist ".env" (
  echo Файл .env не найден. Скопируйте .env.example в .env и заполните настройки.
  exit /b 1
)

php yii migrate --interactive=0
exit /b %errorlevel%
