# Outline RU в Docker на Windows

Репозиторий содержит отдельную русскую Docker-сборку Outline 1.9.1 и набор `.bat`-файлов для Docker Desktop.

## Что входит

- русский язык `ru_RU` подключается к i18next и `date-fns`;
- русский язык используется по умолчанию;
- перевод закреплён на совместимой версии `flameshikari/outline-ru` для Outline 1.9.1;
- PostgreSQL, Redis и локальное хранилище вложений запускаются через Docker Compose;
- данные хранятся в Docker volumes и не удаляются при обычной остановке;
- доступны резервное копирование и восстановление PostgreSQL.

## Требования

- Windows 10/11;
- Docker Desktop с Docker Compose V2;
- не менее 8 ГБ оперативной памяти для сборки; желательно 12–16 ГБ;
- доступ к GitHub и реестрам Docker во время первой сборки.

## Первый запуск

1. Запустите `docker.bat` из корня репозитория.
2. Выберите **1. Инициализация docker.env**.
3. Откройте созданный `docker.env` и настройте хотя бы один способ авторизации: OIDC либо SMTP.
4. Снова запустите `docker.bat` и выберите **2. Собрать и запустить**.
5. Откройте `http://localhost:3000`.

Первая сборка компилирует Outline из исходников и может потребовать значительный объём памяти и места на диске.

## Авторизация

Outline не предоставляет локальную форму с паролем без настроенного провайдера. Для рабочего входа настройте один из вариантов в `docker.env`:

- OIDC: Authentik, Keycloak, Authelia или другой совместимый провайдер;
- SMTP: вход по ссылке из письма.

При использовании SSO первый зарегистрировавшийся пользователь рабочего пространства получает права администратора. Не используйте старый production-подход через `build/server/scripts/seed.js`: разработчики Outline указывают, что этот скрипт предназначен для разработки.

## Bat-файлы

| Файл | Назначение |
| --- | --- |
| `docker.bat` | главное интерактивное меню |
| `docker/windows/docker-init.bat` | создаёт `docker.env` и генерирует секреты |
| `docker/windows/docker-start.bat` | собирает и запускает контейнеры |
| `docker/windows/docker-stop.bat` | останавливает контейнеры без удаления данных |
| `docker/windows/docker-restart.bat` | пересоздаёт контейнер Outline |
| `docker/windows/docker-status.bat` | показывает контейнеры и проверяет `/_health` |
| `docker/windows/docker-logs.bat` | показывает текущие логи Outline |
| `docker/windows/docker-backup.bat` | сохраняет SQL-дамп в `backups` |
| `docker/windows/docker-restore.bat` | полностью заменяет БД выбранным SQL-дампом |
| `docker/windows/docker-update.bat` | создаёт дамп, делает `git pull`, пересобирает образ |
| `docker/windows/docker-reset.bat` | удаляет контейнеры и все volumes после подтверждения |

## Остановка и сохранность данных

Обычная остановка через `docker-stop.bat` выполняет `docker compose down`, но не удаляет volumes. Сохраняются:

- база PostgreSQL;
- данные Redis;
- загруженные файлы Outline.

Только `docker-reset.bat` удаляет volumes и все данные.

## Резервное копирование

`docker-backup.bat` создаёт файл:

```text
backups/outline_ГГГГ-ММ-ДД_ЧЧ-ММ-СС.sql
```

Вложения находятся в отдельном Docker volume `outline-data`. SQL-дамп содержит БД, но не содержимое этого volume. Для полной серверной резервной копии дополнительно архивируйте volume с вложениями или используйте внешнее S3-совместимое хранилище.

## Публичный сервер и HTTPS

Настройки из `docker.env.example` рассчитаны на локальный адрес `http://localhost:3000`. Для сервера:

1. замените `URL` на публичный HTTPS-адрес;
2. установите `FORCE_HTTPS=true`;
3. разместите Outline за Nginx, Caddy, Traefik или другим обратным прокси;
4. не публикуйте порты PostgreSQL и Redis наружу;
5. смените пароль PostgreSQL в `docker-compose.ru.yml` и `DATABASE_URL` перед публичным развёртыванием.

## Обновление перевода

Скрипт `scripts/sync-russian-translation.mjs` загружает перевод по закреплённой ссылке. Это защищает сборку от неожиданного изменения перевода, но при переходе Outline на новую версию ссылку следует обновить на совместимый коммит перевода.

Для временной проверки другого файла перевода можно передать переменную Compose:

```powershell
$env:RUSSIAN_TRANSLATION_URL="https://example.org/ru.json"
docker compose -f docker-compose.ru.yml build outline
```

Скрипт проверяет JSON и прерывает сборку, если файл перевода недоступен или выглядит неполным.

## Диагностика

```bat
docker\windows\docker-status.bat
docker\windows\docker-logs.bat
```

Полные логи всех сервисов:

```bat
docker compose -f docker-compose.ru.yml logs --tail=300
```
