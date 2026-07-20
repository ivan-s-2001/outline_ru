<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AdminController;
use app\models\Event;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\helpers\ArrayHelper;

final class AuditController extends AdminController
{
    public function actionIndex(): string
    {
        $queryText = trim((string)Yii::$app->request->get('q', ''));
        $userId = trim((string)Yii::$app->request->get('userId', ''));

        $query = Event::find()
            ->with('user')
            ->where(['workspace_id' => $this->workspaceId()])
            ->orderBy(['created_at' => SORT_DESC]);
        if ($queryText !== '') {
            $escaped = addcslashes($queryText, '%_');
            $query->andWhere(['or',
                ['like', 'name', $escaped, false],
                ['like', 'model_id', $escaped, false],
                ['like', 'ip', $escaped, false],
                ['like', 'data', $escaped, false],
            ]);
        }
        if ($userId !== '') {
            $query->andWhere(['user_id' => $userId]);
        }

        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50],
        ]);
        $users = User::find()
            ->where(['workspace_id' => $this->workspaceId()])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();

        return $this->render('index', [
            'provider' => $provider,
            'queryText' => $queryText,
            'userId' => $userId,
            'userOptions' => ArrayHelper::map($users, 'id', static fn (User $user): string => $user->getFullName()),
        ]);
    }
}
