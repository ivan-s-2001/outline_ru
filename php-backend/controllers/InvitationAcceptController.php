<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\forms\AcceptInvitationForm;
use app\services\InvitationService;
use RuntimeException;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

final class InvitationAcceptController extends Controller
{
    public function actionIndex(string $token = ''): Response|string
    {
        $this->layout = 'auth';
        try {
            $invitation = (new InvitationService())->findUsable($token);
        } catch (RuntimeException $error) {
            throw new BadRequestHttpException($error->getMessage(), 0, $error);
        }

        $form = new AcceptInvitationForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                $user = (new InvitationService())->accept($invitation, $form);
                Yii::$app->user->login($user, 30 * 86400);
                Yii::$app->session->setFlash('success', 'Учётная запись создана.');
                return $this->redirect(['/site/dashboard']);
            } catch (RuntimeException $error) {
                $form->addError('', $error->getMessage());
            }
        }

        return $this->render('index', [
            'model' => $form,
            'invitation' => $invitation,
            'token' => $token,
        ]);
    }
}
