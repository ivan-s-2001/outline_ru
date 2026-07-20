<?php

namespace app\controllers;

use app\models\DutyAssignment;
use app\models\User;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class DutyController extends BaseController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = ['class' => VerbFilter::class, 'actions' => ['delete' => ['post']]];
        return $behaviors;
    }

    public function actionIndex(): string|\yii\web\Response
    {
        $assignments = DutyAssignment::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->andWhere(['>=', 'duty_date', date('Y-m-01')])->with('user')->orderBy(['duty_date' => SORT_ASC, 'hall' => SORT_ASC])->all();
        $model = new DutyAssignment(['workspace_id' => $this->currentUser()->workspace_id, 'created_by' => $this->currentUser()->id, 'duration_minutes' => 60]);
        if ($this->currentUser()->canManage() && $model->load(Yii::$app->request->post())) {
            $model->workspace_id = $this->currentUser()->workspace_id;
            $model->created_by = $this->currentUser()->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Дежурство назначено.');
                return $this->redirect(['index']);
            }
        }
        $users = User::find()->where(['workspace_id' => $this->currentUser()->workspace_id, 'status' => User::STATUS_ACTIVE])->orderBy(['last_name' => SORT_ASC])->all();
        return $this->render('index', compact('assignments', 'model', 'users'));
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $this->requireManager();
        $model = DutyAssignment::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]);
        if (!$model) throw new NotFoundHttpException('Дежурство не найдено.');
        $model->delete();
        return $this->redirect(['index']);
    }
}
