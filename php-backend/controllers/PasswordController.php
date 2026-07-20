<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\forms\ForgotPasswordForm;
use app\models\forms\ResetPasswordForm;
use app\services\PasswordResetService;
use RuntimeException;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

final class PasswordController extends Controller
{
    public function actionForgot(): Response|string
    {
        $this->layout = 'auth';
        $form = new ForgotPasswordForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                (new PasswordResetService())->request($form->identity);
            } catch (RuntimeException $error) {
                Yii::warning(['message' => 'Password reset email queue failed', 'error' => $error->getMessage()], __METHOD__);
            }
            Yii::$app->session->setFlash(
                'success',
                'Если учётная запись найдена, письмо со ссылкой уже поставлено в очередь.'
            );
            return $this->refresh();
        }
        return $this->render('forgot', ['model' => $form]);
    }

    public function actionReset(string $token = ''): Response|string
    {
        $this->layout = 'auth';
        try {
            $resetToken = (new PasswordResetService())->findUsable($token);
        } catch (RuntimeException $error) {
            throw new BadRequestHttpException($error->getMessage(), 0, $error);
        }
        $form = new ResetPasswordForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $user = (new PasswordResetService())->reset($resetToken, $form->password);
                Yii::$app->user->login($user, 30 * 86400);
                Yii::$app->session->setFlash('success', 'Пароль изменён.');
                return $this->redirect(['/site/dashboard']);
            } catch (RuntimeException $error) {
                $form->addError('', $error->getMessage());
            }
        }
        return $this->render('reset', ['model' => $form, 'token' => $token]);
    }
}
