@echo off
setlocal EnableExtensions
chcp 65001 >nul
cd /d "%~dp0\.."

echo [Outline RU] Проверка Node.js...
where node >nul 2>&1
if errorlevel 1 (
  echo ОШИБКА: Node.js не найден. Выберите Node.js 22 через NVM в Open Server Panel.
  exit /b 1
)

for /f %%V in ('node -p "process.versions.node.split('.')[0]"') do set "NODE_MAJOR=%%V"
if not "%NODE_MAJOR%"=="22" (
  echo ОШИБКА: требуется Node.js 22 из Open Server Panel. Сейчас используется:
  node --version
  exit /b 1
)

where corepack >nul 2>&1
if errorlevel 1 (
  echo ОШИБКА: Corepack не найден в выбранном Node.js.
  exit /b 1
)

if not exist ".env" (
  copy /y ".env.osp.example" ".env" >nul
  node -e "const fs=require('fs'),c=require('crypto');let s=fs.readFileSync('.env','utf8');s=s.replace('__GENERATE_64_HEX__',c.randomBytes(32).toString('hex')).replace('__GENERATE_64_HEX__',c.randomBytes(32).toString('hex'));fs.writeFileSync('.env',s);"
  echo Создан .env и сгенерированы локальные секреты.
) else (
  echo Файл .env уже существует, оставляю без изменений.
)

set "YARN_ENABLE_GLOBAL_CACHE=true"
set "YARN_NM_MODE=hardlinks-global"
if defined LOCALAPPDATA set "YARN_GLOBAL_FOLDER=%LOCALAPPDATA%\Yarn\Berry"

echo [Outline RU] Установка зависимостей без глобальных npm-пакетов...
corepack yarn install --immutable
if errorlevel 1 exit /b 1

echo.
echo Готово. Пакеты хранятся в глобальном Yarn-кэше и подключаются hardlink-ссылками.
echo Следующие команды:
echo   osp\db-init.cmd
 echo   osp\build.cmd
 echo   osp\start.cmd
endlocal
