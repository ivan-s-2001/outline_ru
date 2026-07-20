<?php

declare(strict_types=1);

use app\models\Webhook;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ActiveDataProvider $provider */
$this->title = 'Webhooks';
?>
<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div><h1 class="h3 mb-1">Webhooks</h1><p class="text-body-secondary mb-0">Подписанные уведомления внешних систем о событиях рабочего пространства.</p></div>
    <a class="btn btn-primary" href="<?= Url::to(['/webhook/create']) ?>">Новый webhook</a>
</div>
<div class="card border-0 shadow-sm overflow-hidden">
    <?= GridView::widget([
        'dataProvider' => $provider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => 'Webhooks ещё не настроены.',
        'columns' => [
            'name',
            'url:url',
            [
                'label' => 'События',
                'value' => static fn (Webhook $model): string => implode(', ', $model->getEventsValue()),
            ],
            [
                'label' => 'Статус',
                'format' => 'raw',
                'value' => static fn (Webhook $model): string => Html::tag('span', $model->is_active ? 'Активен' : 'Отключён', ['class' => 'badge text-bg-' . ($model->is_active ? 'success' : 'secondary')]),
            ],
            [
                'format' => 'raw',
                'value' => static fn (Webhook $model): string => Html::a('Открыть', ['/webhook/view', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm']),
            ],
        ],
    ]) ?>
</div>
