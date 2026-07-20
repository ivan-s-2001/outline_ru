<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\models\Template;
use app\services\DocumentService;
use app\services\PermissionService;
use RuntimeException;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class TemplateController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $provider = new ActiveDataProvider([
            'query' => Template::find()
                ->with('creator')
                ->where(['workspace_id' => $this->workspaceId()])
                ->orderBy(['name' => SORT_ASC]),
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', [
            'provider' => $provider,
            'canCreate' => $this->currentUser()->role !== 'viewer',
        ]);
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
            'canManage' => $this->canManage($model),
            'canUse' => $this->currentUser()->role !== 'viewer',
        ]);
    }

    public function actionCreate(): Response|string
    {
        $this->assertCanCreate();
        $model = new Template([
            'workspace_id' => $this->workspaceId(),
            'created_by_id' => $this->currentUser()->id,
            'updated_by_id' => $this->currentUser()->id,
            'content_json' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'content_text' => '',
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Шаблон создан.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Новый шаблон',
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);
        $this->assertCanManage($model);

        if ($model->load(Yii::$app->request->post())) {
            $model->updated_by_id = $this->currentUser()->id;
            if ($model->save()) {
                Yii::$app->session->setFlash('success', 'Шаблон обновлён.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Редактирование шаблона',
        ]);
    }

    public function actionDelete(string $id): Response
    {
        $model = $this->findModel($id);
        $this->assertCanManage($model);
        $model->delete();
        Yii::$app->session->setFlash('success', 'Шаблон удалён.');
        return $this->redirect(['index']);
    }

    public function actionUse(string $id): Response|string
    {
        $template = $this->findModel($id);
        $this->assertCanCreate();
        $document = new Document([
            'title' => $template->name,
            'content_json' => $template->getContentData(),
            'content_text' => (string)$template->content_text,
        ]);

        if ($document->load(Yii::$app->request->post())) {
            try {
                $this->validateDocumentRelations($document);
                (new DocumentService())->save($document, $this->currentUser());
                Yii::$app->session->setFlash('success', 'Документ создан из шаблона.');
                return $this->redirect(['/document/view', 'id' => $document->id]);
            } catch (RuntimeException|BadRequestHttpException $error) {
                $document->addError('', $error->getMessage());
            }
        }

        return $this->render('use', [
            'template' => $template,
            'document' => $document,
            ...$this->documentOptions(),
        ]);
    }

    private function documentOptions(): array
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

        $parents = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $parents = array_values(array_filter(
            $parents,
            fn (Document $document): bool => $permissions->canUpdateDocument($this->currentUser(), $document)
        ));

        return [
            'collectionOptions' => ArrayHelper::map($collections, 'id', 'name'),
            'parentOptions' => ArrayHelper::map($parents, 'id', 'title'),
        ];
    }

    private function validateDocumentRelations(Document $document): void
    {
        $permissions = new PermissionService();
        if ($document->collection_id) {
            $collection = Collection::findOne([
                'id' => $document->collection_id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
            ]);
            if (!$collection || !$permissions->canUpdateCollection($this->currentUser(), $collection)) {
                throw new BadRequestHttpException('Выбранная коллекция недоступна для записи.');
            }
        }

        if ($document->parent_document_id) {
            $parent = Document::find()
                ->with('collection')
                ->where([
                    'id' => $document->parent_document_id,
                    'workspace_id' => $this->workspaceId(),
                    'archived_at' => null,
                    'deleted_at' => null,
                ])
                ->one();
            if (!$parent || !$permissions->canUpdateDocument($this->currentUser(), $parent)) {
                throw new BadRequestHttpException('Родительский документ недоступен для записи.');
            }
            if ($document->collection_id && $parent->collection_id !== $document->collection_id) {
                throw new BadRequestHttpException('Родитель и новый документ должны находиться в одной коллекции.');
            }
            $document->collection_id ??= $parent->collection_id;
        }
    }

    private function assertCanCreate(): void
    {
        if ($this->currentUser()->role === 'viewer') {
            throw new ForbiddenHttpException('Наблюдатель не может создавать документы и шаблоны.');
        }
    }

    private function canManage(Template $model): bool
    {
        return $this->currentUser()->isAdmin() || $model->created_by_id === $this->currentUser()->id;
    }

    private function assertCanManage(Template $model): void
    {
        if (!$this->canManage($model)) {
            throw new ForbiddenHttpException('Изменять шаблон может его автор или администратор.');
        }
    }

    private function findModel(string $id): Template
    {
        $model = Template::find()
            ->with('creator')
            ->where(['id' => $id, 'workspace_id' => $this->workspaceId()])
            ->one();
        if (!$model) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        return $model;
    }
}
