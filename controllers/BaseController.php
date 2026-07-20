<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;

abstract class BaseController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [[
                    'allow' => true,
                    'roles' => ['@'],
                ]],
            ],
        ];
    }

    protected function currentUser(): User
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        return $user;
    }

    protected function requireManager(): void
    {
        if (!$this->currentUser()->canManage()) {
            throw new ForbiddenHttpException('Недостаточно прав.');
        }
    }

    protected function requireAdmin(): void
    {
        if (!$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Требуются права администратора.');
        }
    }
}
