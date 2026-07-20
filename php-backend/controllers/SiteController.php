<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\forms\InstallForm;
use app\models\forms\LoginForm;
use app\models\User;
use app\services\AuthService;
use RuntimeException;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['POST'],
                    'health' => ['GET'],
                ],
            ],
        ];
    }

    public function actionHealth(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->db->createCommand('SELECT 1')->queryScalar();

        return [
            'ok' => true,
            'service' => 'outline-yii',
            'database' => 'mariadb',
            'time' => gmdate(DATE_ATOM),
        ];
    }

    public function actionInstall(): Response|string
    {
        if (User::find()->exists()) {
            return $this->redirect(['site/login']);
        }

        $model = new InstallForm();
        if ($model->load(Yii::$app->request->post())) {
            try {
                $user = (new AuthService())->install($model);
                Yii::$app->user->login($user, 30 * 86400);
                Yii::$app->session->setFlash('success', 'Рабочее пространство создано.');
                return $this->redirect(['site/dashboard']);
            } catch (RuntimeException $error) {
                $model->addError('', $error->getMessage());
            }
        }

        $this->layout = 'auth';
        return $this->render('install', ['model' => $model]);
    }

    public function actionLogin(): Response|string
    {
        if (!User::find()->exists()) {
            return $this->redirect(['site/install']);
        }
        if (!Yii::$app->user->isGuest) {
            return $this->redirect(['site/dashboard']);
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack(['site/dashboard']);
        }

        $model->password = '';
        $this->layout = 'auth';
        return $this->render('login', ['model' => $model]);
    }

    public function actionLogout(): Response
    {
        Yii::$app->user->logout(true);
        return $this->redirect(['site/login']);
    }

    public function actionDashboard(): Response|string
    {
        if (!User::find()->exists()) {
            return $this->redirect(['site/install']);
        }
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['site/login']);
        }

        $workspaceId = Yii::$app->user->identity->workspace_id;
        $collectionsCount = (int)Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%collections}} WHERE workspace_id=:workspace AND archived_at IS NULL',
            [':workspace' => $workspaceId]
        )->queryScalar();
        $documentsCount = (int)Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%documents}} WHERE workspace_id=:workspace AND archived_at IS NULL AND deleted_at IS NULL',
            [':workspace' => $workspaceId]
        )->queryScalar();
        $usersCount = (int)User::find()->where(['workspace_id' => $workspaceId, 'status' => 'active'])->count();

        return $this->render('dashboard', [
            'collectionsCount' => $collectionsCount,
            'documentsCount' => $documentsCount,
            'usersCount' => $usersCount,
        ]);
    }
}
