<?php

declare(strict_types=1);

use app\models\DutySetting;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var DutySetting $model */
$this->title = $model->isNewRecord ? 'Новый план дежурств' : 'Изменение плана дежурств';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <a class="btn btn-outline-secondary" href="<?= Url::to($model->isNewRecord ? ['/duty/index'] : ['/duty/view', 'id' => $model->id]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm"><div class="card-body p-4">
            <?php $form = ActiveForm::begin(); ?>
            <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
            <div class="row g-3">
                <div class="col-md-6"><?= $form->field($model, 'date_from')->input('date') ?></div>
                <div class="col-md-6"><?= $form->field($model, 'date_to')->input('date') ?></div>
            </div>
            <div class="row g-3">
                <div class="col-md-4"><?= $form->field($model, 'start_time')->input('time') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'end_time')->input('time') ?></div>
                <div class="col-md-4"><?= $form->field($model, 'slot_minutes')->dropDownList([10 => '10 минут', 20 => '20 минут', 30 => '30 минут', 60 => '60 минут'], ['class' => 'form-select']) ?></div>
            </div>
            <?= $form->field($model, 'status')->dropDownList(['draft' => 'Черновик', 'published' => 'Опубликован', 'archived' => 'Архив'], ['class' => 'form-select']) ?>
            <div class="d-flex justify-content-end"><?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?></div>
            <?php ActiveForm::end(); ?>
        </div></div>
    </div>
</div>
