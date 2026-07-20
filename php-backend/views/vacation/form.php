<?php

declare(strict_types=1);

use app\models\VacationRequest;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var VacationRequest $model */
/** @var array $users */
$this->title = $model->isNewRecord ? 'Новый отпуск' : 'Изменение отпуска';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/vacation/index', 'year' => substr((string)$model->date_from, 0, 4), 'userId' => $model->user_id]) ?>">Назад</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin(); ?>
                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
                <?= $form->field($model, 'user_id')->dropDownList($users, ['class' => 'form-select']) ?>
                <div class="row g-3">
                    <div class="col-md-6"><?= $form->field($model, 'date_from')->input('date') ?></div>
                    <div class="col-md-6"><?= $form->field($model, 'date_to')->input('date') ?></div>
                </div>
                <?= $form->field($model, 'status')->dropDownList([
                    'pending' => 'Ожидает решения',
                    'approved' => 'Подтверждён',
                    'rejected' => 'Отклонён',
                    'cancelled' => 'Отменён',
                ], ['class' => 'form-select']) ?>
                <?= $form->field($model, 'comment')->textarea(['rows' => 4]) ?>
                <?php if (!$model->isNewRecord): ?>
                    <div class="alert alert-secondary">
                        Рабочих дней: <strong><?= number_format((float)$model->working_days, 1, ',', ' ') ?></strong><br>
                        Источник календаря: <?= Html::encode((string)$model->calendar_source) ?>
                        <?= $model->calendar_version ? ' · ' . Html::encode((string)$model->calendar_version) : '' ?>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-end">
                    <?= Html::submitButton('Рассчитать и сохранить', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
