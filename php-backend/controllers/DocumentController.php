<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\models\Revision;
use app\services\DocumentService;
use app\services\PermissionService;
use RuntimeException;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class DocumentController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'archive' => ['POST'],
                ],
            ],
        ];
    }

    public function actionCreate(?string $collectionId = null, ?string $parentId = null): Response|string
    {
        if ($parentId) {
            $parent = $this->findModel($parentId);
            if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $parent)) {
                throw new ForbiddenHttpException('Недостаточно прав для создания дочернего документа.');
            }
            $collectionId ??= $parent->collection_id;
        }

        if ($collectionId) {
            $collection = $this->findCollection($collectionId);
            if (!(new PermissionService())->canUpdateCollection($this->currentUser(), $collection)) {
                throw new ForbiddenHttpException('Недостаточно прав для создания документа в этой коллекции.');
            }
        }

        $model = new Document([
            'collection_id' => $collectionId,
            'parent_document_id' => $parentId,
            'title' => '',
            'content_text' => '',
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
        ]);

        if ($model->load(Yii::$app->request->post())) {
            try {
                $this->validateRelations($model);
                (new DocumentService())->save($model, $this->currentUser());
                Yii::$app->session->setFlash('success', 'Документ создан.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (RuntimeException|BadRequestHttpException $error) {
                $model->addError('', $error->getMessage());
            }
        }

        return $this->render('form', $this->formParams($model, 'Новый документ'));
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        $permissions = new PermissionService();
        if (!$permissions->canReadDocument($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Нет доступа к документу.');
        }

        $revisions = Revision::find()
            ->where(['document_id' => $model->id])
            ->orderBy(['revision_number' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('view', [
            'model' => $model,
            'revisions' => $revisions,
            'canUpdate' => $permissions->canUpdateDocument($this->currentUser(), $model),
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Недостаточно прав для изменения документа.');
        }

        if ($model->load(Yii::$app->request->post())) {
            try {
                $this->validateRelations($model);
                (new DocumentService())->save($model, $this->currentUser());
                Yii::$app->session->setFlash('success', 'Документ сохранён.');
                return $this->redirect(['view', 'id' => $model->id]);
            } catch (RuntimeException|BadRequestHttpException $error) {
                $model->addError('', $error->getMessage());
            }
        }

        return $this->render('form', $this->formParams($model, 'Редактирование документа'));
    }

    public function actionArchive(string $id): Response
    {
        $model = $this->findModel($id);
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Недостаточно прав для архивирования документа.');
        }

        $model->updateAttributes([
            'archived_at' => gmdate('Y-m-d H:i:s.u'),
            'updated_by_id' => $this->currentUser()->id,
            'updated_at' => gmdate('Y-m-d H:i:s.u'),
        ]);
        Yii::$app->session->setFlash('success', 'Документ перемещён в архив.');

        return $model->collection_id
            ? $this->redirect(['/collection/view', 'id' => $model->collection_id])
            : $this->redirect(['/site/dashboard']);
    }

    private function formParams(Document $model, string $title): array
    {
        $permissions = new PermissionService();
        $collections = Collection::find()
            ->where(['workspace_id' => $this->workspaceId(), 'archived_at' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $collections = array_values(array_filter(
            $collections,
            fn (Collection $collection): bool => $permissions->canUpdateCollection($this->currentUser(), $collection)
        ));

        $parentQuery = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['title' => SORT_ASC]);
        if (!$model->isNewRecord) {
            $parentQuery->andWhere(['<>', 'id', $model->id]);
        }
        $parents = array_values(array_filter(
            $parentQuery->all(),
            fn (Document $document): bool => $permissions->canUpdateDocument($this->currentUser(), $document)
        ));

        return [
            'model' => $model,
            'title' => $title,
            'collectionOptions' => ArrayHelper::map($collections, 'id', 'name'),
            'parentOptions' => ArrayHelper::map($parents, 'id', 'title'),
        ];
    }

    private function validateRelations(Document $model): void
    {
        $permissions = new PermissionService();
        if ($model->collection_id) {
            $collection = $this->findCollection((string)$model->collection_id);
            if (!$permissions->canUpdateCollection($this->currentUser(), $collection)) {
                throw new BadRequestHttpException('Выбранная коллекция недоступна для записи.');
            }
        }

        if ($model->parent_document_id) {
            if ($model->parent_document_id === $model->id) {
                throw new BadRequestHttpException('Документ не может быть родителем самому себе.');
            }
            $parent = $this->findModel((string)$model->parent_document_id);
            if (!$permissions->canUpdateDocument($this->currentUser(), $parent)) {
                throw new BadRequestHttpException('Родительский документ недоступен для записи.');
            }
            if ($model->collection_id && $parent->collection_id !== $model->collection_id) {
                throw new BadRequestHttpException('Родитель и дочерний документ должны находиться в одной коллекции.');
            }
        }
    }

    private function findCollection(string $id): Collection
    {
        $collection = Collection::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'archived_at' => null,
        ]);
        if (!$collection) {
            throw new NotFoundHttpException('Коллекция не найдена.');
        }
        return $collection;
    }

    private function findModel(string $id): Document
    {
        $model = Document::find()
            ->with('collection')
            ->where([
                'id' => $id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->one();
        if (!$model) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $model;
    }
}
