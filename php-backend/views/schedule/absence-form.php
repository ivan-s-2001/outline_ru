<?php

declare(strict_types=1);

use app\models\Absence;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Absence $model */
/** @var array $users */
/** @var array $types */
$this->title = $model->isNewRecord ? 'Новое отсутствие' : 'Изменение отсутствия';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/index', 'view' => 'month', 'date' => $model->date_from]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'user_id')->dropDownList($users, ['class' => 'form-select']) ?>
                <?= $form->field($model, 'absence_type_id')->dropDownList($types, ['class' => 'form-select']) ?>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'date_from')->input('date') ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'date_to')->input('date') ?></div>
                </div>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'start_time')->input('time')->hint('Оставьте пустым для полного дня.') ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'end_time')->input('time')->hint('Оставьте пустым для полного дня.') ?></div>
                </div>
                <?= $form->field($model, 'status')->dropDownList([
                    'pending' => 'Ожидает решения',
                    'approved' => 'Подтверждено',
                    'rejected' => 'Отклонено',
                    'cancelled' => 'Отменено',
                ], ['class' => 'form-select']) ?>
                <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
                <div class="d-flex justify-content-end">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
