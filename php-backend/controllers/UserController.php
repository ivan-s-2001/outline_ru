<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AdminController;
use app\models\forms\UserForm;
use app\models\User;
use app\services\UserService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class UserController extends AdminController
{
    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => User::find()
                ->where(['workspace_id' => $this->workspaceId()])
                ->orderBy([
                    'status' => SORT_ASC,
                    'last_name' => SORT_ASC,
                    'first_name' => SORT_ASC,
                ]),
            'pagination' => ['pageSize' => 50],
        ]);

        return $this->render('index', ['provider' => $provider]);
    }

    public function actionCreate(): Response|string
    {
        $form = new UserForm();
        if ($form->load(Yii::$app->request->post())) {
            try {
                $user = (new UserService())->save($form, $this->currentUser());
                Yii::$app->session->setFlash('success', 'Пользователь создан.');
                return $this->redirect(['update', 'id' => $user->id]);
            } catch (RuntimeException $error) {
                $form->addError('', $error->getMessage());
            }
        }

        return $this->render('form', [
            'model' => $form,
            'title' => 'Новый пользователь',
            'user' => null,
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $user = $this->findModel($id);
        $form = UserForm::fromUser($user);
        if ($form->load(Yii::$app->request->post())) {
            try {
                $saved = (new UserService())->save($form, $this->currentUser(), $user);
                if ($saved->id === $this->currentUser()->id) {
                    Yii::$app->user->login($saved, 30 * 86400);
                }
                Yii::$app->session->setFlash('success', 'Пользователь обновлён.');
                return $this->redirect(['update', 'id' => $saved->id]);
            } catch (RuntimeException $error) {
                $form->addError('', $error->getMessage());
            }
        }

        $form->password = '';
        $form->passwordRepeat = '';
        return $this->render('form', [
            'model' => $form,
            'title' => 'Пользователь',
            'user' => $user,
        ]);
    }

    private function findModel(string $id): User
    {
        $user = User::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
        ]);
        if (!$user) {
            throw new NotFoundHttpException('Пользователь не найден.');
        }
        return $user;
    }
}
