<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Attachment;
use app\models\BaseRecord;
use app\models\Document;
use app\models\Favorite;
use app\services\AttachmentStorage;
use app\services\AuditService;
use app\services\PermissionService;
use RuntimeException;
use Throwable;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Expression;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class LibraryController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'toggle-favorite' => ['POST'],
                    'trash-document' => ['POST'],
                    'restore' => ['POST'],
                    'delete-forever' => ['POST'],
                ],
            ],
        ];
    }

    public function actionFavorites(): string
    {
        $favorites = Favorite::find()
            ->with(['document', 'document.collection'])
            ->where([
                'workspace_id' => $this->workspaceId(),
                'user_id' => $this->currentUser()->id,
            ])
            ->orderBy(['sort_order' => SORT_ASC, 'created_at' => SORT_DESC])
            ->all();
        $permissions = new PermissionService();
        $documents = [];
        foreach ($favorites as $favorite) {
            $document = $favorite->document;
            if (
                $document &&
                $document->archived_at === null &&
                $document->deleted_at === null &&
                $permissions->canReadDocument($this->currentUser(), $document)
            ) {
                $documents[] = $document;
            }
        }
        return $this->render('list', $this->listParams('Избранное', $documents, 'В избранном пока нет документов.', 'favorites'));
    }

    public function actionArchive(): string
    {
        $documents = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'deleted_at' => null,
            ])
            ->andWhere(['is not', 'archived_at', null])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();
        $documents = $this->readable($documents);
        return $this->render('list', $this->listParams('Архив', $documents, 'В архиве нет документов.', 'archive'));
    }

    public function actionTrash(): string
    {
        $documents = Document::find()
            ->with('collection')
            ->where(['workspace_id' => $this->workspaceId()])
            ->andWhere(['is not', 'deleted_at', null])
            ->orderBy(['deleted_at' => SORT_DESC])
            ->all();
        $documents = $this->manageable($documents);
        return $this->render('list', $this->listParams('Корзина', $documents, 'Корзина пуста.', 'trash'));
    }

    public function actionToggleFavorite(string $id): Response
    {
        $document = $this->findActive($id);
        if (!(new PermissionService())->canReadDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Нет доступа к документу.');
        }
        $favorite = Favorite::findOne([
            'workspace_id' => $this->workspaceId(),
            'user_id' => $this->currentUser()->id,
            'document_id' => $document->id,
        ]);
        if ($favorite) {
            $favorite->delete();
            Yii::$app->session->setFlash('success', 'Документ удалён из избранного.');
        } else {
            $favorite = new Favorite([
                'id' => BaseRecord::uuid(),
                'workspace_id' => $this->workspaceId(),
                'user_id' => $this->currentUser()->id,
                'document_id' => $document->id,
            ]);
            if (!$favorite->save()) {
                throw new RuntimeException('Не удалось добавить документ в избранное.');
            }
            Yii::$app->session->setFlash('success', 'Документ добавлен в избранное.');
        }
        return $this->redirect(['/document/view', 'id' => $document->id]);
    }

    public function actionTrashDocument(string $id): Response
    {
        $document = $this->findAny($id);
        $this->assertCanUpdate($document);
        $ids = array_merge([(string)$document->id], $this->descendantIds((string)$document->id));
        Document::updateAll([
            'deleted_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            'updated_by_id' => $this->currentUser()->id,
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ], ['id' => $ids, 'workspace_id' => $this->workspaceId()]);
        (new AuditService())->record($this->workspaceId(), 'document.trashed', $this->currentUser(), (string)$document->id, ['documents' => count($ids)]);
        Yii::$app->session->setFlash('success', 'Документ и дочерние документы перемещены в корзину.');
        return $this->redirect(['trash']);
    }

    public function actionRestore(string $id): Response
    {
        $document = $this->findAny($id);
        $this->assertCanUpdate($document);
        $document->updateAttributes([
            'deleted_at' => null,
            'archived_at' => null,
            'updated_by_id' => $this->currentUser()->id,
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        Yii::$app->session->setFlash('success', 'Документ восстановлен.');
        return $this->redirect(['/document/view', 'id' => $document->id]);
    }

    public function actionDeleteForever(string $id): Response
    {
        $document = $this->findAny($id);
        if ($document->deleted_at === null) {
            throw new ForbiddenHttpException('Окончательно удалять можно только документы из корзины.');
        }
        $this->assertCanUpdate($document);
        $ids = array_merge([(string)$document->id], $this->descendantIds((string)$document->id));
        $attachments = Attachment::find()->where(['document_id' => $ids])->all();
        $storage = new AttachmentStorage();
        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($attachments as $attachment) {
                $storage->delete($attachment);
                $attachment->delete();
            }
            Document::deleteAll(['id' => $ids, 'workspace_id' => $this->workspaceId()]);
            $transaction->commit();
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
        (new AuditService())->record($this->workspaceId(), 'document.deleted', $this->currentUser(), (string)$document->id, ['documents' => count($ids)]);
        Yii::$app->session->setFlash('success', 'Документ окончательно удалён.');
        return $this->redirect(['trash']);
    }

    private function listParams(string $title, array $documents, string $emptyText, string $mode): array
    {
        return [
            'title' => $title,
            'mode' => $mode,
            'emptyText' => $emptyText,
            'provider' => new ArrayDataProvider([
                'allModels' => $documents,
                'pagination' => ['pageSize' => 40],
            ]),
        ];
    }

    private function readable(array $documents): array
    {
        $permissions = new PermissionService();
        return array_values(array_filter(
            $documents,
            fn (Document $document): bool => $permissions->canReadDocument($this->currentUser(), $document)
        ));
    }

    private function manageable(array $documents): array
    {
        $permissions = new PermissionService();
        return array_values(array_filter(
            $documents,
            fn (Document $document): bool => $permissions->canUpdateDocument($this->currentUser(), $document)
        ));
    }

    private function assertCanUpdate(Document $document): void
    {
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Недостаточно прав для изменения документа.');
        }
    }

    private function findActive(string $id): Document
    {
        $document = Document::find()->with('collection')->where([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'archived_at' => null,
            'deleted_at' => null,
        ])->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $document;
    }

    private function findAny(string $id): Document
    {
        $document = Document::find()->with('collection')->where([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
        ])->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $document;
    }

    /** @return string[] */
    private function descendantIds(string $rootId): array
    {
        $result = [];
        $pending = [$rootId];
        while ($pending) {
            $children = Document::find()
                ->select('id')
                ->where(['workspace_id' => $this->workspaceId(), 'parent_document_id' => $pending])
                ->column();
            $children = array_values(array_diff(array_map('strval', $children), $result));
            if (!$children) {
                break;
            }
            $result = array_values(array_unique(array_merge($result, $children)));
            $pending = $children;
            if (count($result) > 10000) {
                throw new RuntimeException('Слишком большое дерево документов для одной операции.');
            }
        }
        return $result;
    }
}
