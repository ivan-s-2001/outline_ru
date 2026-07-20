<?php

namespace app\controllers;

use app\models\Collection;
use app\models\Document;
use app\models\InstallForm;
use app\models\LoginForm;
use app\models\Shift;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ErrorAction;

class SiteController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['index', 'logout'],
                'rules' => [
                    ['actions' => ['index', 'logout'], 'allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['logout' => ['post']],
            ],
        ];
    }

    public function actions(): array
    {
        return ['error' => ['class' => ErrorAction::class]];
    }

    public function actionIndex(): string
    {
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $documents = Document::find()->where(['workspace_id' => $user->workspace_id, 'status' => 'published'])->orderBy(['updated_at' => SORT_DESC])->limit(10)->all();
        $collections = Collection::find()->where(['workspace_id' => $user->workspace_id])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all();
        $shifts = Shift::find()->where(['workspace_id' => $user->workspace_id])->andWhere(['>=', 'shift_date', date('Y-m-d')])->orderBy(['shift_date' => SORT_ASC, 'start_time' => SORT_ASC])->limit(8)->all();
        return $this->render('index', compact('documents', 'collections', 'shifts'));
    }

    public function actionLogin(): string|\yii\web\Response
    {
        if (!User::find()->exists()) {
            return $this->redirect(['install']);
        }
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }
        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }
        $model->password = '';
        return $this->render('login', ['model' => $model]);
    }

    public function actionInstall(): string|\yii\web\Response
    {
        if (User::find()->exists()) {
            return $this->redirect(['login']);
        }
        $model = new InstallForm();
        if ($model->load(Yii::$app->request->post())) {
            $user = $model->install();
            if ($user) {
                Yii::$app->user->login($user, 2592000);
                Yii::$app->session->setFlash('success', 'Рабочее пространство создано.');
                return $this->goHome();
            }
        }
        return $this->render('install', ['model' => $model]);
    }

    public function actionLogout(): \yii\web\Response
    {
        Yii::$app->user->logout();
        return $this->redirect(['login']);
    }
}
