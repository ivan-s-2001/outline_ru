<?php

declare(strict_types=1);

use app\models\OAuthApp;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var OAuthApp $model */
/** @var ActiveDataProvider $tokens */
$this->title = $model->name;
$oneTimeKey = Yii::$app->session->getFlash('oauthClientSecret');
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <a class="small text-decoration-none" href="<?= Url::to(['/oauth-app/index']) ?>">OAuth-приложения</a>
        <h1 class="h3 mt-1 mb-1"><?= Html::encode($model->name) ?></h1>
        <code><?= Html::encode((string)$model->client_id) ?></code>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?= Url::to(['/oauth-app/create', 'id' => $model->id]) ?>">Изменить</a>
        <?= Html::beginForm(['/oauth-app/revoke-tokens', 'id' => $model->id], 'post') ?>
        <?= Html::submitButton('Отозвать токены', ['class' => 'btn btn-outline-warning', 'data-confirm' => 'Отозвать все токены приложения?']) ?>
        <?= Html::endForm() ?>
    </div>
</div>

<?php if (is_string($oneTimeKey) && $oneTimeKey !== ''): ?>
    <div class="alert alert-warning">
        <div class="fw-semibold">Сохраните ключ клиента. Он показывается один раз.</div>
        <code class="user-select-all text-break"><?= Html::encode($oneTimeKey) ?></code>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <dl class="row mb-0">
                <dt class="col-sm-4">Client ID</dt><dd class="col-sm-8"><code><?= Html::encode((string)$model->client_id) ?></code></dd>
                <dt class="col-sm-4">Redirect URI</dt><dd class="col-sm-8"><?php foreach ($model->getRedirectUris() as $uri): ?><div class="text-break"><code><?= Html::encode($uri) ?></code></div><?php endforeach; ?></dd>
                <dt class="col-sm-4">Scopes</dt><dd class="col-sm-8"><?= Html::encode(implode(' ', $model->getScopes())) ?></dd>
                <dt class="col-sm-4">Тип</dt><dd class="col-sm-8"><?= $model->is_confidential ? 'Конфиденциальное' : 'Публичное, PKCE обязательно' ?></dd>
            </dl>
        </div></div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h6">Управление</h2>
            <p class="small text-body-secondary">Ротация ключа не отзывает уже выпущенные токены.</p>
            <div class="d-flex flex-wrap gap-2">
                <?php if ($model->is_confidential): ?>
                    <?= Html::beginForm(['/oauth-app/rotate', 'id' => $model->id], 'post') ?><?= Html::submitButton('Сменить ключ', ['class' => 'btn btn-outline-warning', 'data-confirm' => 'Сменить ключ клиента?']) ?><?= Html::endForm() ?>
                <?php endif; ?>
                <?= Html::beginForm(['/oauth-app/delete', 'id' => $model->id], 'post') ?><?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger', 'data-confirm' => 'Удалить приложение и все токены?']) ?><?= Html::endForm() ?>
            </div>
        </div></div>
    </div>
</div>

<?= $this->render('_tokens', ['provider' => $tokens]) ?>
