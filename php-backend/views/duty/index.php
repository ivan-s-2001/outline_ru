<?php

declare(strict_types=1);

use app\models\DutySetting;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;

/** @var ActiveDataProvider $provider */
/** @var bool $canManage */
$this->title = 'Дежурства';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div><h1 class="h3 mb-1">Дежурства</h1><p class="text-body-secondary mb-0">Пропорциональное распределение ответственности между участниками.</p></div>
    <?php if ($canManage): ?><a class="btn btn-primary" href="<?= yii\helpers\Url::to(['/duty/create']) ?>">Новый план</a><?php endif; ?>
</div>
<div class="card border-0 shadow-sm overflow-hidden">
    <?= GridView::widget([
        'dataProvider' => $provider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => 'Планов дежурств пока нет.',
        'columns' => [
            ['attribute' => 'name', 'label' => 'Название'],
            ['label' => 'Период', 'value' => static fn (DutySetting $m): string => $m->date_from . ' — ' . $m->date_to],
            ['label' => 'Время', 'value' => static fn (DutySetting $m): string => substr((string)$m->start_time, 0, 5) . '–' . substr((string)$m->end_time, 0, 5)],
            ['attribute' => 'slot_minutes', 'label' => 'Шаг, мин'],
            ['attribute' => 'status', 'label' => 'Статус'],
            [
                'format' => 'raw',
                'value' => static fn (DutySetting $m): string => Html::a('Открыть', ['/duty/view', 'id' => $m->id], ['class' => 'btn btn-outline-primary btn-sm']),
            ],
        ],
    ]) ?>
</div>
