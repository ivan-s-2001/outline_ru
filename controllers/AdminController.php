<?php

namespace app\controllers;

use app\models\User;
use Yii;
use yii\web\NotFoundHttpException;

class AdminController extends BaseController
{
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }
        $this->requireAdmin();
        return true;
    }

    public function actionUsers(): string
    {
        $users = User::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all();
        return $this->render('users', ['users' => $users]);
    }

    public function actionUser(?int $id = null): string|\yii\web\Response
    {
        $model = $id ? User::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]) : new User([
            'workspace_id' => $this->currentUser()->workspace_id,
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_ACTIVE,
        ]);
        if (!$model) throw new NotFoundHttpException('Пользователь не найден.');
        if ($model->load(Yii::$app->request->post())) {
            $model->workspace_id = $this->currentUser()->workspace_id;
            if ($model->isNewRecord && !$model->newPassword) {
                $model->addError('newPassword', 'Укажите пароль.');
            } elseif ($model->save()) {
                Yii::$app->session->setFlash('success', 'Пользователь сохранён.');
                return $this->redirect(['users']);
            }
        }
        return $this->render('user', ['model' => $model]);
    }
}
