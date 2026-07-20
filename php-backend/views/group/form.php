<?php

declare(strict_types=1);

use app\models\Group;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Group $model */
/** @var string $title */
$this->title = $title;
$cancelUrl = $model->isNewRecord ? ['/group/index'] : ['/group/view', 'id' => $model->id];
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= Html::encode($title) ?></h1>
                <p class="text-body-secondary mb-0">Группы используются для назначения прав доступа.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= Url::to($cancelUrl) ?>">Отмена</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin([
                    'options' => ['novalidate' => true],
                    'fieldConfig' => [
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-semibold'],
                        'errorOptions' => ['class' => 'invalid-feedback d-block'],
                    ],
                ]); ?>

                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'name')->textInput([
                    'autofocus' => true,
                    'maxlength' => true,
                    'placeholder' => 'Например, Отдел тестирования',
                ]) ?>
                <?= $form->field($model, 'description')->textarea([
                    'rows' => 4,
                    'maxlength' => 2000,
                    'placeholder' => 'Назначение группы',
                ]) ?>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="<?= Url::to($cancelUrl) ?>">Отмена</a>
                    <?= Html::submitButton($model->isNewRecord ? 'Создать группу' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
