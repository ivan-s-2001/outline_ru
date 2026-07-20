<?php

declare(strict_types=1);

use app\assets\AppAsset;
use app\models\Notification;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
/** @var User|null $currentUser */
$currentUser = Yii::$app->user->identity instanceof User ? Yii::$app->user->identity : null;
$route = Yii::$app->controller->route;
$unreadNotifications = $currentUser
    ? (int)Notification::find()->where([
        'workspace_id' => $currentUser->workspace_id,
        'user_id' => $currentUser->id,
        'read_at' => null,
        'archived_at' => null,
    ])->count()
    : 0;
$notificationLabel = 'Уведомления' . ($unreadNotifications > 0 ? ' (' . $unreadNotifications . ')' : '');
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Html::encode(Yii::$app->language) ?>" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title ? $this->title . ' — ' . Yii::$app->name : Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body class="app-page">
<?php $this->beginBody() ?>
<div class="d-flex min-vh-100">
    <aside class="app-sidebar border-end bg-body-tertiary d-none d-lg-flex flex-column">
        <div class="p-3 border-bottom">
            <a class="d-flex align-items-center gap-2 text-decoration-none text-body fw-semibold" href="<?= Url::to(['/site/dashboard']) ?>">
                <span class="brand-mark brand-mark-sm">O</span>
                <span><?= Html::encode(Yii::$app->name) ?></span>
            </a>
        </div>
        <nav class="nav nav-pills flex-column gap-1 p-3 flex-grow-1 overflow-y-auto">
            <a class="nav-link <?= str_starts_with($route, 'site/dashboard') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/site/dashboard']) ?>">Главная</a>
            <a class="nav-link <?= str_starts_with($route, 'collection/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/collection/index']) ?>">Коллекции</a>
            <a class="nav-link <?= str_starts_with($route, 'document/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/document/create']) ?>">Новый документ</a>
            <a class="nav-link <?= $route === 'library/favorites' ? 'active' : 'text-body' ?>" href="<?= Url::to(['/library/favorites']) ?>">Избранное</a>
            <a class="nav-link <?= $route === 'library/archive' ? 'active' : 'text-body' ?>" href="<?= Url::to(['/library/archive']) ?>">Архив</a>
            <a class="nav-link <?= $route === 'library/trash' ? 'active' : 'text-body' ?>" href="<?= Url::to(['/library/trash']) ?>">Корзина</a>
            <a class="nav-link <?= str_starts_with($route, 'notification/') ? 'active' : 'text-body' ?> d-flex justify-content-between align-items-center" href="<?= Url::to(['/notification/index']) ?>">
                <span>Уведомления</span>
                <?php if ($unreadNotifications > 0): ?><span class="badge text-bg-primary rounded-pill"><?= $unreadNotifications ?></span><?php endif; ?>
            </a>
            <a class="nav-link <?= str_starts_with($route, 'import/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/import/document']) ?>">Импорт</a>
            <a class="nav-link <?= str_starts_with($route, 'template/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/template/index']) ?>">Шаблоны</a>
            <a class="nav-link <?= str_starts_with($route, 'search/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/search/index']) ?>">Поиск</a>
            <a class="nav-link <?= str_starts_with($route, 'api-key/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/api-key/index']) ?>">API-ключи</a>

            <div class="small text-uppercase text-body-secondary fw-semibold mt-4 mb-1 px-2">Персонал</div>
            <a class="nav-link <?= str_starts_with($route, 'schedule/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/schedule/index', 'view' => 'week']) ?>">График</a>
            <a class="nav-link <?= str_starts_with($route, 'vacation/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/vacation/index']) ?>">Отпуска</a>
            <a class="nav-link <?= str_starts_with($route, 'duty/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/duty/index']) ?>">Дежурства</a>

            <?php if ($currentUser?->isAdmin()): ?>
                <div class="small text-uppercase text-body-secondary fw-semibold mt-4 mb-1 px-2">Администрирование</div>
                <a class="nav-link <?= str_starts_with($route, 'user/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/user/index']) ?>">Пользователи</a>
                <a class="nav-link <?= str_starts_with($route, 'group/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/group/index']) ?>">Группы</a>
                <a class="nav-link <?= str_starts_with($route, 'audit/') ? 'active' : 'text-body' ?>" href="<?= Url::to(['/audit/index']) ?>">Аудит</a>
            <?php endif; ?>
        </nav>
        <?php if ($currentUser): ?>
            <div class="p-3 border-top">
                <div class="small fw-semibold text-truncate"><?= Html::encode($currentUser->getFullName()) ?></div>
                <div class="small text-body-secondary text-truncate mb-2">@<?= Html::encode($currentUser->login) ?></div>
                <?= Html::beginForm(['/site/logout'], 'post') ?>
                <?= Html::submitButton('Выйти', ['class' => 'btn btn-outline-secondary btn-sm w-100']) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </aside>

    <div class="flex-grow-1 min-w-0">
        <header class="navbar navbar-expand-lg border-bottom bg-body sticky-top px-3">
            <button class="btn btn-outline-secondary d-lg-none me-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">Меню</button>
            <span class="navbar-brand mb-0 h1 fs-6 text-truncate"><?= Html::encode($this->title ?: Yii::$app->name) ?></span>
        </header>

        <main class="container-fluid app-content py-4 px-3 px-md-4">
            <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
                <?php if ($type === 'apiKeySecret') { continue; } ?>
                <div class="alert alert-<?= Html::encode($type === 'error' ? 'danger' : $type) ?> alert-dismissible fade show" role="alert">
                    <?= Html::encode((string)$message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
                </div>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
</div>

<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title h5" id="mobileSidebarLabel"><?= Html::encode(Yii::$app->name) ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Закрыть"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="nav nav-pills flex-column gap-1">
            <a class="nav-link" href="<?= Url::to(['/site/dashboard']) ?>">Главная</a>
            <a class="nav-link" href="<?= Url::to(['/collection/index']) ?>">Коллекции</a>
            <a class="nav-link" href="<?= Url::to(['/document/create']) ?>">Новый документ</a>
            <a class="nav-link" href="<?= Url::to(['/library/favorites']) ?>">Избранное</a>
            <a class="nav-link" href="<?= Url::to(['/library/archive']) ?>">Архив</a>
            <a class="nav-link" href="<?= Url::to(['/library/trash']) ?>">Корзина</a>
            <a class="nav-link" href="<?= Url::to(['/notification/index']) ?>"><?= Html::encode($notificationLabel) ?></a>
            <a class="nav-link" href="<?= Url::to(['/import/document']) ?>">Импорт</a>
            <a class="nav-link" href="<?= Url::to(['/template/index']) ?>">Шаблоны</a>
            <a class="nav-link" href="<?= Url::to(['/search/index']) ?>">Поиск</a>
            <a class="nav-link" href="<?= Url::to(['/api-key/index']) ?>">API-ключи</a>
            <hr>
            <a class="nav-link" href="<?= Url::to(['/schedule/index', 'view' => 'week']) ?>">График</a>
            <a class="nav-link" href="<?= Url::to(['/vacation/index']) ?>">Отпуска</a>
            <a class="nav-link" href="<?= Url::to(['/duty/index']) ?>">Дежурства</a>
            <?php if ($currentUser?->isAdmin()): ?>
                <hr>
                <a class="nav-link" href="<?= Url::to(['/user/index']) ?>">Пользователи</a>
                <a class="nav-link" href="<?= Url::to(['/group/index']) ?>">Группы</a>
                <a class="nav-link" href="<?= Url::to(['/audit/index']) ?>">Аудит</a>
            <?php endif; ?>
        </nav>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
