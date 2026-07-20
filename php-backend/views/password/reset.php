<?php

declare(strict_types=1);

use app\models\forms\ResetPasswordForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var ResetPasswordForm $model */
/** @var string $token */
$this->title = 'Новый пароль';
?>
<div class="auth-card card border-0 shadow-lg"><div class="card-body p-4 p-md-5">
<div class="text-center mb-4"><span class="brand-mark">O</span><h1 class="h4 mt-3 mb-1">Новый пароль</h1><p class="text-body-secondary mb-0">После сохранения старые API/OAuth-токены будут отозваны.</p></div>
<?php $form = ActiveForm::begin(['action' => ['/password/reset', 'token' => $token]]); ?>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?= $form->field($model, 'password')->passwordInput(['autofocus' => true, 'autocomplete' => 'new-password']) ?>
<?= $form->field($model, 'passwordRepeat')->passwordInput(['autocomplete' => 'new-password']) ?>
<div class="d-grid"><?= Html::submitButton('Сохранить новый пароль', ['class' => 'btn btn-primary btn-lg']) ?></div>
<?php ActiveForm::end(); ?>
</div></div>
