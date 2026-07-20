<?php

declare(strict_types=1);

use app\models\OAuthAccessToken;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;

/** @var ActiveDataProvider $provider */
?>
<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Токены</h2></div>
    <?= GridView::widget([
        'dataProvider' => $provider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => 'Токены ещё не выдавались.',
        'columns' => [
            ['label' => 'Пользователь', 'value' => static fn (OAuthAccessToken $token): string => $token->user?->getFullName() ?: 'Удалённый пользователь'],
            ['label' => 'Scopes', 'value' => static fn (OAuthAccessToken $token): string => implode(' ', $token->getScopes())],
            'expires_at',
            'last_used_at',
            ['label' => 'Статус', 'value' => static fn (OAuthAccessToken $token): string => $token->revoked_at ? 'Отозван' : (strtotime((string)$token->expires_at) <= time() ? 'Истёк' : 'Активен')],
        ],
    ]) ?>
</div>
