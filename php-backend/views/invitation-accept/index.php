<?php

declare(strict_types=1);

use app\models\Invitation;
use app\models\forms\AcceptInvitationForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var AcceptInvitationForm $model */
/** @var Invitation $invitation */
/** @var string $token */
$this->title = 'Создание учётной записи';
?>
<div class="auth-card card border-0 shadow-lg"><div class="card-body p-4 p-md-5">
<div class="text-center mb-4"><span class="brand-mark">O</span><h1 class="h4 mt-3 mb-1">Создание учётной записи</h1><p class="text-body-secondary mb-0"><?= Html::encode((string)$invitation->email) ?></p></div>
<?php $form = ActiveForm::begin(['action' => ['/invitation-accept/index', 'token' => $token]]); ?>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?= $form->field($model, 'login')->textInput(['autofocus' => true, 'autocomplete' => 'username', 'placeholder' => 'ivanov']) ?>
<div class="row g-3"><div class="col-md-6"><?= $form->field($model, 'lastName')->textInput(['autocomplete' => 'family-name']) ?></div><div class="col-md-6"><?= $form->field($model, 'firstName')->textInput(['autocomplete' => 'given-name']) ?></div></div>
<?= $form->field($model, 'middleName')->textInput(['autocomplete' => 'additional-name']) ?>
<?= $form->field($model, 'password')->passwordInput(['autocomplete' => 'new-password']) ?>
<?= $form->field($model, 'passwordRepeat')->passwordInput(['autocomplete' => 'new-password']) ?>
<div class="d-grid"><?= Html::submitButton('Создать учётную запись', ['class' => 'btn btn-primary btn-lg']) ?></div>
<?php ActiveForm::end(); ?>
</div></div>
