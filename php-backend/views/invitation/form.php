<?php

declare(strict_types=1);

use app\models\forms\InviteForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var InviteForm $model */
$this->title = 'Пригласить пользователя';
?>
<div class="row justify-content-center"><div class="col-12 col-lg-7 col-xl-5">
<div class="d-flex justify-content-between align-items-center mb-4"><h1 class="h3 mb-0">Пригласить пользователя</h1><a class="btn btn-outline-secondary" href="<?= Url::to(['/invitation/index']) ?>">Назад</a></div>
<div class="card border-0 shadow-sm"><div class="card-body p-4">
<?php $form = ActiveForm::begin(); ?>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?= $form->field($model, 'email')->input('email', ['autofocus' => true, 'placeholder' => 'user@example.org']) ?>
<?= $form->field($model, 'role')->dropDownList(['member' => 'Участник', 'viewer' => 'Наблюдатель', 'admin' => 'Администратор'], ['class' => 'form-select']) ?>
<div class="alert alert-secondary small">Пользователь сам задаст ФИО, логин и пароль. Письмо появится в Mailpit при локальной настройке SMTP.</div>
<div class="d-flex justify-content-end"><?= Html::submitButton('Отправить приглашение', ['class' => 'btn btn-primary']) ?></div>
<?php ActiveForm::end(); ?>
</div></div></div></div>
