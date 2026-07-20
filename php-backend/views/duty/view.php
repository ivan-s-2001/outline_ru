<?php

declare(strict_types=1);

use app\models\DutyAssignment;
use app\models\DutyParticipant;
use app\models\DutySetting;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var DutySetting $setting */
/** @var DutyParticipant[] $participants */
/** @var DutyAssignment[] $assignments */
/** @var array $users */
/** @var bool $canManage */
$this->title = $setting->name;
$assignmentOptions = [];
foreach ($assignments as $assignment) {
    $assignmentOptions[$assignment->id] = sprintf(
        '%s %s–%s · %s',
        $assignment->duty_date,
        substr((string)$assignment->start_time, 0, 5),
        substr((string)$assignment->end_time, 0, 5),
        $assignment->user?->getFullName() ?: 'Сотрудник'
    );
}
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <a class="small text-decoration-none" href="<?= Url::to(['/duty/index']) ?>">Дежурства</a>
        <h1 class="h3 mt-1 mb-1"><?= Html::encode($setting->name) ?></h1>
        <div class="text-body-secondary"><?= Html::encode($setting->date_from . ' — ' . $setting->date_to) ?> · <?= Html::encode(substr((string)$setting->start_time, 0, 5) . '–' . substr((string)$setting->end_time, 0, 5)) ?></div>
    </div>
    <?php if ($canManage): ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-primary" href="<?= Url::to(['/duty/create', 'id' => $setting->id]) ?>">Настройки</a>
            <?= Html::beginForm(['/duty/generate', 'id' => $setting->id], 'post', ['class' => 'd-inline', 'data-confirm' => 'Пересоздать все назначения?']) ?>
            <?= Html::submitButton('Распределить заново', ['class' => 'btn btn-primary']) ?>
            <?= Html::endForm() ?>
        </div>
    <?php endif; ?>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Участники</h2></div>
            <div class="list-group list-group-flush">
                <?php foreach ($participants as $participant): ?>
                    <div class="list-group-item px-4 py-3 d-flex align-items-center justify-content-between gap-3">
                        <div><div class="fw-semibold"><?= Html::encode($participant->user?->getFullName() ?: 'Сотрудник') ?></div><div class="small text-body-secondary">Коэффициент <?= number_format((float)$participant->coefficient, 3, ',', ' ') ?></div></div>
                        <?php if ($canManage): ?>
                            <?= Html::beginForm(['/duty/remove-participant', 'id' => $setting->id, 'participantId' => $participant->id], 'post') ?>
                            <?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger btn-sm', 'data-confirm' => 'Удалить участника?']) ?>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$participants): ?><div class="list-group-item px-4 py-4 text-body-secondary">Участники не добавлены.</div><?php endif; ?>
            </div>
            <?php if ($canManage): ?>
                <div class="card-footer bg-body border-0 p-4">
                    <?= Html::beginForm(['/duty/add-participant', 'id' => $setting->id], 'post', ['class' => 'vstack gap-2']) ?>
                    <?= Html::dropDownList('userId', '', ['' => 'Выберите сотрудника'] + $users, ['class' => 'form-select', 'required' => true]) ?>
                    <div class="input-group"><span class="input-group-text">Коэффициент</span><?= Html::input('number', 'coefficient', '1', ['class' => 'form-control', 'min' => 0.001, 'max' => 10, 'step' => 0.001]) ?></div>
                    <?= Html::submitButton('Добавить участника', ['class' => 'btn btn-outline-primary']) ?>
                    <?= Html::endForm() ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($canManage && count($assignments) >= 2): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Обмен назначениями</h2></div>
                <div class="card-body p-4">
                    <p class="small text-body-secondary">В один день меняются сотрудники. Между разными днями вместе с сотрудниками переносится доля распределения.</p>
                    <?= Html::beginForm(['/duty/swap', 'id' => $setting->id], 'post', ['class' => 'vstack gap-2']) ?>
                    <?= Html::dropDownList('fromAssignmentId', '', ['' => 'Первое назначение'] + $assignmentOptions, ['class' => 'form-select', 'required' => true]) ?>
                    <?= Html::dropDownList('toAssignmentId', '', ['' => 'Второе назначение'] + $assignmentOptions, ['class' => 'form-select', 'required' => true]) ?>
                    <?= Html::submitButton('Обменять', ['class' => 'btn btn-outline-primary']) ?>
                    <?= Html::endForm() ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>Дата</th><th>Время</th><th>Сотрудник</th><th>Доля</th><th>Статус</th></tr></thead>
                    <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= Html::encode((string)$assignment->duty_date) ?></td>
                            <td><?= Html::encode(substr((string)$assignment->start_time, 0, 5) . '–' . substr((string)$assignment->end_time, 0, 5)) ?></td>
                            <td><?= Html::encode($assignment->user?->getFullName() ?: 'Сотрудник') ?></td>
                            <td><?= number_format((float)$assignment->share_coefficient, 3, ',', ' ') ?></td>
                            <td><?= Html::encode((string)$assignment->status) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$assignments): ?><tr><td colspan="5" class="p-5 text-center text-body-secondary">Назначения ещё не распределены.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($canManage): ?>
            <div class="mt-3">
                <?= Html::beginForm(['/duty/delete', 'id' => $setting->id], 'post') ?>
                <?= Html::submitButton('Удалить план', ['class' => 'btn btn-outline-danger', 'data-confirm' => 'Удалить план и все назначения?']) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
