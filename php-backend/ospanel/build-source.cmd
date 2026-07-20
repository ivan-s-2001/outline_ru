@echo off
setlocal
chcp 65001 >nul
cd /d "%~dp0..\.."

where composer >nul 2>nul || (echo Composer не найден в PATH.& exit /b 1)
where node >nul 2>nul || (echo Node.js 22 не найден в PATH.& exit /b 1)
where npm >nul 2>nul || (echo npm не найден в PATH.& exit /b 1)

echo [1/4] Установка PHP-зависимостей...
cd php-backend
call composer install --no-interaction --prefer-dist --optimize-autoloader
if errorlevel 1 exit /b %errorlevel%
cd ..

echo [2/4] Установка зависимостей оригинального редактора...
call corepack enable
if errorlevel 1 exit /b %errorlevel%
call yarn install --immutable
if errorlevel 1 exit /b %errorlevel%

echo [3/4] Сборка ProseMirror editor bundle...
call yarn vite build --config vite.yii-editor.config.ts
if errorlevel 1 exit /b %errorlevel%

echo [4/4] Сборка Hocuspocus collaboration runtime...
cd services\yii-collaboration
call npm install --ignore-scripts --no-audit --no-fund --package-lock=false
if errorlevel 1 exit /b %errorlevel%
call npm run build
if errorlevel 1 exit /b %errorlevel%
cd ..\..\php-backend

if not exist ".env" copy /Y ".env.example" ".env" >nul

echo.
echo Сборка завершена. Настройте php-backend\.env и запустите ospanel\migrate.cmd.
exit /b 0
