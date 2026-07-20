<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\User;
use Yii;
use yii\web\Response;

final class MentionController extends AuthenticatedController
{
    public function actionUsers(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $users = User::find()
            ->where([
                'workspace_id' => $this->workspaceId(),
                'status' => 'active',
            ])
            ->orderBy([
                'last_name' => SORT_ASC,
                'first_name' => SORT_ASC,
                'middle_name' => SORT_ASC,
            ])
            ->all();

        return [
            'data' => array_map(static fn (User $user): array => [
                'id' => (string)$user->id,
                'name' => $user->getFullName(),
                'login' => (string)$user->login,
            ], $users),
        ];
    }
}
