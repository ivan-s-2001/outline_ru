@echo off
setlocal EnableExtensions
call "%~dp0_common.bat"
if errorlevel 1 goto :error

if not exist "docker.env.example" (
    echo ОШИБКА: не найден docker.env.example.
    goto :error
)

if exist "%OUTLINE_ENV%" (
    choice /C YN /N /M "Файл docker.env уже существует. Пересоздать его? [Y/N]: "
    if errorlevel 2 (
        echo Изменения отменены.
        exit /b 0
    )
)

copy /Y "docker.env.example" "%OUTLINE_ENV%" >nul
if errorlevel 1 goto :error

for /f "usebackq delims=" %%S in (`powershell -NoProfile -Command "$s=[guid]::NewGuid().ToString('N')+[guid]::NewGuid().ToString('N'); Write-Output $s"`) do set "SECRET_KEY=%%S"
for /f "usebackq delims=" %%S in (`powershell -NoProfile -Command "$s=[guid]::NewGuid().ToString('N')+[guid]::NewGuid().ToString('N'); Write-Output $s"`) do set "UTILS_SECRET=%%S"

if not defined SECRET_KEY goto :secret_error
if not defined UTILS_SECRET goto :secret_error

powershell -NoProfile -Command "$p='%OUTLINE_ENV%'; $c=[IO.File]::ReadAllText($p); $c=$c.Replace('__SECRET_KEY__',$env:SECRET_KEY).Replace('__UTILS_SECRET__',$env:UTILS_SECRET); [IO.File]::WriteAllText($p,$c,(New-Object System.Text.UTF8Encoding($false)))"
if errorlevel 1 goto :secret_error

if not exist "backups" mkdir "backups"

echo.
echo Файл docker.env создан, секретные ключи сгенерированы.
echo Перед первым запуском откройте docker.env и настройте OIDC или SMTP.
echo Затем запустите docker-start.bat либо docker.bat.
echo.
exit /b 0

:secret_error
echo ОШИБКА: не удалось сгенерировать секретные ключи через PowerShell.
goto :error

:error
echo Инициализация не завершена.
pause
exit /b 1
