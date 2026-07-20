<?php

declare(strict_types=1);

use app\models\WorkTimeAdjustment;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var WorkTimeAdjustment $model */
/** @var array $users */
$this->title = $model->isNewRecord ? 'Новая корректировка времени' : 'Изменение корректировки';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/schedule/index', 'view' => 'week', 'date' => $model->work_date]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'user_id')->dropDownList($users, ['class' => 'form-select']) ?>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'work_date')->input('date') ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'kind')->dropDownList([
                        'overtime' => 'Переработка',
                        'undertime' => 'Недоработка',
                        'manual' => 'Ручная корректировка',
                    ], ['class' => 'form-select']) ?></div>
                </div>
                <?= $form->field($model, 'minutes')->input('number', ['min' => -1440, 'max' => 1440, 'step' => 10])
                    ->hint('Положительное число добавляет время, отрицательное вычитает.') ?>
                <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
                <div class="d-flex justify-content-end">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
