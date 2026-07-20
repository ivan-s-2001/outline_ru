<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AdminController;
use app\models\Group;
use app\models\GroupUser;
use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class GroupController extends AdminController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'add-member' => ['POST'],
                    'remove-member' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => Group::find()
                ->where(['workspace_id' => $this->workspaceId()])
                ->with('users')
                ->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', ['provider' => $provider]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Group(['workspace_id' => $this->workspaceId()]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Группа создана.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Новая группа',
        ]);
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        $memberIds = GroupUser::find()
            ->select('user_id')
            ->where(['group_id' => $model->id])
            ->column();

        $availableUsers = User::find()
            ->where(['workspace_id' => $this->workspaceId(), 'status' => 'active'])
            ->andFilterWhere(['not in', 'id', $memberIds])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'model' => $model,
            'members' => $model->users,
            'availableUsers' => $availableUsers,
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Группа обновлена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Редактирование группы',
        ]);
    }

    public function actionDelete(string $id): Response
    {
        $model = $this->findModel($id);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Группа удалена.');
        return $this->redirect(['index']);
    }

    public function actionAddMember(string $id): Response
    {
        $group = $this->findModel($id);
        $userId = trim((string)Yii::$app->request->post('userId', ''));
        $user = User::findOne([
            'id' => $userId,
            'workspace_id' => $this->workspaceId(),
            'status' => 'active',
        ]);
        if (!$user) {
            throw new BadRequestHttpException('Пользователь не найден.');
        }

        $membership = new GroupUser([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);
        if (!$membership->save()) {
            Yii::$app->session->setFlash('error', $this->firstError($membership->getFirstErrors()));
        } else {
            Yii::$app->session->setFlash('success', 'Пользователь добавлен в группу.');
        }

        return $this->redirect(['view', 'id' => $group->id]);
    }

    public function actionRemoveMember(string $id, string $userId): Response
    {
        $group = $this->findModel($id);
        $membership = GroupUser::findOne([
            'group_id' => $group->id,
            'user_id' => $userId,
        ]);
        if (!$membership) {
            throw new NotFoundHttpException('Участник группы не найден.');
        }
        $membership->delete();
        Yii::$app->session->setFlash('success', 'Пользователь удалён из группы.');

        return $this->redirect(['view', 'id' => $group->id]);
    }

    private function findModel(string $id): Group
    {
        $model = Group::find()
            ->with('users')
            ->where(['id' => $id, 'workspace_id' => $this->workspaceId()])
            ->one();
        if (!$model) {
            throw new NotFoundHttpException('Группа не найдена.');
        }
        return $model;
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось изменить группу.';
    }
}
