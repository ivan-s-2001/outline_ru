# Outline RU для Open Server Panel

Рабочая ветка: `agent/osp-mariadb`.

## Цель

- Node.js 22 устанавливается и выбирается через NVM Open Server Panel.
- Никакие npm/yarn-пакеты не устанавливаются глобально.
- Yarn запускается через встроенный Corepack.
- Пакеты физически хранятся один раз в глобальном Yarn-кэше.
- В проект они подключаются hardlink-ссылками.
- После production-сборки из `node_modules` удаляются dev-зависимости.
- MariaDB, Redis и Mailpit запускаются модулями Open Server Panel.

## Подготовка Open Server Panel

В меню проекта выберите:

- Node.js 22;
- Nginx;
- MariaDB;
- Redis;
- Mailpit.

После изменения `.osp/project.ini` или конфигурации модулей перезапустите Open Server Panel.

Работайте через `cmd.exe` в окружении проекта:

```bat
osp project outline.local
```

## Первый запуск

```bat
git checkout agent/osp-mariadb
git pull
osp\setup.cmd
osp\db-init.cmd
osp\build.cmd
osp\start.cmd
```

При пароле root для MariaDB:

```bat
osp\db-init.cmd -p
```

Локальный адрес:

```text
http://outline.local
```

## Очистка

Удалить результаты сборки и временное состояние Yarn:

```bat
osp\clean.cmd
```

Удалить также локальный `node_modules`:

```bat
osp\clean.cmd all
```

Глобальный Yarn-кэш намеренно не удаляется. По умолчанию он находится вне проекта:

```text
%LOCALAPPDATA%\Yarn\Berry
```

## Текущее состояние MariaDB-порта

Конфигурация OSPanel и база создаются автоматически. Сам сервер Outline изначально использует PostgreSQL-специфичные возможности: JSONB, массивы, полнотекстовый индекс, `ILIKE`, Postgres-драйвер и SQL-миграции. Порт на MariaDB выполняется отдельными совместимыми слоями; до завершения моделей, миграций и поиска эта ветка не считается готовой для рабочих данных.
