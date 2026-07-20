@echo off
setlocal EnableExtensions
chcp 65001 >nul

:menu
cls
echo ==========================================================
echo                 OUTLINE RU - DOCKER
echo ==========================================================
echo  1. Инициализация docker.env
echo  2. Собрать и запустить
echo  3. Остановить
echo  4. Перезапустить Outline
echo  5. Состояние и проверка HTTP
echo  6. Логи Outline
echo  7. Резервная копия базы
echo  8. Восстановить базу
echo  9. Обновить репозиторий и контейнеры
echo 10. Полный сброс с удалением данных
echo  0. Выход
echo ==========================================================
set "ACTION="
set /p "ACTION=Выберите действие: "

if "%ACTION%"=="1" call "%~dp0docker-init.bat"
if "%ACTION%"=="2" call "%~dp0docker-start.bat"
if "%ACTION%"=="3" call "%~dp0docker-stop.bat"
if "%ACTION%"=="4" call "%~dp0docker-restart.bat"
if "%ACTION%"=="5" call "%~dp0docker-status.bat"
if "%ACTION%"=="6" call "%~dp0docker-logs.bat"
if "%ACTION%"=="7" call "%~dp0docker-backup.bat"
if "%ACTION%"=="8" call "%~dp0docker-restore.bat"
if "%ACTION%"=="9" call "%~dp0docker-update.bat"
if "%ACTION%"=="10" call "%~dp0docker-reset.bat"
if "%ACTION%"=="0" exit /b 0

goto :menu
