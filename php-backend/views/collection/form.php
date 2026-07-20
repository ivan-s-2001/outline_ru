<?php

declare(strict_types=1);

use app\models\Collection;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Collection $model */
/** @var string $title */
$this->title = $title;
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= Html::encode($title) ?></h1>
                <p class="text-body-secondary mb-0">Название, оформление и доступ по умолчанию.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= $model->isNewRecord ? Url::to(['/collection/index']) : Url::to(['/collection/view', 'id' => $model->id]) ?>">Отмена</a>
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
                    'placeholder' => 'Например, Регламенты',
                ]) ?>

                <?= $form->field($model, 'description')->textarea([
                    'rows' => 4,
                    'maxlength' => 2000,
                    'placeholder' => 'Кратко опишите назначение коллекции',
                ]) ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <?= $form->field($model, 'icon')->textInput([
                            'maxlength' => true,
                            'placeholder' => '📁',
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'color')->input('color', [
                            'class' => 'form-control form-control-color w-100',
                            'title' => 'Выберите цвет',
                        ]) ?>
                    </div>
                </div>

                <?= $form->field($model, 'permission')->dropDownList([
                    'read_write' => 'Участники могут читать и изменять',
                    'read' => 'Участники могут только читать',
                ]) ?>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="<?= $model->isNewRecord ? Url::to(['/collection/index']) : Url::to(['/collection/view', 'id' => $model->id]) ?>">Отмена</a>
                    <?= Html::submitButton($model->isNewRecord ? 'Создать коллекцию' : 'Сохранить', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
