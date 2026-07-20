# Полная функциональная совместимость с Outline

## Зафиксированная цель

Новая система должна повторять не только страницу документов, но и весь пользовательский функционал исходного Outline:

- рабочие пространства, пользователи, группы, роли и приглашения;
- коллекции, документы, шаблоны, архив и корзина;
- блочный ProseMirror-редактор;
- совместное редактирование Yjs/Hocuspocus;
- локальная офлайн-копия IndexedDB;
- курсоры, присутствие, наблюдение за участником и reconnect;
- комментарии к документу и выделенному тексту;
- упоминания пользователей и документов;
- вложения, изображения, видео и внешние embed;
- история версий и восстановление;
- полнотекстовый поиск;
- общий доступ, публичные ссылки и гостевой доступ;
- импорт, экспорт и API;
- уведомления и realtime-события;
- новый модуль графиков, отпусков и дежурств.

## Архитектура

### Основное приложение

- Yii 2 / PHP 8.2+
- MariaDB 10.6+
- Redis
- OSPanel/Nginx

Yii отвечает за пользователей, права, коллекции, документы, API, файлы, поиск, кадровые модули и административный интерфейс.

### Редактор

Редактор остаётся браузерным TypeScript/ProseMirror-приложением. В рабочую установку кладутся только готовые файлы `web/editor/*.js` и `web/editor/*.css`. Исходники и зависимости собираются в GitHub Actions, поэтому на рабочем компьютере нет `node_modules`.

Поддерживаемые узлы и marks:

- paragraph, heading, text, hard break;
- bold, italic, underline, strike, code, highlight, link;
- bullet/ordered/check lists;
- blockquote, notice/callout, horizontal rule, toggle;
- code block и code fence;
- table, row, header, cell;
- attachment, image, video, embed;
- emoji, mention, date/time;
- inline math и math block;
- template placeholder;
- inline comments.

### Collaboration

Отдельный сервис Hocuspocus:

- WebSocket endpoint `/collaboration`;
- токены выдаёт Yii;
- права read/update зашиты в короткоживущий JWT;
- state хранится в `documents.collaboration_state` как LONGBLOB;
- Redis синхронизирует несколько процессов и presence;
- MariaDB сохраняет Yjs state и производную JSON/text-проекцию;
- клиент дополнительно хранит Yjs state в IndexedDB.

Сервис собирается в CI в один `dist/collaboration.cjs`. В OSPanel нужен Node runtime, но установка npm-пакетов и `node_modules` не нужны.

## Контракты

### Токен collaboration

Claims:

- `sub` — user id;
- `workspace` — workspace id;
- `document` — document id;
- `canRead`;
- `canUpdate`;
- `name`;
- `color`;
- `exp` — не более 10 минут.

### Имя Yjs-документа

`document.<document-id>`

### Persisted document

- `content_json` — текущая ProseMirror JSON-проекция;
- `content_text` — текст для MariaDB FULLTEXT;
- `collaboration_state` — бинарный Yjs update;
- `version` — серверная версия;
- `updated_by` — последний пользователь;
- revisions создаются при завершении сессии и периодически во время длинной сессии.

## Этапы

1. Схема MariaDB, установка, пользователи и RBAC.
2. Коллекции, документы, revisions, комментарии, файлы.
3. Полный editor bundle со всеми узлами.
4. Hocuspocus auth/persistence и IndexedDB.
5. Realtime presence, notifications и document events.
6. Публичный доступ, shares, templates, imports/exports.
7. API parity.
8. График, отпуска, рабочее время и дежурства.
9. Мигратор данных из PostgreSQL Outline.

Нельзя считать систему совместимой с оригиналом, пока не пройдены интеграционные сценарии редактора, collaboration и прав доступа.
