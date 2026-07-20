<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Document;
use app\models\Revision;
use app\services\DocumentService;
use app\services\PermissionService;
use RuntimeException;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class RevisionController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'restore' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(string $documentId): string
    {
        $document = $this->findDocument($documentId);
        $permissions = new PermissionService();
        if (!$permissions->canReadDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Нет доступа к истории документа.');
        }

        $revisions = Revision::find()
            ->with('user')
            ->where(['document_id' => $document->id])
            ->orderBy(['revision_number' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'document' => $document,
            'revisions' => $revisions,
            'canUpdate' => $permissions->canUpdateDocument($this->currentUser(), $document),
        ]);
    }

    public function actionView(string $id): string
    {
        $revision = $this->findRevision($id);
        $document = $revision->document;
        $permissions = new PermissionService();
        if (!$permissions->canReadDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Нет доступа к этой версии документа.');
        }

        return $this->render('view', [
            'revision' => $revision,
            'document' => $document,
            'canUpdate' => $permissions->canUpdateDocument($this->currentUser(), $document),
        ]);
    }

    public function actionRestore(string $id): Response
    {
        $revision = $this->findRevision($id);
        $document = $revision->document;
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Недостаточно прав для восстановления версии.');
        }

        $document->title = (string)$revision->title;
        $document->content_json = $revision->content_json;
        $document->content_text = (string)$revision->content_text;
        $document->yjs_state = $revision->yjs_state;

        try {
            (new DocumentService())->save($document, $this->currentUser());
            Yii::$app->session->setFlash(
                'success',
                sprintf('Версия %d восстановлена как новая ревизия.', (int)$revision->revision_number)
            );
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }

        return $this->redirect(['/document/view', 'id' => $document->id]);
    }

    private function findDocument(string $id): Document
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
        return $document;
    }

    private function findRevision(string $id): Revision
    {
        $revision = Revision::find()
            ->with(['document.collection', 'user'])
            ->where(['id' => $id])
            ->one();
        if (!$revision || !$revision->document || $revision->document->workspace_id !== $this->workspaceId()) {
            throw new NotFoundHttpException('Версия документа не найдена.');
        }
        if ($revision->document->archived_at !== null || $revision->document->deleted_at !== null) {
            throw new NotFoundHttpException('Документ недоступен.');
        }
        return $revision;
    }
}
