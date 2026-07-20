<?php

namespace app\controllers;

use app\models\ProductionCalendarDay;
use app\models\VacationRequest;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class VacationController extends BaseController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = ['class' => VerbFilter::class, 'actions' => ['status' => ['post']]];
        return $behaviors;
    }

    public function actionIndex(): string|\yii\web\Response
    {
        $query = VacationRequest::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->with('user')->orderBy(['created_at' => SORT_DESC]);
        if (!$this->currentUser()->canManage()) {
            $query->andWhere(['user_id' => $this->currentUser()->id]);
        }
        $requests = $query->all();
        $model = new VacationRequest([
            'workspace_id' => $this->currentUser()->workspace_id,
            'user_id' => $this->currentUser()->id,
            'status' => 'pending',
        ]);
        if ($model->load(Yii::$app->request->post())) {
            $model->workspace_id = $this->currentUser()->workspace_id;
            $model->user_id = $this->currentUser()->id;
            $model->working_days = $this->workingDays($model->date_from, $model->date_to);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Заявка на отпуск отправлена.');
                return $this->redirect(['index']);
            }
        }
        return $this->render('index', compact('requests', 'model'));
    }

    public function actionStatus(int $id, string $status): \yii\web\Response
    {
        $this->requireManager();
        if (!in_array($status, ['approved', 'rejected', 'cancelled'], true)) {
            throw new \yii\web\BadRequestHttpException('Недопустимый статус.');
        }
        $model = VacationRequest::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]);
        if (!$model) throw new NotFoundHttpException('Заявка не найдена.');
        $model->status = $status;
        $model->reviewed_by = $this->currentUser()->id;
        $model->reviewed_at = gmdate('Y-m-d H:i:s');
        $model->save(false);
        Yii::$app->session->setFlash('success', 'Статус заявки изменён.');
        return $this->redirect(['index']);
    }

    private function workingDays(?string $from, ?string $to): int
    {
        if (!$from || !$to || $from > $to) return 0;
        $start = new DateTimeImmutable($from);
        $end = new DateTimeImmutable($to);
        $special = ProductionCalendarDay::find()->where(['between', 'calendar_date', $from, $to])->indexBy('calendar_date')->all();
        $count = 0;
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $day) {
            $key = $day->format('Y-m-d');
            if (isset($special[$key])) {
                if ((int)$special[$key]->is_working === 1) $count++;
                continue;
            }
            if ((int)$day->format('N') <= 5) $count++;
        }
        return $count;
    }
}
