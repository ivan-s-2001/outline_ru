<?php

declare(strict_types=1);

use app\models\User;
use app\models\VacationAllowance;
use app\models\VacationRequest;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ActiveDataProvider $provider */
/** @var int $year */
/** @var User $target */
/** @var array $users */
/** @var VacationAllowance $allowance */
/** @var float $usedDays */
/** @var float $remainingDays */
/** @var bool $canManage */
$this->title = 'Отпуска';
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Отпуска</h1>
        <div class="text-body-secondary"><?= Html::encode($target->getFullName()) ?> · <?= $year ?></div>
    </div>
    <?php if ($canManage): ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-primary" href="<?= Url::to(['/vacation/create', 'userId' => $target->id]) ?>">Добавить отпуск</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/allowance', 'id' => $allowance->id]) ?>">Изменить баланс</a>
            <?= Html::beginForm(['/vacation/sync-calendar', 'year' => $year], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('Обновить календарь', ['class' => 'btn btn-outline-secondary']) ?>
            <?= Html::endForm() ?>
        </div>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary">Доступно</div><div class="display-6 fw-semibold"><?= number_format($allowance->totalDays(), 1, ',', ' ') ?></div><div class="small text-body-secondary">дней с переносом</div></div></div></div>
    <div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary">Использовано</div><div class="display-6 fw-semibold"><?= number_format($usedDays, 1, ',', ' ') ?></div><div class="small text-body-secondary">подтверждённых дней</div></div></div></div>
    <div class="col-12 col-md-4"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="small text-body-secondary">Остаток</div><div class="display-6 fw-semibold <?= $remainingDays < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($remainingDays, 1, ',', ' ') ?></div><div class="small text-body-secondary">рабочих дней</div></div></div></div>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="btn-group">
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/index', 'year' => $year - 1, 'userId' => $target->id]) ?>">← <?= $year - 1 ?></a>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/index', 'year' => (int)gmdate('Y'), 'userId' => $target->id]) ?>">Текущий год</a>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/index', 'year' => $year + 1, 'userId' => $target->id]) ?>"><?= $year + 1 ?> →</a>
    </div>
    <?php if ($canManage && $users): ?>
        <form method="get" action="<?= Url::to(['/vacation/index']) ?>" class="d-flex gap-2">
            <input type="hidden" name="year" value="<?= $year ?>">
            <?= Html::dropDownList('userId', $target->id, $users, ['class' => 'form-select', 'aria-label' => 'Сотрудник']) ?>
            <button class="btn btn-outline-primary" type="submit">Показать</button>
        </form>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <?= GridView::widget([
        'dataProvider' => $provider,
        'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => 'Отпусков за выбранный год нет.',
        'columns' => [
            [
                'label' => 'Период',
                'value' => static fn (VacationRequest $model): string => $model->date_from . ' — ' . $model->date_to,
            ],
            [
                'attribute' => 'working_days',
                'label' => 'Рабочих дней',
                'value' => static fn (VacationRequest $model): string => number_format((float)$model->working_days, 1, ',', ' '),
            ],
            [
                'attribute' => 'status',
                'label' => 'Статус',
                'format' => 'raw',
                'value' => static function (VacationRequest $model): string {
                    $labels = ['pending' => 'Ожидает', 'approved' => 'Подтверждён', 'rejected' => 'Отклонён', 'cancelled' => 'Отменён'];
                    $classes = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                    return Html::tag('span', $labels[$model->status] ?? $model->status, ['class' => 'badge text-bg-' . ($classes[$model->status] ?? 'secondary')]);
                },
            ],
            [
                'label' => 'Календарь',
                'value' => static fn (VacationRequest $model): string => $model->calendar_source . ($model->calendar_version ? ' · ' . $model->calendar_version : ''),
            ],
            'comment:ntext',
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => $canManage ? '{update} {cancel}' : '',
                'buttons' => [
                    'update' => static fn (string $url, VacationRequest $model): string => Html::a('Изменить', ['/vacation/create', 'id' => $model->id], ['class' => 'btn btn-outline-primary btn-sm']),
                    'cancel' => static fn (string $url, VacationRequest $model): string => $model->status === 'cancelled' ? '' : Html::beginForm(['/vacation/cancel', 'id' => $model->id], 'post', ['class' => 'd-inline']) . Html::submitButton('Отменить', ['class' => 'btn btn-outline-danger btn-sm', 'data-confirm' => 'Отменить отпуск?']) . Html::endForm(),
                ],
            ],
        ],
    ]) ?>
</div>
