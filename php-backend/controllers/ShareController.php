<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Document;
use app\models\Share;
use app\services\PermissionService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class ShareController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'save' => ['POST'],
                    'revoke' => ['POST'],
                ],
            ],
        ];
    }

    public function actionManage(string $documentId): string
    {
        $document = $this->findWritableDocument($documentId);
        $share = Share::findOne(['document_id' => $document->id]);

        return $this->render('manage', [
            'document' => $document,
            'share' => $share,
        ]);
    }

    public function actionSave(string $documentId): Response
    {
        $document = $this->findWritableDocument($documentId);
        $share = Share::findOne(['document_id' => $document->id]);
        if (!$share) {
            $share = new Share([
                'document_id' => $document->id,
                'created_by_id' => $this->currentUser()->id,
                'token' => bin2hex(random_bytes(32)),
            ]);
        }

        $share->is_published = filter_var(
            Yii::$app->request->post('isPublished', true),
            FILTER_VALIDATE_BOOL
        );
        $share->include_child_documents = filter_var(
            Yii::$app->request->post('includeChildDocuments', false),
            FILTER_VALIDATE_BOOL
        );

        if ($share->save()) {
            Yii::$app->session->setFlash('success', 'Настройки публичной ссылки сохранены.');
        } else {
            Yii::$app->session->setFlash('error', $this->firstError($share->getFirstErrors()));
        }

        return $this->redirect(['/share/manage', 'documentId' => $document->id]);
    }

    public function actionRevoke(string $id): Response
    {
        $share = Share::find()->with('document.collection')->where(['id' => $id])->one();
        if (!$share || !$share->document || $share->document->workspace_id !== $this->workspaceId()) {
            throw new NotFoundHttpException('Публичная ссылка не найдена.');
        }
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $share->document)) {
            throw new ForbiddenHttpException('Недостаточно прав для отзыва ссылки.');
        }

        $documentId = (string)$share->document_id;
        $share->delete();
        Yii::$app->session->setFlash('success', 'Публичная ссылка отозвана.');

        return $this->redirect(['/share/manage', 'documentId' => $documentId]);
    }

    private function findWritableDocument(string $id): Document
    {
        $document = Document::find()
            ->with('collection')
            ->where([
                'id' => $id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Недостаточно прав для публикации документа.');
        }
        return $document;
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить публичную ссылку.';
    }
}
