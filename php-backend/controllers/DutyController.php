<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\BaseRecord;
use app\models\DutyAssignment;
use app\models\DutyParticipant;
use app\models\DutySetting;
use app\models\DutySwap;
use app\models\User;
use app\services\AuditService;
use app\services\DutyAllocationService;
use RuntimeException;
use Throwable;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class DutyController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'add-participant' => ['POST'],
                    'remove-participant' => ['POST'],
                    'generate' => ['POST'],
                    'swap' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'provider' => new ActiveDataProvider([
                'query' => DutySetting::find()
                    ->where(['workspace_id' => $this->workspaceId()])
                    ->orderBy(['date_from' => SORT_DESC]),
                'pagination' => ['pageSize' => 30],
            ]),
            'canManage' => $this->currentUser()->isAdmin(),
        ]);
    }

    public function actionView(string $id): string
    {
        $setting = $this->findSetting($id);
        $participants = DutyParticipant::find()
            ->with('user')
            ->where(['duty_setting_id' => $setting->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        $assignments = DutyAssignment::find()
            ->with('user')
            ->where(['duty_setting_id' => $setting->id])
            ->orderBy(['duty_date' => SORT_ASC, 'start_time' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'setting' => $setting,
            'participants' => $participants,
            'assignments' => $assignments,
            'users' => $this->userOptions(),
            'canManage' => $this->currentUser()->isAdmin(),
        ]);
    }

    public function actionCreate(?string $id = null): Response|string
    {
        $this->assertManager();
        $model = $id ? $this->findSetting($id) : new DutySetting([
            'workspace_id' => $this->workspaceId(),
            'name' => 'Дежурства',
            'date_from' => gmdate('Y-m-d'),
            'date_to' => gmdate('Y-m-d', strtotime('+4 days')),
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'slot_minutes' => 10,
            'status' => 'draft',
            'created_by_id' => $this->currentUser()->id,
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Настройка дежурств сохранена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', ['model' => $model]);
    }

    public function actionAddParticipant(string $id): Response
    {
        $this->assertManager();
        $setting = $this->findSetting($id);
        $userId = trim((string)Yii::$app->request->post('userId'));
        $user = User::findOne([
            'id' => $userId,
            'workspace_id' => $this->workspaceId(),
            'status' => 'active',
        ]);
        if (!$user) {
            throw new BadRequestHttpException('Сотрудник не найден.');
        }
        $coefficient = (float)Yii::$app->request->post('coefficient', 1);
        if ($coefficient <= 0 || $coefficient > 10) {
            throw new BadRequestHttpException('Коэффициент должен быть больше нуля и не больше 10.');
        }

        $participant = DutyParticipant::findOne([
            'duty_setting_id' => $setting->id,
            'user_id' => $user->id,
        ]) ?? new DutyParticipant([
            'id' => BaseRecord::uuid(),
            'duty_setting_id' => $setting->id,
            'user_id' => $user->id,
        ]);
        $participant->coefficient = $coefficient;
        $participant->is_excluded = false;
        $participant->schedule_snapshot = [
            'capturedAt' => gmdate(DATE_ATOM),
            'coefficient' => $coefficient,
        ];
        if (!$participant->save()) {
            throw new BadRequestHttpException('Не удалось добавить участника.');
        }

        return $this->redirect(['view', 'id' => $setting->id]);
    }

    public function actionRemoveParticipant(string $id, string $participantId): Response
    {
        $this->assertManager();
        $setting = $this->findSetting($id);
        $participant = DutyParticipant::findOne([
            'id' => $participantId,
            'duty_setting_id' => $setting->id,
        ]);
        if (!$participant) {
            throw new NotFoundHttpException('Участник не найден.');
        }
        $participant->delete();
        return $this->redirect(['view', 'id' => $setting->id]);
    }

    public function actionGenerate(string $id): Response
    {
        $this->assertManager();
        $setting = $this->findSetting($id);
        try {
            $assignments = (new DutyAllocationService())->generate($setting);
            (new AuditService())->record(
                $this->workspaceId(),
                'duty.generated',
                $this->currentUser(),
                (string)$setting->id,
                ['assignments' => count($assignments)]
            );
            Yii::$app->session->setFlash('success', 'Дежурства распределены. Назначений: ' . count($assignments) . '.');
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }
        return $this->redirect(['view', 'id' => $setting->id]);
    }

    public function actionSwap(string $id): Response
    {
        $this->assertManager();
        $setting = $this->findSetting($id);
        $from = $this->findAssignment((string)Yii::$app->request->post('fromAssignmentId'), $setting);
        $to = $this->findAssignment((string)Yii::$app->request->post('toAssignmentId'), $setting);
        if ($from->id === $to->id) {
            throw new BadRequestHttpException('Выберите два разных назначения.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $fromUser = (string)$from->user_id;
            $fromShare = (float)$from->share_coefficient;
            $from->user_id = $to->user_id;
            $to->user_id = $fromUser;
            if ((string)$from->duty_date !== (string)$to->duty_date) {
                $from->share_coefficient = $to->share_coefficient;
                $to->share_coefficient = $fromShare;
            }
            if (!$from->save() || !$to->save()) {
                throw new RuntimeException('Не удалось обменять назначения.');
            }
            $swap = new DutySwap([
                'id' => BaseRecord::uuid(),
                'workspace_id' => $this->workspaceId(),
                'from_assignment_id' => $from->id,
                'to_assignment_id' => $to->id,
                'requested_by_id' => $this->currentUser()->id,
                'approved_by_id' => $this->currentUser()->id,
                'status' => 'approved',
            ]);
            if (!$swap->save()) {
                throw new RuntimeException('Не удалось записать обмен.');
            }
            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Назначения обменены.');
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
        return $this->redirect(['view', 'id' => $setting->id]);
    }

    public function actionDelete(string $id): Response
    {
        $this->assertManager();
        $setting = $this->findSetting($id);
        $setting->delete();
        Yii::$app->session->setFlash('success', 'План дежурств удалён.');
        return $this->redirect(['index']);
    }

    private function findSetting(string $id): DutySetting
    {
        $model = DutySetting::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('План дежурств не найден.');
        }
        return $model;
    }

    private function findAssignment(string $id, DutySetting $setting): DutyAssignment
    {
        $model = DutyAssignment::findOne(['id' => $id, 'duty_setting_id' => $setting->id]);
        if (!$model) {
            throw new NotFoundHttpException('Назначение дежурства не найдено.');
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
            throw new ForbiddenHttpException('Управлять дежурствами может только администратор.');
        }
    }
}
