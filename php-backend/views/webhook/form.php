<?php

declare(strict_types=1);

use app\models\Webhook;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Webhook $model */
/** @var string $eventsText */
$this->title = $model->isNewRecord ? 'Новый webhook' : 'Изменение webhook';
?>
<div class="row justify-content-center"><div class="col-12 col-lg-8 col-xl-6">
    <div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1><a class="btn btn-outline-secondary" href="<?= Url::to($model->isNewRecord ? ['/webhook/index'] : ['/webhook/view', 'id' => $model->id]) ?>">Назад</a></div>
    <div class="card border-0 shadow-sm"><div class="card-body p-4">
        <?php $form = ActiveForm::begin(); ?>
        <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
        <?= $form->field($model, 'name')->textInput(['maxlength' => true])->label('Название') ?>
        <?= $form->field($model, 'url')->input('url', ['maxlength' => true, 'placeholder' => 'https://example.org/webhook'])->label('URL') ?>
        <div class="mb-3"><label class="form-label fw-semibold" for="eventsText">События</label><?= Html::textInput('eventsText', $eventsText, ['id' => 'eventsText', 'class' => 'form-control', 'placeholder' => 'document.created, document.updated или *']) ?><div class="form-text">Разделяйте события пробелами или запятыми. Звёздочка подписывает на все события.</div></div>
        <?= $form->field($model, 'is_active')->checkbox()->label('Webhook активен') ?>
        <div class="d-flex justify-content-end"><?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?></div>
        <?php ActiveForm::end(); ?>
    </div></div>
</div></div>
