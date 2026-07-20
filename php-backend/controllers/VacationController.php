<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\BaseRecord;
use app\models\User;
use app\models\VacationAllowance;
use app\models\VacationRequest;
use app\services\AuditService;
use app\services\ProductionCalendarService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class VacationController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'cancel' => ['POST'],
                    'sync-calendar' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(?int $year = null, ?string $userId = null): string
    {
        $year = $year && $year >= 2000 && $year <= 2200 ? $year : (int)gmdate('Y');
        $admin = $this->currentUser()->isAdmin();
        $targetUserId = $admin && $userId ? $userId : (string)$this->currentUser()->id;
        $target = $this->findUser($targetUserId);
        $allowance = $this->allowance($target, $year);

        $query = VacationRequest::find()
            ->with(['user', 'requester', 'approver'])
            ->where([
                'workspace_id' => $this->workspaceId(),
                'user_id' => $target->id,
            ])
            ->andWhere(['or',
                ['between', 'date_from', sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)],
                ['between', 'date_to', sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)],
            ])
            ->orderBy(['date_from' => SORT_DESC]);
        $approved = (float)VacationRequest::find()
            ->where([
                'workspace_id' => $this->workspaceId(),
                'user_id' => $target->id,
                'status' => 'approved',
            ])
            ->andWhere(['between', 'date_from', sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)])
            ->sum('working_days');

        return $this->render('index', [
            'provider' => new ActiveDataProvider(['query' => $query, 'pagination' => ['pageSize' => 30]]),
            'year' => $year,
            'target' => $target,
            'users' => $admin ? $this->userOptions() : [],
            'allowance' => $allowance,
            'usedDays' => $approved,
            'remainingDays' => $allowance->totalDays() - $approved,
            'canManage' => $admin,
        ]);
    }

    public function actionCreate(?string $id = null): Response|string
    {
        $this->assertManager();
        $model = $id ? $this->findRequest($id) : new VacationRequest([
            'workspace_id' => $this->workspaceId(),
            'user_id' => Yii::$app->request->get('userId'),
            'date_from' => gmdate('Y-m-d'),
            'date_to' => gmdate('Y-m-d'),
            'working_days' => 1,
            'status' => 'approved',
            'calendar_source' => 'pending-calculation',
            'requested_by_id' => $this->currentUser()->id,
            'approved_by_id' => $this->currentUser()->id,
        ]);

        if ($model->load(Yii::$app->request->post())) {
            $user = $this->findUser((string)$model->user_id);
            if ($this->hasOverlap($model)) {
                $model->addError('date_from', 'На выбранные даты уже есть заявление на отпуск.');
            }

            try {
                $snapshot = (new ProductionCalendarService())->calculate(
                    $this->workspaceId(),
                    (string)$model->date_from,
                    (string)$model->date_to
                );
                $model->working_days = $snapshot['days'];
                $model->calendar_source = $snapshot['source'];
                $model->calendar_version = $snapshot['version'];
                if ($model->status === 'approved') {
                    $model->approved_by_id = $this->currentUser()->id;
                }
                if (!$model->hasErrors()) {
                    $year = (int)substr((string)$model->date_from, 0, 4);
                    $allowance = $this->allowance($user, $year);
                    $used = (float)VacationRequest::find()
                        ->where([
                            'workspace_id' => $this->workspaceId(),
                            'user_id' => $user->id,
                            'status' => 'approved',
                        ])
                        ->andWhere(['between', 'date_from', sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year)])
                        ->andWhere(['<>', 'id', $model->id ?: ''])
                        ->sum('working_days');
                    if ($model->status === 'approved' && $used + (float)$model->working_days > $allowance->totalDays()) {
                        $model->addError('working_days', 'Недостаточно дней в отпускном балансе.');
                    }
                }
            } catch (RuntimeException $error) {
                $model->addError('date_from', $error->getMessage());
            }

            if (!$model->hasErrors() && $model->save()) {
                (new AuditService())->record(
                    $this->workspaceId(),
                    'vacation.saved',
                    $this->currentUser(),
                    (string)$model->id,
                    [
                        'userId' => $model->user_id,
                        'dateFrom' => $model->date_from,
                        'dateTo' => $model->date_to,
                        'workingDays' => (float)$model->working_days,
                        'status' => $model->status,
                        'calendarSource' => $model->calendar_source,
                    ]
                );
                Yii::$app->session->setFlash('success', 'Отпуск сохранён.');
                return $this->redirect(['index', 'year' => substr((string)$model->date_from, 0, 4), 'userId' => $model->user_id]);
            }
        }

        return $this->render('form', [
            'model' => $model,
            'users' => $this->userOptions(),
        ]);
    }

    public function actionAllowance(?string $id = null, ?string $userId = null, ?int $year = null): Response|string
    {
        $this->assertManager();
        $year ??= (int)gmdate('Y');
        $model = $id
            ? VacationAllowance::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()])
            : new VacationAllowance([
                'id' => BaseRecord::uuid(),
                'workspace_id' => $this->workspaceId(),
                'user_id' => $userId,
                'allowance_year' => $year,
                'allowance_days' => 20,
                'carried_days' => 0,
            ]);
        if (!$model) {
            throw new NotFoundHttpException('Отпускной баланс не найден.');
        }
        if ($model->load(Yii::$app->request->post())) {
            $this->findUser((string)$model->user_id);
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Отпускной баланс сохранён.');
                return $this->redirect(['index', 'year' => $model->allowance_year, 'userId' => $model->user_id]);
            }
        }
        return $this->render('allowance-form', ['model' => $model, 'users' => $this->userOptions()]);
    }

    public function actionCancel(string $id): Response
    {
        $this->assertManager();
        $model = $this->findRequest($id);
        $model->updateAttributes([
            'status' => 'cancelled',
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        Yii::$app->session->setFlash('success', 'Отпуск отменён.');
        return $this->redirect(['index', 'year' => substr((string)$model->date_from, 0, 4), 'userId' => $model->user_id]);
    }

    public function actionSyncCalendar(?int $year = null): Response
    {
        $this->assertManager();
        $year ??= (int)gmdate('Y');
        try {
            $count = (new ProductionCalendarService())->syncYear($this->workspaceId(), $year);
            Yii::$app->session->setFlash('success', 'Производственный календарь обновлён. Дней: ' . $count . '.');
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }
        return $this->redirect(['index', 'year' => $year]);
    }

    private function allowance(User $user, int $year): VacationAllowance
    {
        $model = VacationAllowance::findOne([
            'workspace_id' => $this->workspaceId(),
            'user_id' => $user->id,
            'allowance_year' => $year,
        ]);
        if ($model) {
            return $model;
        }
        $model = new VacationAllowance([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId(),
            'user_id' => $user->id,
            'allowance_year' => $year,
            'allowance_days' => 20,
            'carried_days' => 0,
        ]);
        if (!$model->save()) {
            throw new BadRequestHttpException('Не удалось создать отпускной баланс.');
        }
        return $model;
    }

    private function hasOverlap(VacationRequest $model): bool
    {
        return VacationRequest::find()
            ->where(['workspace_id' => $this->workspaceId(), 'user_id' => $model->user_id])
            ->andWhere(['in', 'status', ['pending', 'approved']])
            ->andWhere(['<>', 'id', $model->id ?: ''])
            ->andWhere(['<=', 'date_from', $model->date_to])
            ->andWhere(['>=', 'date_to', $model->date_from])
            ->exists();
    }

    private function findRequest(string $id): VacationRequest
    {
        $model = VacationRequest::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('Отпуск не найден.');
        }
        return $model;
    }

    private function findUser(string $id): User
    {
        $model = User::findOne(['id' => $id, 'workspace_id' => $this->workspaceId(), 'status' => 'active']);
        if (!$model) {
            throw new NotFoundHttpException('Сотрудник не найден.');
        }
        return $model;
    }

    private function userOptions(): array
    {
        $users = User::find()->where(['workspace_id' => $this->workspaceId(), 'status' => 'active'])
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC, 'middle_name' => SORT_ASC])->all();
        $result = [];
        foreach ($users as $user) {
            $result[$user->id] = $user->getFullName();
        }
        return $result;
    }

    private function assertManager(): void
    {
        if (!$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Управлять отпусками может только администратор.');
        }
    }
}
