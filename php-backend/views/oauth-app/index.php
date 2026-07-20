<?php

declare(strict_types=1);

use app\models\OAuthApp;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ActiveDataProvider $provider */
$this->title = 'OAuth-приложения';
?>
<div class="d-flex align-items-center justify-content-between gap-3 mb-4"><div><h1 class="h3 mb-1">OAuth-приложения</h1><p class="text-body-secondary mb-0">Authorization Code с PKCE и короткоживущими access token.</p></div><a class="btn btn-primary" href="<?= Url::to(['/oauth-app/create']) ?>">Новое приложение</a></div>
<div class="card border-0 shadow-sm overflow-hidden"><?= GridView::widget([
    'dataProvider' => $provider,
    'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
    'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
    'emptyText' => 'OAuth-приложения ещё не зарегистрированы.',
    'columns' => [
        'name',
        'client_id',
        ['label' => 'Scopes', 'value' => static fn (OAuthApp $m): string => implode(' ', $m->getScopes())],
        ['label' => 'Тип', 'value' => static fn (OAuthApp $m): string => $m->is_confidential ? 'Конфиденциальное' : 'Публичное + PKCE'],
        ['label' => 'Статус', 'format' => 'raw', 'value' => static fn (OAuthApp $m): string => Html::tag('span', $m->is_active ? 'Активно' : 'Отключено', ['class' => 'badge text-bg-' . ($m->is_active ? 'success' : 'secondary')])],
        ['format' => 'raw', 'value' => static fn (OAuthApp $m): string => Html::a('Открыть', ['/oauth-app/view', 'id' => $m->id], ['class' => 'btn btn-outline-primary btn-sm'])],
    ],
]) ?></div>
