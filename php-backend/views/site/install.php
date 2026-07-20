<?php

declare(strict_types=1);

use app\models\forms\InstallForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

/** @var InstallForm $model */
$this->title = 'Первичная настройка';
?>
<div class="card border-0 shadow-sm auth-card auth-card-wide">
    <div class="card-body p-4 p-md-5">
        <div class="mb-4">
            <span class="badge text-bg-primary mb-2">Первый запуск</span>
            <h2 class="h4 mb-1">Создание рабочего пространства</h2>
            <p class="text-body-secondary mb-0">Создайте локального администратора с логином и паролем.</p>
        </div>

        <?php $form = ActiveForm::begin([
            'id' => 'install-form',
            'options' => ['novalidate' => true],
            'fieldConfig' => [
                'inputOptions' => ['class' => 'form-control'],
                'labelOptions' => ['class' => 'form-label fw-semibold'],
                'errorOptions' => ['class' => 'invalid-feedback d-block'],
            ],
        ]); ?>

        <?= $form->errorSummary($model, [
            'class' => 'alert alert-danger',
            'header' => '<div class="fw-semibold mb-1">Проверьте данные</div>',
        ]) ?>

        <div class="row g-3">
            <div class="col-12">
                <?= $form->field($model, 'workspaceName')->textInput([
                    'autofocus' => true,
                    'autocomplete' => 'organization',
                    'placeholder' => 'Название компании или команды',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'login')->textInput([
                    'autocomplete' => 'username',
                    'placeholder' => 'admin',
                ])->hint('Латинские буквы, цифры, точка, дефис и подчёркивание.', ['class' => 'form-text']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'email')->input('email', [
                    'autocomplete' => 'email',
                    'placeholder' => 'admin@example.local',
                ]) ?>
            </div>
            <div class="col-12">
                <?= $form->field($model, 'fullName')->textInput([
                    'autocomplete' => 'name',
                    'placeholder' => 'Иванов Иван Иванович',
                ])->hint('Пишите ФИО через пробел: фамилия имя отчество.', ['class' => 'form-text']) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'password')->passwordInput([
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Не менее 10 символов',
                ]) ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($model, 'passwordRepeat')->passwordInput([
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Повторите пароль',
                ]) ?>
            </div>
        </div>

        <?= Html::submitButton('Создать рабочее пространство', [
            'class' => 'btn btn-primary btn-lg w-100 mt-3',
        ]) ?>

        <?php ActiveForm::end(); ?>
    </div>
</div>
