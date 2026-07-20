@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0\.."

if exist "build" rmdir /s /q "build"
if exist ".yarn\install-state.gz" del /q ".yarn\install-state.gz"
if exist ".yarn\unplugged" rmdir /s /q ".yarn\unplugged"

if /i "%~1"=="all" (
  if exist "node_modules" rmdir /s /q "node_modules"
  echo Удалены build и node_modules. Глобальный Yarn-кэш не затронут.
) else (
  echo Удалён build и временное состояние Yarn.
  echo Для удаления node_modules: osp\clean.cmd all
)

endlocal
