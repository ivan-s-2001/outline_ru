<?php

declare(strict_types=1);

use app\models\forms\UserForm;
use app\models\User;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var UserForm $model */
/** @var string $title */
/** @var User|null $user */
$this->title = $title;
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-9">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= Html::encode($title) ?></h1>
                <p class="text-body-secondary mb-0">
                    <?= $user ? Html::encode($user->getFullName()) : 'Создание локальной учётной записи.' ?>
                </p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/user/index']) ?>">К списку</a>
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

                <h2 class="h5 mb-3">ФИО</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <?= $form->field($model, 'lastName')->textInput([
                            'autofocus' => true,
                            'autocomplete' => 'family-name',
                            'maxlength' => true,
                        ]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'firstName')->textInput([
                            'autocomplete' => 'given-name',
                            'maxlength' => true,
                        ]) ?>
                    </div>
                    <div class="col-md-4">
                        <?= $form->field($model, 'middleName')->textInput([
                            'autocomplete' => 'additional-name',
                            'maxlength' => true,
                        ]) ?>
                    </div>
                </div>

                <h2 class="h5 mb-3">Учётная запись</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <?= $form->field($model, 'login')->textInput([
                            'autocomplete' => 'username',
                            'maxlength' => true,
                            'placeholder' => 'ivanov',
                        ])->hint('Латинские буквы, цифры, точка, дефис и подчёркивание.', ['class' => 'form-text']) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'email')->input('email', [
                            'autocomplete' => 'email',
                            'maxlength' => true,
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'role')->dropDownList([
                            'owner' => 'Владелец',
                            'admin' => 'Администратор',
                            'member' => 'Участник',
                            'viewer' => 'Наблюдатель',
                        ], ['class' => 'form-select']) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'status')->dropDownList([
                            'active' => 'Активен',
                            'suspended' => 'Заблокирован',
                        ], ['class' => 'form-select']) ?>
                    </div>
                </div>

                <h2 class="h5 mb-1"><?= $user ? 'Смена пароля' : 'Пароль' ?></h2>
                <?php if ($user): ?>
                    <p class="text-body-secondary small">Оставьте оба поля пустыми, чтобы сохранить текущий пароль.</p>
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <?= $form->field($model, 'password')->passwordInput([
                            'autocomplete' => 'new-password',
                            'placeholder' => $user ? 'Новый пароль' : 'Не менее 10 символов',
                        ]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'passwordRepeat')->passwordInput([
                            'autocomplete' => 'new-password',
                            'placeholder' => 'Повторите пароль',
                        ]) ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="<?= Url::to(['/user/index']) ?>">Отмена</a>
                    <?= Html::submitButton($user ? 'Сохранить изменения' : 'Создать пользователя', ['class' => 'btn btn-primary']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
