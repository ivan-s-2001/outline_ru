<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\services\PermissionService;
use Yii;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class CollectionController extends AuthenticatedController
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

    public function actionIndex(): string
    {
        $permissions = new PermissionService();
        $models = Collection::find()
            ->where(['workspace_id' => $this->workspaceId(), 'archived_at' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $models = array_values(array_filter(
            $models,
            fn (Collection $collection): bool => $permissions->canReadCollection($this->currentUser(), $collection)
        ));

        $provider = new ArrayDataProvider([
            'allModels' => $models,
            'pagination' => ['pageSize' => 30],
        ]);

        return $this->render('index', ['provider' => $provider]);
    }

    public function actionCreate(): Response|string
    {
        $model = new Collection([
            'workspace_id' => $this->workspaceId(),
            'created_by_id' => $this->currentUser()->id,
            'permission' => 'read_write',
            'color' => '#4E5C6E',
        ]);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Коллекция создана.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Новая коллекция',
        ]);
    }

    public function actionView(string $id): string
    {
        $model = $this->findModel($id);
        $permissions = new PermissionService();
        if (!$permissions->canReadCollection($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Нет доступа к коллекции.');
        }

        $documents = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'collection_id' => $model->id,
                'parent_document_id' => null,
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])
            ->all();
        $documents = array_values(array_filter(
            $documents,
            fn (Document $document): bool => $permissions->canReadDocument($this->currentUser(), $document)
        ));

        return $this->render('view', [
            'model' => $model,
            'documents' => new ArrayDataProvider([
                'allModels' => $documents,
                'pagination' => false,
            ]),
            'canUpdate' => $permissions->canUpdateCollection($this->currentUser(), $model),
        ]);
    }

    public function actionUpdate(string $id): Response|string
    {
        $model = $this->findModel($id);
        if (!(new PermissionService())->canUpdateCollection($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Недостаточно прав для изменения коллекции.');
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Коллекция обновлена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('form', [
            'model' => $model,
            'title' => 'Настройки коллекции',
        ]);
    }

    public function actionArchive(string $id): Response
    {
        $model = $this->findModel($id);
        if (!(new PermissionService())->canUpdateCollection($this->currentUser(), $model)) {
            throw new ForbiddenHttpException('Недостаточно прав для архивирования коллекции.');
        }

        $model->updateAttributes(['archived_at' => gmdate('Y-m-d H:i:s.u')]);
        Yii::$app->session->setFlash('success', 'Коллекция перемещена в архив.');
        return $this->redirect(['index']);
    }

    private function findModel(string $id): Collection
    {
        $model = Collection::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'archived_at' => null,
        ]);
        if (!$model) {
            throw new NotFoundHttpException('Коллекция не найдена.');
        }
        return $model;
    }
}
