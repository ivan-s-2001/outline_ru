<?php

namespace app\controllers;

use app\models\Absence;
use app\models\Shift;
use app\models\User;
use app\models\WorkTimeAdjustment;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class ScheduleController extends BaseController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = ['class' => VerbFilter::class, 'actions' => ['save-shift' => ['post'], 'delete-shift' => ['post'], 'save-absence' => ['post'], 'save-adjustment' => ['post']]];
        return $behaviors;
    }

    public function actionIndex(string $view = 'week', ?string $date = null): string
    {
        $anchor = new DateTimeImmutable($date ?: 'today');
        if ($view === 'month') {
            $from = $anchor->modify('first day of this month');
            $to = $anchor->modify('last day of this month');
        } else {
            $view = 'week';
            $from = $anchor->modify('monday this week');
            $to = $from->modify('+6 days');
        }
        $dates = [];
        foreach (new DatePeriod($from, new DateInterval('P1D'), $to->modify('+1 day')) as $day) {
            $dates[] = new DateTimeImmutable($day->format('Y-m-d'));
        }
        $users = User::find()->where(['workspace_id' => $this->currentUser()->workspace_id, 'status' => User::STATUS_ACTIVE])->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all();
        $shifts = Shift::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->andWhere(['between', 'shift_date', $from->format('Y-m-d'), $to->format('Y-m-d')])->orderBy(['start_time' => SORT_ASC])->all();
        $absences = Absence::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->andWhere(['<=', 'date_from', $to->format('Y-m-d')])->andWhere(['>=', 'date_to', $from->format('Y-m-d')])->all();
        $adjustments = WorkTimeAdjustment::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->andWhere(['between', 'work_date', $from->format('Y-m-d'), $to->format('Y-m-d')])->all();
        return $this->render('index', compact('view', 'anchor', 'from', 'to', 'dates', 'users', 'shifts', 'absences', 'adjustments'));
    }

    public function actionSaveShift(): \yii\web\Response
    {
        $this->requireManager();
        $model = new Shift([
            'workspace_id' => $this->currentUser()->workspace_id,
            'created_by' => $this->currentUser()->id,
        ]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Смена добавлена.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }
        return $this->redirect(['index', 'view' => Yii::$app->request->post('view', 'week'), 'date' => Yii::$app->request->post('date')]);
    }

    public function actionDeleteShift(int $id): \yii\web\Response
    {
        $this->requireManager();
        $model = Shift::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]);
        if (!$model) throw new NotFoundHttpException('Смена не найдена.');
        $date = $model->shift_date;
        $model->delete();
        return $this->redirect(['index', 'date' => $date]);
    }

    public function actionSaveAbsence(): \yii\web\Response
    {
        $this->requireManager();
        $model = new Absence(['workspace_id' => $this->currentUser()->workspace_id, 'created_by' => $this->currentUser()->id]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Отсутствие добавлено.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }
        return $this->redirect(['index', 'date' => $model->date_from ?: null]);
    }

    public function actionSaveAdjustment(): \yii\web\Response
    {
        $this->requireManager();
        $model = new WorkTimeAdjustment(['workspace_id' => $this->currentUser()->workspace_id, 'created_by' => $this->currentUser()->id]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Корректировка добавлена.');
        } else {
            Yii::$app->session->setFlash('error', implode(' ', $model->getFirstErrors()));
        }
        return $this->redirect(['index', 'date' => $model->work_date ?: null]);
    }
}
