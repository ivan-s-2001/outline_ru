@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0\.."

if not exist ".env" (
  echo ОШИБКА: файл .env не найден. Сначала запустите osp\setup.cmd
  exit /b 1
)

set "YARN_ENABLE_GLOBAL_CACHE=true"
set "YARN_NM_MODE=hardlinks-global"
if defined LOCALAPPDATA set "YARN_GLOBAL_FOLDER=%LOCALAPPDATA%\Yarn\Berry"

corepack yarn install --immutable
if errorlevel 1 exit /b 1

node osp\build.mjs
if errorlevel 1 exit /b 1

endlocal
