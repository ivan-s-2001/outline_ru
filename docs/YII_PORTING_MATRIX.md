# Перенос Outline на Yii + Bootstrap + MariaDB

Этот файл является контрольной картой функционального паритета. Исходный React/Koa/PostgreSQL-код сохраняется в репозитории до тех пор, пока соответствующий модуль не получит статус `accepted`.

## Правило приёмки

Модуль считается перенесённым только при наличии:

1. перечня исходных файлов Outline;
2. Yii-моделей и миграций MariaDB;
3. сервиса прав доступа;
4. web/API-контроллеров;
5. Bootstrap 5 views;
6. автоматических тестов;
7. проверки поведения по исходному интерфейсу.

Команда `cd php-backend && php yii port/inventory` создаёт `runtime/port-inventory.json` со строками, SHA-256 и статусом каждого исходного файла из `app`, `server`, `shared` и `plugins`.

## Модули

| Модуль | Источник Outline | Цель Yii | Статус |
|---|---|---|---|
| Установка | `app/scenes/Login/components/WorkspaceSetup.tsx`, `server/routes/api/installation` | `SiteController`, `ApiController`, `InstallForm`, `AuthService`, Bootstrap install view | ported |
| Логин/сессия | `app/scenes/Login`, `server/routes/auth`, auth providers | `LoginForm`, Yii User/session, Redis, Bootstrap login view | ported |
| Пользователи/ФИО | `server/models/User.ts`, user routes, settings views | `User`, admin/profile controllers и views | in-progress |
| Рабочие пространства | `Team`, team routes/settings | `workspaces`, workspace services/controllers | in-progress |
| Группы | `Group`, `GroupUser`, group routes | `groups`, `group_users`, Bootstrap admin | in-progress |
| Коллекции | Collection model/routes/policies/scenes | `Collection`, policy, CRUD, Bootstrap views | in-progress |
| Документы | Document model/routes/commands/scenes | `Document`, document service, CRUD, hierarchy | in-progress |
| Редактор блоков | `shared/editor`, `app/editor` | оригинальная ProseMirror-схема и standalone bundle внутри Bootstrap document view | in-progress |
| Совместное редактирование | Hocuspocus/Yjs services/extensions | Yjs/Hocuspocus service + MariaDB state + Yii JWT ACL | in-progress |
| История версий | Revision model/routes/scenes | список, просмотр и восстановление ревизий | in-progress |
| Комментарии | Comment model/routes/scenes | comments API, Bootstrap thread, inline marks | in-progress |
| Упоминания | Mention nodes, notifications | mentions resolver + notifications | pending |
| Вложения | Attachment model/routes/storage | локальное хранилище, ACL upload/download, editor upload | in-progress |
| Права | policies, collection/document memberships | scoped ACL для пользователей и групп | in-progress |
| Публичные ссылки | Share model/routes/scenes | токены, публикация потомков, public read-only view | in-progress |
| Поиск | PostgreSQL search provider | MariaDB FULLTEXT provider + ACL | in-progress |
| Шаблоны | Template model/routes/scenes | templates module | pending |
| Импорт | import models/tasks/plugins | queue-based import module | pending |
| Экспорт | export tasks/routes | ZIP/Markdown/HTML/PDF exports | pending |
| Уведомления | notifications, emails, jobs | DB notifications + SMTP/Mailpit + jobs | pending |
| Realtime | Socket.IO events/presence | WebSocket event gateway | pending |
| API ключи | ApiKey model/routes/settings | token auth and admin views | pending |
| OAuth приложения | OAuth models/routes | Yii OAuth server module | pending |
| Webhooks | webhook plugin/tasks | webhook subscriptions/deliveries/jobs | pending |
| Интеграции | plugins and integrations | Yii modules/adapters | pending |
| Аналитика/аудит | Event and insights models | event log and reports | pending |
| График | custom workforce module | schedule module | pending |
| Отпуска | custom workforce module | vacation module | pending |
| Дежурства | custom workforce module | duty module | pending |

## Автоматическая проверка

На текущем этапе GitHub Actions отдельно проверяет:

- `Yii MariaDB`: Composer, PHP lint, миграции на чистой MariaDB и PHPUnit;
- `Yii Editor Bundle`: сборку оригинального ProseMirror-редактора для Yii;
- `Yii Collaboration Bundle`: сборку standalone Hocuspocus runtime;
- основной CI и CodeQL сохранённого исходного Outline.

Статус `accepted` ставится только после зелёных проверок и проверки пользовательского сценария в OSPanel.

## Запрещённые сокращения

- Нельзя заменять редактор обычным `textarea` и считать модуль завершённым.
- Нельзя считать API перенесённым только по совпадению имени endpoint.
- Нельзя оставлять доступ к записи без проверки workspace и ACL.
- Нельзя удалять исходный модуль Outline до статуса `accepted`.
- Нельзя использовать CDN: Bootstrap, редактор и остальные runtime assets должны работать локально.
