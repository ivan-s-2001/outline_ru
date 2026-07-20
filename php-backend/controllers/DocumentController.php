<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\models\Revision;
use app\services\DocumentService;
use RuntimeException;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
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
        $revisions = Revision::find()
            ->where(['document_id' => $model->id])
            ->orderBy(['revision_number' => SORT_DESC])
            ->limit(10)
            ->all();

        return $this->render('view', [
            'model' => $model,
            'revisions' => $revisions,
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);
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
        $collections = Collection::find()
            ->where(['workspace_id' => $this->workspaceId(), 'archived_at' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        $parentQuery = Document::find()
            ->where([
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['title' => SORT_ASC]);
        if (!$model->isNewRecord) {
            $parentQuery->andWhere(['<>', 'id', $model->id]);
        }

        return [
            'model' => $model,
            'title' => $title,
            'collectionOptions' => ArrayHelper::map($collections, 'id', 'name'),
            'parentOptions' => ArrayHelper::map($parentQuery->all(), 'id', 'title'),
        ];
    }

    private function validateRelations(Document $model): void
    {
        if ($model->collection_id) {
            $collectionExists = Collection::find()->where([
                'id' => $model->collection_id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
            ])->exists();
            if (!$collectionExists) {
                throw new BadRequestHttpException('Выбранная коллекция недоступна.');
            }
        }

        if ($model->parent_document_id) {
            if ($model->parent_document_id === $model->id) {
                throw new BadRequestHttpException('Документ не может быть родителем самому себе.');
            }
            $parentExists = Document::find()->where([
                'id' => $model->parent_document_id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])->exists();
            if (!$parentExists) {
                throw new BadRequestHttpException('Родительский документ недоступен.');
            }
        }
    }

    private function findModel(string $id): Document
    {
        $model = Document::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'archived_at' => null,
            'deleted_at' => null,
        ]);
        if (!$model) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $model;
    }
}
