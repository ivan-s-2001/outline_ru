<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Absence;
use app\models\AbsenceType;
use app\models\Shift;
use app\models\ShiftType;
use app\models\User;
use app\models\WorkTimeAdjustment;
use app\services\AuditService;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class ScheduleController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete-shift' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(string $view = 'week', ?string $date = null): string
    {
        $view = in_array($view, ['week', 'month'], true) ? $view : 'week';
        $anchor = $this->date($date);
        if ($view === 'month') {
            $start = $anchor->modify('first day of this month');
            $end = $anchor->modify('last day of this month');
        } else {
            $start = $anchor->modify('monday this week');
            $end = $start->modify('+6 days');
        }

        $this->seedTypes();
        $users = User::find()
            ->where(['workspace_id' => $this->workspaceId(), 'status' => 'active'])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC, 'middle_name' => SORT_ASC])
            ->all();
        $shifts = Shift::find()
            ->with('shiftType')
            ->where(['workspace_id' => $this->workspaceId()])
            ->andWhere(['between', 'work_date', $start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy(['start_time' => SORT_ASC])
            ->all();
        $absences = Absence::find()
            ->with('absenceType')
            ->where(['workspace_id' => $this->workspaceId(), 'status' => 'approved'])
            ->andWhere(['<=', 'date_from', $end->format('Y-m-d')])
            ->andWhere(['>=', 'date_to', $start->format('Y-m-d')])
            ->all();
        $adjustments = WorkTimeAdjustment::find()
            ->where(['workspace_id' => $this->workspaceId()])
            ->andWhere(['between', 'work_date', $start->format('Y-m-d'), $end->format('Y-m-d')])
            ->all();

        $dates = [];
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day')) as $day) {
            $dates[] = $day->format('Y-m-d');
        }
        $cells = [];
        foreach ($users as $user) {
            foreach ($dates as $day) {
                $cells[$user->id][$day] = ['shifts' => [], 'absences' => [], 'adjustments' => []];
            }
        }
        foreach ($shifts as $shift) {
            $cells[$shift->user_id][$shift->work_date]['shifts'][] = $shift;
        }
        foreach ($absences as $absence) {
            $from = max($start->format('Y-m-d'), (string)$absence->date_from);
            $to = min($end->format('Y-m-d'), (string)$absence->date_to);
            foreach (new DatePeriod(new DateTimeImmutable($from), new DateInterval('P1D'), (new DateTimeImmutable($to))->modify('+1 day')) as $day) {
                $cells[$absence->user_id][$day->format('Y-m-d')]['absences'][] = $absence;
            }
        }
        foreach ($adjustments as $adjustment) {
            $cells[$adjustment->user_id][$adjustment->work_date]['adjustments'][] = $adjustment;
        }

        return $this->render('index', [
            'view' => $view,
            'anchor' => $anchor,
            'start' => $start,
            'end' => $end,
            'dates' => $dates,
            'users' => $users,
            'cells' => $cells,
            'canManage' => $this->currentUser()->isAdmin(),
        ]);
    }

    public function actionShift(?string $id = null, ?string $date = null, ?string $userId = null): Response|string
    {
        $this->assertManager();
        $model = $id ? $this->findShift($id) : new Shift([
            'workspace_id' => $this->workspaceId(),
            'user_id' => $userId,
            'work_date' => $date ?: gmdate('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_minutes' => 60,
            'status' => 'planned',
            'created_by_id' => $this->currentUser()->id,
            'updated_by_id' => $this->currentUser()->id,
        ]);
        $model->updated_by_id = $this->currentUser()->id;

        if ($model->load(Yii::$app->request->post())) {
            $this->assertUser((string)$model->user_id);
            if ($model->shift_type_id) {
                $type = ShiftType::findOne(['id' => $model->shift_type_id, 'workspace_id' => $this->workspaceId()]);
                if (!$type) {
                    $model->addError('shift_type_id', 'Тип смены недоступен.');
                }
            }
            if (!$model->hasErrors() && $model->save()) {
                (new AuditService())->record($this->workspaceId(), 'schedule.shift.saved', $this->currentUser(), (string)$model->id, [
                    'userId' => $model->user_id,
                    'date' => $model->work_date,
                ]);
                Yii::$app->session->setFlash('success', 'Смена сохранена.');
                return $this->redirect(['/schedule/index', 'view' => 'week', 'date' => $model->work_date]);
            }
        }

        return $this->render('shift-form', [
            'model' => $model,
            'users' => $this->userOptions(),
            'types' => ArrayHelper::map(ShiftType::find()->where(['workspace_id' => $this->workspaceId(), 'is_active' => true])->orderBy('name')->all(), 'id', 'name'),
        ]);
    }

    public function actionDeleteShift(string $id): Response
    {
        $this->assertManager();
        $model = $this->findShift($id);
        $date = (string)$model->work_date;
        $model->delete();
        Yii::$app->session->setFlash('success', 'Смена удалена.');
        return $this->redirect(['/schedule/index', 'view' => 'week', 'date' => $date]);
    }

    public function actionAbsence(?string $id = null): Response|string
    {
        $this->assertManager();
        $model = $id ? $this->findAbsence($id) : new Absence([
            'workspace_id' => $this->workspaceId(),
            'date_from' => gmdate('Y-m-d'),
            'date_to' => gmdate('Y-m-d'),
            'status' => 'approved',
            'created_by_id' => $this->currentUser()->id,
            'approved_by_id' => $this->currentUser()->id,
        ]);
        if ($model->load(Yii::$app->request->post())) {
            $this->assertUser((string)$model->user_id);
            $type = AbsenceType::findOne(['id' => $model->absence_type_id, 'workspace_id' => $this->workspaceId()]);
            if (!$type) {
                $model->addError('absence_type_id', 'Тип отсутствия недоступен.');
            }
            if (!$model->hasErrors() && $model->save()) {
                Yii::$app->session->setFlash('success', 'Отсутствие сохранено.');
                return $this->redirect(['/schedule/index', 'view' => 'month', 'date' => $model->date_from]);
            }
        }
        return $this->render('absence-form', [
            'model' => $model,
            'users' => $this->userOptions(),
            'types' => ArrayHelper::map(AbsenceType::find()->where(['workspace_id' => $this->workspaceId(), 'is_active' => true])->orderBy('name')->all(), 'id', 'name'),
        ]);
    }

    public function actionAdjustment(?string $id = null): Response|string
    {
        $this->assertManager();
        $model = $id ? WorkTimeAdjustment::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]) : new WorkTimeAdjustment([
            'workspace_id' => $this->workspaceId(),
            'work_date' => gmdate('Y-m-d'),
            'kind' => 'manual',
            'minutes' => 0,
            'created_by_id' => $this->currentUser()->id,
        ]);
        if (!$model) {
            throw new NotFoundHttpException('Корректировка не найдена.');
        }
        if ($model->load(Yii::$app->request->post())) {
            $this->assertUser((string)$model->user_id);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Корректировка времени сохранена.');
                return $this->redirect(['/schedule/index', 'view' => 'week', 'date' => $model->work_date]);
            }
        }
        return $this->render('adjustment-form', ['model' => $model, 'users' => $this->userOptions()]);
    }

    private function seedTypes(): void
    {
        if (!ShiftType::find()->where(['workspace_id' => $this->workspaceId()])->exists()) {
            foreach ([
                ['Дневная', 'day', '#0D6EFD', '09:00:00', '18:00:00', 60],
                ['Ранняя', 'early', '#198754', '07:00:00', '16:00:00', 60],
                ['Поздняя', 'late', '#6F42C1', '12:00:00', '21:00:00', 60],
            ] as [$name, $code, $color, $start, $end, $break]) {
                (new ShiftType([
                    'workspace_id' => $this->workspaceId(), 'name' => $name, 'code' => $code,
                    'color' => $color, 'default_start_time' => $start, 'default_end_time' => $end,
                    'default_break_minutes' => $break, 'is_active' => true,
                ]))->save(false);
            }
        }
        if (!AbsenceType::find()->where(['workspace_id' => $this->workspaceId()])->exists()) {
            foreach ([
                ['Отпуск', 'vacation', '#0DCAF0', true],
                ['Больничный', 'sick', '#DC3545', true],
                ['Отсутствие', 'absence', '#FFC107', false],
            ] as [$name, $code, $color, $paid]) {
                (new AbsenceType([
                    'workspace_id' => $this->workspaceId(), 'name' => $name, 'code' => $code,
                    'color' => $color, 'is_paid' => $paid, 'is_active' => true,
                ]))->save(false);
            }
        }
    }

    private function date(?string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value ?: 'today');
        } catch (\Throwable) {
            return new DateTimeImmutable('today');
        }
    }

    private function userOptions(): array
    {
        $users = User::find()->where(['workspace_id' => $this->workspaceId(), 'status' => 'active'])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])->all();
        $result = [];
        foreach ($users as $user) {
            $result[$user->id] = $user->getFullName();
        }
        return $result;
    }

    private function assertUser(string $id): void
    {
        if (!User::find()->where(['id' => $id, 'workspace_id' => $this->workspaceId(), 'status' => 'active'])->exists()) {
            throw new ForbiddenHttpException('Сотрудник недоступен.');
        }
    }

    private function assertManager(): void
    {
        if (!$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Изменять график может только администратор.');
        }
    }

    private function findShift(string $id): Shift
    {
        $model = Shift::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('Смена не найдена.');
        }
        return $model;
    }

    private function findAbsence(string $id): Absence
    {
        $model = Absence::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('Отсутствие не найдено.');
        }
        return $model;
    }
}
