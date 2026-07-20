<?php

declare(strict_types=1);

use app\models\Invitation;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ActiveDataProvider $provider */
$this->title = 'Приглашения';
?>
<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div><h1 class="h3 mb-1">Приглашения</h1><p class="text-body-secondary mb-0">Ссылки действуют семь дней и используются один раз.</p></div>
    <a class="btn btn-primary" href="<?= Url::to(['/invitation/create']) ?>">Пригласить пользователя</a>
</div>
<div class="card border-0 shadow-sm overflow-hidden">
<?= GridView::widget([
    'dataProvider' => $provider,
    'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
    'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
    'emptyText' => 'Приглашений пока нет.',
    'columns' => [
        'email:email',
        'role',
        ['label' => 'Пригласил', 'value' => static fn (Invitation $model): string => $model->inviter?->getFullName() ?: 'Удалённый пользователь'],
        'expires_at',
        [
            'label' => 'Статус',
            'value' => static fn (Invitation $model): string => $model->accepted_at ? 'Принято' : ($model->revoked_at ? 'Отозвано' : (strtotime((string)$model->expires_at) <= time() ? 'Истекло' : 'Ожидает')),
        ],
        [
            'format' => 'raw',
            'value' => static fn (Invitation $model): string => (!$model->accepted_at && !$model->revoked_at)
                ? Html::beginForm(['/invitation/revoke', 'id' => $model->id], 'post') . Html::submitButton('Отозвать', ['class' => 'btn btn-outline-danger btn-sm', 'data-confirm' => 'Отозвать приглашение?']) . Html::endForm()
                : '',
        ],
    ],
]) ?>
</div>
