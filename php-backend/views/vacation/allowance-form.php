<?php

declare(strict_types=1);

use app\models\VacationAllowance;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var VacationAllowance $model */
/** @var array $users */
$this->title = 'Отпускной баланс';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Отпускной баланс</h1>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/index', 'year' => $model->allowance_year, 'userId' => $model->user_id]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'user_id')->dropDownList($users, ['class' => 'form-select']) ?>
                <?= $form->field($model, 'allowance_year')->input('number', ['min' => 2000, 'max' => 2200]) ?>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'allowance_days')->input('number', ['min' => -365, 'max' => 365, 'step' => 0.5]) ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'carried_days')->input('number', ['min' => -365, 'max' => 365, 'step' => 0.5]) ?></div>
                </div>
                <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
                <div class="d-flex justify-content-end">
                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
