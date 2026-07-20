<?php

declare(strict_types=1);

use app\models\forms\ForgotPasswordForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ForgotPasswordForm $model */
$this->title = 'Восстановление пароля';
?>
<div class="auth-card card border-0 shadow-lg"><div class="card-body p-4 p-md-5">
<div class="text-center mb-4"><span class="brand-mark">O</span><h1 class="h4 mt-3 mb-1">Восстановление пароля</h1><p class="text-body-secondary mb-0">Ссылка будет отправлена на email учётной записи.</p></div>
<?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?><div class="alert alert-<?= $type === 'error' ? 'danger' : Html::encode($type) ?>"><?= Html::encode((string)$message) ?></div><?php endforeach; ?>
<?php $form = ActiveForm::begin(); ?>
<?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>
<?= $form->field($model, 'identity')->textInput(['autofocus' => true, 'autocomplete' => 'username', 'placeholder' => 'логин или email']) ?>
<div class="d-grid gap-2"><?= Html::submitButton('Отправить ссылку', ['class' => 'btn btn-primary btn-lg']) ?><a class="btn btn-outline-secondary" href="<?= Url::to(['/site/login']) ?>">Вернуться ко входу</a></div>
<?php ActiveForm::end(); ?>
</div></div>
