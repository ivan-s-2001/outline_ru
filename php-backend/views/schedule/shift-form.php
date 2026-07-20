<?php

declare(strict_types=1);

use app\models\Shift;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var Shift $model */
/** @var array $users */
/** @var array $types */
$this->title = $model->isNewRecord ? 'Новая смена' : 'Изменение смены';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <a class="btn btn-outline-secondary" href="<?= yii\helpers\Url::to(['/schedule/index', 'view' => 'week', 'date' => $model->work_date]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'user_id')->dropDownList($users, ['class' => 'form-select']) ?>
                <?= $form->field($model, 'shift_type_id')->dropDownList(['' => 'Без типа'] + $types, ['class' => 'form-select']) ?>
                <div class="row g-3">
                    <div class="col-md-4"><?= $form->field($model, 'work_date')->input('date') ?></div>
                    <div class="col-md-4"><?= $form->field($model, 'start_time')->input('time') ?></div>
                    <div class="col-md-4"><?= $form->field($model, 'end_time')->input('time') ?></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'break_minutes')->input('number', ['min' => 0, 'max' => 720]) ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'status')->dropDownList([
                        'planned' => 'Запланирована',
                        'confirmed' => 'Подтверждена',
                        'cancelled' => 'Отменена',
                    ], ['class' => 'form-select']) ?></div>
                </div>
                <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
                <div class="d-flex justify-content-end gap-2">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
        <?php if (!$model->isNewRecord): ?>
            <div class="mt-3">
                <?= Html::beginForm(['/schedule/delete-shift', 'id' => $model->id], 'post', ['data-confirm' => 'Удалить смену?']) ?>
                <?= Html::submitButton('Удалить смену', ['class' => 'btn btn-outline-danger']) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
