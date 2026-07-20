<?php

declare(strict_types=1);

use app\models\Absence;
use app\models\Shift;
use app\models\User;
use app\models\WorkTimeAdjustment;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var string $view */
/** @var DateTimeImmutable $anchor */
/** @var DateTimeImmutable $start */
/** @var DateTimeImmutable $end */
/** @var string[] $dates */
/** @var User[] $users */
/** @var array $cells */
/** @var bool $canManage */
$this->title = 'График';
$months = [1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
$weekdays = [1 => 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];
$previous = $view === 'month' ? $anchor->modify('-1 month') : $anchor->modify('-7 days');
$next = $view === 'month' ? $anchor->modify('+1 month') : $anchor->modify('+7 days');
$periodTitle = $view === 'month'
    ? ucfirst($months[(int)$start->format('n')]) . ' ' . $start->format('Y')
    : $start->format('d') . ' ' . $months[(int)$start->format('n')] . ' — ' . $end->format('d') . ' ' . $months[(int)$end->format('n')] . ' ' . $end->format('Y');
$formatAdjustment = static function (int $minutes): string {
    $sign = $minutes >= 0 ? '+' : '−';
    $value = abs($minutes);
    $hours = intdiv($value, 60);
    $rest = $value % 60;
    return $sign . ($hours ? $hours . ' ч' : '') . ($hours && $rest ? ' ' : '') . ($rest ? $rest . ' мин' : '');
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <div>
        <h1 class="h3 mb-1">График</h1>
        <div class="text-body-secondary"><?= Html::encode($periodTitle) ?></div>
    </div>
    <?php if ($canManage): ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="<?= Url::to(['/schedule/shift']) ?>">Добавить смену</a>
            <a class="btn btn-outline-warning" href="<?= Url::to(['/schedule/absence']) ?>">Добавить отсутствие</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/adjustment']) ?>">Корректировка времени</a>
        </div>
    <?php endif; ?>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="btn-group" role="group" aria-label="Режим графика">
        <a class="btn btn-<?= $view === 'week' ? 'primary' : 'outline-primary' ?>" href="<?= Url::to(['/schedule/index', 'view' => 'week', 'date' => $anchor->format('Y-m-d')]) ?>">Неделя</a>
        <a class="btn btn-<?= $view === 'month' ? 'primary' : 'outline-primary' ?>" href="<?= Url::to(['/schedule/index', 'view' => 'month', 'date' => $anchor->format('Y-m-d')]) ?>">Месяц</a>
    </div>
    <div class="btn-group" role="group" aria-label="Навигация по датам">
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/index', 'view' => $view, 'date' => $previous->format('Y-m-d')]) ?>">←</a>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/index', 'view' => $view, 'date' => gmdate('Y-m-d')]) ?>">Сегодня</a>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/index', 'view' => $view, 'date' => $next->format('Y-m-d')]) ?>">→</a>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0 schedule-table <?= $view === 'month' ? 'schedule-month' : 'schedule-week' ?>">
            <thead class="table-light sticky-top">
            <tr>
                <th class="position-sticky start-0 bg-body-tertiary" style="min-width:220px;z-index:3">Сотрудник</th>
                <?php foreach ($dates as $date): $day = new DateTimeImmutable($date); ?>
                    <th class="text-center <?= $date === gmdate('Y-m-d') ? 'table-primary' : '' ?>" style="min-width:<?= $view === 'month' ? '64' : '180' ?>px">
                        <div><?= Html::encode($weekdays[(int)$day->format('N')]) ?></div>
                        <div class="small fw-normal"><?= $day->format('d.m') ?></div>
                    </th>
                <?php endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <th class="position-sticky start-0 bg-body" style="z-index:2">
                        <div class="fw-semibold text-nowrap"><?= Html::encode($user->getFullName()) ?></div>
                        <div class="small text-body-secondary">@<?= Html::encode((string)$user->login) ?></div>
                    </th>
                    <?php foreach ($dates as $date):
                        $cell = $cells[$user->id][$date] ?? ['shifts' => [], 'absences' => [], 'adjustments' => []];
                    ?>
                        <td class="p-1 align-top <?= $date === gmdate('Y-m-d') ? 'table-primary' : '' ?>">
                            <?php foreach ($cell['absences'] as $absence): /** @var Absence $absence */ ?>
                                <div class="badge d-block text-wrap mb-1" style="background:<?= Html::encode((string)$absence->absenceType?->color) ?>">
                                    <?= Html::encode((string)$absence->absenceType?->name) ?>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($cell['shifts'] as $shift): /** @var Shift $shift */ ?>
                                <?php if ($view === 'month'): ?>
                                    <a class="badge d-block text-decoration-none mb-1" style="background:<?= Html::encode((string)($shift->shiftType?->color ?: '#4E5C6E')) ?>" href="<?= $canManage ? Url::to(['/schedule/shift', 'id' => $shift->id]) : '#' ?>" title="<?= Html::encode(substr((string)$shift->start_time, 0, 5) . '–' . substr((string)$shift->end_time, 0, 5)) ?>">
                                        <?= Html::encode((string)($shift->shiftType?->code ?: 'С')) ?>
                                    </a>
                                <?php else: ?>
                                    <div class="border rounded p-2 mb-1 bg-body">
                                        <div class="d-flex justify-content-between gap-2">
                                            <span class="fw-semibold"><?= Html::encode((string)($shift->shiftType?->name ?: 'Смена')) ?></span>
                                            <?php if ($canManage): ?><a class="small" href="<?= Url::to(['/schedule/shift', 'id' => $shift->id]) ?>">Изменить</a><?php endif; ?>
                                        </div>
                                        <div><?= Html::encode(substr((string)$shift->start_time, 0, 5) . '–' . substr((string)$shift->end_time, 0, 5)) ?></div>
                                        <?php if ($shift->break_minutes): ?><div class="small text-body-secondary">Перерыв <?= (int)$shift->break_minutes ?> мин</div><?php endif; ?>
                                        <?php if ($shift->comment): ?><div class="small mt-1 text-break"><?= Html::encode((string)$shift->comment) ?></div><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php foreach ($cell['adjustments'] as $adjustment): /** @var WorkTimeAdjustment $adjustment */ ?>
                                <span class="badge <?= (int)$adjustment->minutes >= 0 ? 'text-bg-success' : 'text-bg-danger' ?> me-1" title="<?= Html::encode((string)$adjustment->comment) ?>">
                                    <?= Html::encode($formatAdjustment((int)$adjustment->minutes)) ?>
                                </span>
                            <?php endforeach; ?>

                            <?php if ($canManage && $view === 'week'): ?>
                                <a class="btn btn-link btn-sm p-0 mt-1" href="<?= Url::to(['/schedule/shift', 'date' => $date, 'userId' => $user->id]) ?>">+ смена</a>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="<?= count($dates) + 1 ?>" class="p-5 text-center text-body-secondary">Активных сотрудников нет.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex flex-wrap gap-3 small text-body-secondary mt-3">
    <span><span class="badge text-bg-success">+2 ч</span> переработка</span>
    <span><span class="badge text-bg-danger">−2 ч</span> недоработка</span>
    <span>Цветные метки — смены и отсутствия</span>
</div>
