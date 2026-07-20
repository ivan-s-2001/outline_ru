<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AdminController;
use app\models\Invitation;
use app\models\forms\InviteForm;
use app\services\InvitationService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class InvitationController extends AdminController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => ['revoke' => ['POST']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        return $this->render('index', [
            'provider' => new ActiveDataProvider([
                'query' => Invitation::find()
                    ->with('inviter')
                    ->where(['workspace_id' => $this->workspaceId()])
                    ->orderBy(['created_at' => SORT_DESC]),
                'pagination' => ['pageSize' => 40],
            ]),
        ]);
    }

    public function actionCreate(): Response|string
    {
        $form = new InviteForm();
        if ($form->load(Yii::$app->request->post()) && $form->validate()) {
            try {
                (new InvitationService())->issue($form, $this->currentUser());
                Yii::$app->session->setFlash('success', 'Приглашение отправлено.');
                return $this->redirect(['index']);
            } catch (RuntimeException $error) {
                $form->addError('email', $error->getMessage());
            }
        }
        return $this->render('form', ['model' => $form]);
    }

    public function actionRevoke(string $id): Response
    {
        $model = Invitation::findOne(['id' => $id, 'workspace_id' => $this->workspaceId()]);
        if (!$model) {
            throw new NotFoundHttpException('Приглашение не найдено.');
        }
        if ($model->accepted_at === null) {
            $model->updateAttributes([
                'revoked_at' => new Expression('CURRENT_TIMESTAMP(6)'),
                'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            ]);
        }
        Yii::$app->session->setFlash('success', 'Приглашение отозвано.');
        return $this->redirect(['index']);
    }
}
