<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AdminController;
use app\models\BaseRecord;
use app\models\Webhook;
use app\models\WebhookDelivery;
use app\services\AuditService;
use app\services\UrlGuard;
use app\services\WebhookService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class WebhookController extends AdminController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                    'test' => ['POST'],
                    'rotate' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'provider' => new ActiveDataProvider([
                'query' => Webhook::find()
                    ->where(['workspace_id' => $this->workspaceId()])
                    ->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
            'provider' => new ActiveDataProvider([
                'query' => WebhookDelivery::find()
                    ->where(['webhook_id' => $model->id])
                    ->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 50],
            ]),
        ]);
    }

    public function actionCreate(?string $id = null): Response|string
    {
        $model = $id ? $this->findModel($id) : new Webhook([
            'workspace_id' => $this->workspaceId(),
            'events' => ['*'],
            'is_active' => true,
            'created_by_id' => $this->currentUser()->id,
            'secret_encrypted' => 'pending',
        ]);
        $eventsText = implode(', ', $model->getEventsValue());

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            $eventsText = trim((string)Yii::$app->request->post('eventsText', '*'));
            $events = array_values(array_unique(array_filter(array_map(
                static fn (string $event): string => trim($event),
                preg_split('/[\s,]+/', $eventsText) ?: []
            ))));
            $model->events = $events ?: ['*'];
            try {
                (new UrlGuard())->assertPublicHttpUrl((string)$model->url);
                $newSecret = null;
                if ($model->isNewRecord) {
                    $newSecret = (new WebhookService())->generateSecret();
                    $model->secret_encrypted = (new WebhookService())->encryptSecret($newSecret);
                }
                if ($model->save()) {
                    if ($newSecret !== null) {
                        Yii::$app->session->setFlash('webhookSecret', $newSecret);
                    }
                    Yii::$app->session->setFlash('success', 'Webhook сохранён.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
            } catch (RuntimeException $error) {
                $model->addError('url', $error->getMessage());
            }
        }

        return $this->render('form', ['model' => $model, 'eventsText' => $eventsText]);
    }

    public function actionRotate(string $id): Response
    {
        $model = $this->findModel($id);
        $secret = (new WebhookService())->generateSecret();
        $model->updateAttributes([
            'secret_encrypted' => (new WebhookService())->encryptSecret($secret),
            'updated_at' => gmdate('Y-m-d H:i:s.u'),
        ]);
        Yii::$app->session->setFlash('webhookSecret', $secret);
        Yii::$app->session->setFlash('success', 'Секрет webhook обновлён.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionTest(string $id): Response
    {
        $model = $this->findModel($id);
        $delivery = new WebhookDelivery([
            'id' => BaseRecord::uuid(),
            'webhook_id' => $model->id,
            'event_name' => 'webhook.test',
            'payload' => [
                'id' => BaseRecord::uuid(),
                'event' => 'webhook.test',
                'workspaceId' => $this->workspaceId(),
                'actorId' => $this->currentUser()->id,
                'data' => ['message' => 'Тестовая доставка Outline Yii'],
                'createdAt' => gmdate(DATE_ATOM),
            ],
            'status' => 'pending',
            'attempts' => 0,
            'next_attempt_at' => gmdate('Y-m-d H:i:s.u'),
        ]);
        if (!$delivery->save()) {
            throw new BadRequestHttpException('Не удалось создать тестовую доставку.');
        }
        try {
            (new WebhookService())->deliver($delivery);
            Yii::$app->session->setFlash('success', 'Тестовый webhook доставлен.');
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionDelete(string $id): Response
    {
        $model = $this->findModel($id);
        $model->delete();
        (new AuditService())->record($this->workspaceId(), 'webhook.deleted', $this->currentUser(), $id);
        Yii::$app->session->setFlash('success', 'Webhook удалён.');
        return $this->redirect(['index']);
    }

    private function findModel(string $id): Webhook
    {
        $model = Webhook::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('Webhook не найден.');
        }
        return $model;
    }
}
