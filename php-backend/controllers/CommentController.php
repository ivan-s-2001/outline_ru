<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Comment;
use app\models\Document;
use app\services\PermissionService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class CommentController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['POST'],
                    'update' => ['POST'],
                    'resolve' => ['POST'],
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionCreate(string $documentId, ?string $parentId = null): Response
    {
        $document = $this->findDocument($documentId);
        if (!(new PermissionService())->canReadDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Нет доступа к документу.');
        }

        if ($parentId) {
            $parent = $this->findComment($parentId);
            if ($parent->document_id !== $document->id) {
                throw new BadRequestHttpException('Ответ относится к другому документу.');
            }
        }

        $comment = new Comment([
            'workspace_id' => $this->workspaceId(),
            'document_id' => $document->id,
            'parent_comment_id' => $parentId,
            'user_id' => $this->currentUser()->id,
            'data' => Yii::$app->request->post('data', []),
        ]);
        $comment->load(Yii::$app->request->post());

        if (!$comment->save()) {
            Yii::$app->session->setFlash('error', $this->firstError($comment->getFirstErrors()));
        } else {
            Yii::$app->session->setFlash('success', $parentId ? 'Ответ добавлен.' : 'Комментарий добавлен.');
        }

        return $this->redirect(['/document/view', 'id' => $document->id, '#' => 'comments']);
    }

    public function actionUpdate(string $id): Response
    {
        $comment = $this->findComment($id);
        $this->assertCanManage($comment);

        $text = trim((string)Yii::$app->request->post('text', Yii::$app->request->post('Comment')['text'] ?? ''));
        $comment->text = $text;
        if (!$comment->save()) {
            Yii::$app->session->setFlash('error', $this->firstError($comment->getFirstErrors()));
        } else {
            Yii::$app->session->setFlash('success', 'Комментарий обновлён.');
        }

        return $this->redirect(['/document/view', 'id' => $comment->document_id, '#' => 'comments']);
    }

    public function actionResolve(string $id): Response
    {
        $comment = $this->findComment($id);
        $document = $this->findDocument((string)$comment->document_id);
        $permissions = new PermissionService();
        if (
            $comment->user_id !== $this->currentUser()->id &&
            !$permissions->canUpdateDocument($this->currentUser(), $document) &&
            !$this->currentUser()->isAdmin()
        ) {
            throw new ForbiddenHttpException('Недостаточно прав для завершения обсуждения.');
        }

        $comment->updateAttributes([
            'resolved_at' => $comment->resolved_at ? null : gmdate('Y-m-d H:i:s.u'),
            'updated_at' => gmdate('Y-m-d H:i:s.u'),
        ]);

        return $this->redirect(['/document/view', 'id' => $comment->document_id, '#' => 'comments']);
    }

    public function actionDelete(string $id): Response
    {
        $comment = $this->findComment($id);
        $this->assertCanManage($comment);
        $documentId = (string)$comment->document_id;
        $comment->delete();
        Yii::$app->session->setFlash('success', 'Комментарий удалён.');

        return $this->redirect(['/document/view', 'id' => $documentId, '#' => 'comments']);
    }

    private function assertCanManage(Comment $comment): void
    {
        if ($comment->user_id !== $this->currentUser()->id && !$this->currentUser()->isAdmin()) {
            throw new ForbiddenHttpException('Можно изменять только свои комментарии.');
        }
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

    private function findComment(string $id): Comment
    {
        $comment = Comment::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
        ]);
        if (!$comment) {
            throw new NotFoundHttpException('Комментарий не найден.');
        }
        return $comment;
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить комментарий.';
    }
}
