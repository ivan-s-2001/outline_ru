<?php

declare(strict_types=1);

namespace app\components;

use app\models\User;
use Yii;
use yii\web\Controller;
use yii\web\Response;

abstract class AuthenticatedController extends Controller
{
    public function beforeAction($action): bool
    {
        if (Yii::$app->user->isGuest) {
            Yii::$app->user->setReturnUrl(Yii::$app->request->url);
            $response = Yii::$app->getResponse();
            $response->redirect(['/site/login']);
            $response->send();
            return false;
        }
        return parent::beforeAction($action);
    }

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        return $user;
    }

    protected function workspaceId(): string
    {
        return (string)$this->currentUser()->workspace_id;
    }

    protected function redirectToLogin(): Response
    {
        return $this->redirect(['/site/login']);
    }
}
