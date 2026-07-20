<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\ApiKey;
use app\models\forms\ApiKeyForm;
use app\services\ApiKeyService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class ApiKeyController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'revoke' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => ApiKey::find()
                ->where([
                    'workspace_id' => $this->workspaceId(),
                    'user_id' => $this->currentUser()->id,
                ])
                ->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'model' => new ApiKeyForm(),
            'secret' => Yii::$app->session->getFlash('apiKeySecret'),
        ]);
    }

    public function actionCreate(): Response
    {
        $form = new ApiKeyForm();
        if (!$form->load(Yii::$app->request->post()) || !$form->validate()) {
            Yii::$app->session->setFlash('error', $this->firstError($form->getFirstErrors()));
            return $this->redirect(['index']);
        }

        try {
            [, $secret] = (new ApiKeyService())->create(
                $this->currentUser(),
                $form->name,
                $form->permission,
                $form->expiresInDays
            );
            Yii::$app->session->setFlash('apiKeySecret', $secret);
            Yii::$app->session->setFlash('success', 'API-ключ создан. Скопируйте секрет сейчас.');
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }

        return $this->redirect(['index']);
    }

    public function actionRevoke(string $id): Response
    {
        $model = ApiKey::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
        ]);
        if (!$model) {
            throw new NotFoundHttpException('API-ключ не найден.');
        }
        if ($model->user_id !== $this->currentUser()->id && !$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Недостаточно прав для отзыва этого ключа.');
        }

        (new ApiKeyService())->revoke($model, $this->currentUser());
        Yii::$app->session->setFlash('success', 'API-ключ отозван.');
        return $this->redirect(['index']);
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Проверьте параметры API-ключа.';
    }
}
