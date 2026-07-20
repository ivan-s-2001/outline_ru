@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0\.."

if not exist ".env" (
  echo ОШИБКА: файл .env не найден. Сначала запустите osp\setup.cmd
  exit /b 1
)

if not exist "build\server\index.js" (
  echo ОШИБКА: приложение не собрано. Сначала запустите osp\build.cmd
  exit /b 1
)

set "NODE_ENV=production"
set "YARN_ENABLE_GLOBAL_CACHE=true"
set "YARN_NM_MODE=hardlinks-global"
if defined LOCALAPPDATA set "YARN_GLOBAL_FOLDER=%LOCALAPPDATA%\Yarn\Berry"

corepack yarn start
endlocal
