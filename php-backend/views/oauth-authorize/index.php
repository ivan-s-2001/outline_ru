<?php

declare(strict_types=1);

use app\models\OAuthApp;
use yii\helpers\Html;

/** @var OAuthApp $app */
/** @var string[] $scopes */
/** @var array $params */
$this->title = 'Разрешение доступа';
$scopeLabels = [
    'read' => 'Читать доступные вам документы и данные профиля',
    'write' => 'Изменять доступные вам документы',
    'create' => 'Создавать документы от вашего имени',
];
?>
<div class="auth-card card border-0 shadow-lg"><div class="card-body p-4 p-md-5">
    <div class="text-center mb-4"><span class="brand-mark">O</span><h1 class="h4 mt-3 mb-1"><?= Html::encode($app->name) ?></h1><p class="text-body-secondary mb-0">запрашивает доступ к вашему рабочему пространству</p></div>
    <div class="list-group mb-4">
        <?php foreach ($scopes as $scope): ?><div class="list-group-item"><div class="fw-semibold"><?= Html::encode($scope) ?></div><div class="small text-body-secondary"><?= Html::encode($scopeLabels[$scope] ?? 'Дополнительное разрешение') ?></div></div><?php endforeach; ?>
    </div>
    <div class="alert alert-secondary small">Приложение получит только те права, которые уже есть у вашей учётной записи. Разрешение можно отозвать удалением приложения или его токенов.</div>
    <?= Html::beginForm(['/oauth-authorize/index'], 'post') ?>
    <?php foreach ($params as $name => $value): ?><?= Html::hiddenInput($name === 'clientId' ? 'client_id' : ($name === 'redirectUri' ? 'redirect_uri' : ($name === 'responseType' ? 'response_type' : ($name === 'scopeText' ? 'scope' : ($name === 'codeChallenge' ? 'code_challenge' : ($name === 'codeChallengeMethod' ? 'code_challenge_method' : $name))))), $value) ?><?php endforeach; ?>
    <div class="d-grid gap-2"><?= Html::submitButton('Разрешить', ['name' => 'decision', 'value' => 'allow', 'class' => 'btn btn-primary btn-lg']) ?><?= Html::submitButton('Отклонить', ['name' => 'decision', 'value' => 'deny', 'class' => 'btn btn-outline-secondary']) ?></div>
    <?= Html::endForm() ?>
</div></div>
