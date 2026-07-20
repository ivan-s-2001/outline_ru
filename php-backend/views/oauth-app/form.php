<?php

declare(strict_types=1);

use app\models\OAuthApp;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var OAuthApp $model */
/** @var string $redirectUrisText */
/** @var string $scopesText */
$this->title = $model->isNewRecord ? 'Новое OAuth-приложение' : 'Изменение OAuth-приложения';
?>
<div class="row justify-content-center"><div class="col-12 col-lg-8 col-xl-6">
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0"><?= Html::encode($this->title) ?></h1><a class="btn btn-outline-secondary" href="<?= Url::to($model->isNewRecord ? ['/oauth-app/index'] : ['/oauth-app/view', 'id' => $model->id]) ?>">Назад</a></div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<?php $form = ActiveForm::begin(); ?>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?= $form->field($model, 'name')->textInput(['maxlength' => true])->label('Название') ?>
<div class="mb-3"><label class="form-label fw-semibold" for="redirectUrisText">Redirect URI</label><?= Html::textarea('redirectUrisText', $redirectUrisText, ['id' => 'redirectUrisText', 'class' => 'form-control', 'rows' => 5, 'placeholder' => "https://app.example.org/oauth/callback\nhttp://127.0.0.1:8080/callback"]) ?><div class="form-text">Один полный URI на строку. При обмене кода требуется точное совпадение.</div></div>
<div class="mb-3"><label class="form-label fw-semibold" for="scopesText">Scopes</label><?= Html::textInput('scopesText', $scopesText, ['id' => 'scopesText', 'class' => 'form-control', 'placeholder' => 'read write create']) ?><div class="form-text">Допустимы read, write и create.</div></div>
<?= $form->field($model, 'is_confidential')->checkbox()->label('Конфиденциальное приложение с client_secret') ?>
<?= $form->field($model, 'is_active')->checkbox()->label('Приложение активно') ?>
<div class="d-flex justify-content-end"><?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary']) ?></div>
<?php ActiveForm::end(); ?>
</div></div></div></div>
