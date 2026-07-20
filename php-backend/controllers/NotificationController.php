<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Notification;
use app\services\NotificationService;
use app\services\PermissionService;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class NotificationController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'read' => ['POST'],
                    'read-all' => ['POST'],
                    'archive' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $notifications = Notification::find()
            ->with(['actor', 'document', 'document.collection', 'comment'])
            ->where([
                'workspace_id' => $this->workspaceId(),
                'user_id' => $this->currentUser()->id,
                'archived_at' => null,
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(200)
            ->all();

        $permissions = new PermissionService();
        $notifications = array_values(array_filter(
            $notifications,
            function (Notification $notification) use ($permissions): bool {
                if (!$notification->document_id) {
                    return true;
                }
                return $notification->document !== null
                    && $permissions->canReadDocument($this->currentUser(), $notification->document);
            }
        ));

        return $this->render('index', [
            'provider' => new ArrayDataProvider([
                'allModels' => $notifications,
                'pagination' => ['pageSize' => 40],
            ]),
            'unreadCount' => count(array_filter(
                $notifications,
                static fn (Notification $notification): bool => $notification->isUnread()
            )),
        ]);
    }

    public function actionRead(string $id): Response
    {
        $notification = $this->findModel($id);
        if ($notification->read_at === null) {
            $notification->updateAttributes([
                'read_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            ]);
        }

        $target = $notification->document_id
            ? ['/document/view', 'id' => $notification->document_id, '#' => $notification->comment_id ? 'comments' : null]
            : ['/notification/index'];
        return $this->redirect($target);
    }

    public function actionReadAll(): Response
    {
        (new NotificationService())->markAllRead($this->currentUser());
        Yii::$app->session->setFlash('success', 'Все уведомления отмечены прочитанными.');
        return $this->redirect(['index']);
    }

    public function actionArchive(string $id): Response
    {
        $notification = $this->findModel($id);
        $notification->updateAttributes([
            'archived_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            'read_at' => $notification->read_at ?: new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        return $this->redirect(['index']);
    }

    private function findModel(string $id): Notification
    {
        $notification = Notification::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'user_id' => $this->currentUser()->id,
        ]);
        if (!$notification) {
            throw new NotFoundHttpException('Уведомление не найдено.');
        }
        if ((string)$notification->user_id !== (string)$this->currentUser()->id) {
            throw new ForbiddenHttpException('Нет доступа к уведомлению.');
        }
        return $notification;
    }
}
