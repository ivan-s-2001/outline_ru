@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0.."

where node >nul 2>nul
if errorlevel 1 (
  echo Node.js 22 не найден в PATH. Включите модуль Node.js в OSPanel.
  exit /b 1
)

if not exist ".env" (
  echo Файл .env не найден. Скопируйте .env.example в .env и заполните настройки.
  exit /b 1
)

if not exist "services\collaboration\dist\collaboration.mjs" (
  echo Collaboration runtime не собран. Запустите build-source.cmd или скачайте готовый OSPanel artifact.
  exit /b 1
)

node services\collaboration\dist\collaboration.mjs
exit /b %errorlevel%
