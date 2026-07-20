<?php

declare(strict_types=1);

use app\models\forms\LoginForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var LoginForm $model */
$this->title = 'Вход';
?>
<div class="card border-0 shadow-sm auth-card">
    <div class="card-body p-4 p-md-5">
        <h2 class="h4 mb-1">Вход в систему</h2>
        <p class="text-body-secondary mb-4">Используйте логин и пароль, выданные администратором.</p>

        <?php $form = ActiveForm::begin([
            'id' => 'login-form',
            'options' => ['novalidate' => true],
            'fieldConfig' => [
                'inputOptions' => ['class' => 'form-control form-control-lg'],
                'labelOptions' => ['class' => 'form-label fw-semibold'],
                'errorOptions' => ['class' => 'invalid-feedback d-block'],
            ],
        ]); ?>

        <?= $form->errorSummary($model, [
            'class' => 'alert alert-danger',
            'header' => '<div class="fw-semibold mb-1">Не удалось войти</div>',
        ]) ?>

        <?= $form->field($model, 'login')->textInput([
            'autofocus' => true,
            'autocomplete' => 'username',
            'placeholder' => 'ivanov',
        ])->hint('Можно также указать email.', ['class' => 'form-text']) ?>

        <?= $form->field($model, 'password')->passwordInput([
            'autocomplete' => 'current-password',
            'placeholder' => 'Введите пароль',
        ]) ?>

        <?= $form->field($model, 'rememberMe')->checkbox([
            'class' => 'form-check-input',
            'labelOptions' => ['class' => 'form-check-label'],
        ]) ?>

        <?= Html::submitButton('Войти', [
            'class' => 'btn btn-primary btn-lg w-100 mt-2',
            'name' => 'login-button',
        ]) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
