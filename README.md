<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="./public/logos/outline-logo-dark.png" height="29">
    <source media="(prefers-color-scheme: light)" srcset="./public/logos/outline-logo-light.png" height="29">
    <img src="./public/logos/outline-logo-light.png" height="29" alt="Outline" />
  </picture>
</p>

<h1 align="center">Outline RU</h1>

<p align="center">
  Исходный код Outline 1.9.1 с русской Docker-сборкой и bat-файлами для Windows.
</p>

## Возможности этой версии

- русский язык `ru_RU` подключается к интерфейсу и форматированию дат;
- русский язык используется по умолчанию;
- перевод синхронизируется из совместимой русской локализации Outline 1.9.1;
- готовый Docker Compose запускает Outline, PostgreSQL и Redis;
- управление на Windows выполняется через `.bat`-файлы;
- предусмотрены резервное копирование, восстановление, обновление и полный сброс;
- предусмотрены отдельные реестры для разработки собственных блоков редактора.

## Быстрый запуск на Windows

Требуется установленный и запущенный Docker Desktop.

1. Запустите `docker.bat`.
2. Выберите **1. Инициализация docker.env**.
3. Настройте OIDC или SMTP в созданном `docker.env`.
4. Выберите **2. Собрать и запустить**.
5. Откройте `http://localhost:3000`.

Подробная инструкция: [`docs/DOCKER_RU.md`](docs/DOCKER_RU.md).

## Основные команды

```bat
:: Главное меню
docker.bat

:: Прямой запуск
docker\windows\docker-start.bat

:: Логи
docker\windows\docker-logs.bat

:: Резервная копия
docker\windows\docker-backup.bat

:: Безопасное обновление
docker\windows\docker-update.bat
```

## Как устроена русификация

Во время сборки `scripts/sync-russian-translation.mjs`:

1. добавляет `ru_RU` в список языков Outline;
2. подключает русскую локаль `date-fns`;
3. устанавливает русский язык по умолчанию;
4. загружает закреплённый файл перевода;
5. проверяет JSON и минимальное количество строк;
6. запускает обычную сборку Outline.

Перевод взят из проекта [`flameshikari/outline-ru`](https://github.com/flameshikari/outline-ru) и закреплён на совместимом коммите для версии 1.9.1.

## Разработка собственных блоков

Подробные правила архитектуры, именования, оформления, сериализации, регистрации и тестирования:

[`docs/CUSTOM_BLOCKS_RU.md`](docs/CUSTOM_BLOCKS_RU.md)

Точки расширения форка:

```text
shared/editor/nodes/custom/index.ts   — классы пользовательских узлов
app/editor/menus/custom.tsx           — пункты пользовательских блоков в меню `/`
```

Новый блок является частью исходного кода Outline и требует пересборки приложения или Docker-образа.

## Разработка без Docker

Для подготовки исходников к локальной русской сборке выполните:

```shell
node scripts/sync-russian-translation.mjs
yarn install
yarn build
```

Скрипт идемпотентен: повторный запуск не добавляет дублирующиеся настройки локали.

## Исходный проект

Этот репозиторий основан на [outline/outline](https://github.com/outline/outline) — совместной базе знаний на React, TypeScript и Node.js.

Документация Outline:

- [самостоятельное размещение](https://docs.getoutline.com/s/hosting/);
- [архитектура](docs/ARCHITECTURE.md);
- [локальная разработка](https://docs.getoutline.com/s/hosting/doc/local-development-5hEhFRXow7).

## Лицензия

Outline распространяется по лицензии [Business Source License 1.1](LICENSE). Изменения этого репозитория не меняют условия исходной лицензии.
