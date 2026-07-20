# Запуск Yii-версии Outline в OSPanel

## Требования

- OSPanel с PHP 8.2 или 8.3;
- MariaDB 10.6+;
- Redis — рекомендуется, но может быть отключён через `REDIS_ENABLED=0`;
- Node.js 22 — нужен для совместного редактирования;
- Composer — нужен только при сборке из исходников.

## Вариант 1: готовый artifact

Workflow `Yii OSPanel Package` создаёт архив `outline-yii-ospanel.zip`. В нём уже находятся:

- production Composer dependencies;
- собранный ProseMirror editor;
- собранный Hocuspocus collaboration runtime;
- Windows-команды для миграций и запуска collaboration.

Распакуйте архив, например, в:

```text
C:\OSPanel\home\outline.local\
```

Корнем сайта в OSPanel укажите:

```text
C:\OSPanel\home\outline.local\web
```

## Вариант 2: сборка из исходников

Из корня репозитория запустите:

```bat
php-backend\ospanel\build-source.cmd
```

Скрипт установит Composer dependencies, соберёт оригинальный редактор и collaboration runtime.

## База данных

Создайте пустую базу `outline` с кодировкой `utf8mb4`. Пример SQL:

```sql
CREATE DATABASE outline
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Скопируйте конфигурацию:

```bat
cd php-backend
copy .env.example .env
```

Заполните как минимум:

```dotenv
APP_URL=http://outline.local
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=outline
DB_USER=root
DB_PASSWORD=
COOKIE_VALIDATION_KEY=случайная-строка
JWT_SECRET=случайная-строка-не-короче-32-символов
```

Секрет можно получить командой:

```bat
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## Миграции

```bat
php-backend\ospanel\migrate.cmd
```

Команда создаёт таблицы, ограничения и FULLTEXT-индекс MariaDB.

## Совместное редактирование

В отдельном окне запустите:

```bat
php-backend\ospanel\start-collaboration.cmd
```

По умолчанию сервис слушает `127.0.0.1:3010`, а браузер подключается к:

```dotenv
COLLABORATION_URL=ws://127.0.0.1:3010/collaboration
```

Проверка состояния:

```text
http://127.0.0.1:3010/health
```

Node runtime сам читает `php-backend/.env`; отдельно задавать переменные среды не требуется.

Для доступа с других компьютеров задайте `COLLABORATION_HOST=0.0.0.0` и URL с адресом сервера. Для HTTPS используйте reverse proxy и `wss://.../collaboration`.

## Настройка web-сервера

### Apache

В `web/.htaccess` уже находится правило Yii pretty URL. Требуется включённый `mod_rewrite` и разрешённый `AllowOverride`.

### Nginx

Для корня сайта `php-backend/web` требуется правило:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

При reverse proxy WebSocket добавьте отдельный location:

```nginx
location /collaboration {
    proxy_pass http://127.0.0.1:3010;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
}
```

После этого установите:

```dotenv
COLLABORATION_URL=ws://outline.local/collaboration
```

Для HTTPS используйте `wss://`.

## Первый запуск

Откройте:

```text
http://outline.local/install
```

Введите название рабочего пространства, логин, email, ФИО администратора и пароль. После установки откроется Bootstrap-интерфейс документов.

## Каталоги с правом записи

Процесс PHP должен иметь право записи в:

```text
runtime\
web\assets\
runtime\storage\attachments\
```

## Диагностика

Проверка Yii:

```bat
cd php-backend
php yii help
php yii migrate/history
```

Проверка PHP-кода и тестов при наличии dev dependencies:

```bat
composer lint
composer test
```

Если редактор показывает `Локальный режим`, проверьте:

1. запущен ли `start-collaboration.cmd`;
2. открывается ли `/health` на порту 3010;
3. совпадает ли `JWT_SECRET` у PHP и Node — используется один `.env`;
4. доступен ли `COLLABORATION_URL` из браузера;
5. применены ли миграции MariaDB.
