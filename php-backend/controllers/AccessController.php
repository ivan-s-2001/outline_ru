<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\CollectionPermission;
use app\models\Document;
use app\models\DocumentPermission;
use app\models\Group;
use app\models\User;
use app\services\PermissionService;
use Yii;
use yii\db\ActiveRecord;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class AccessController extends AuthenticatedController
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'grant-collection' => ['POST'],
                    'revoke-collection' => ['POST'],
                    'grant-document' => ['POST'],
                    'revoke-document' => ['POST'],
                ],
            ],
        ];
    }

    public function actionCollection(string $id): string
    {
        $collection = $this->findCollection($id);
        $this->assertCanUpdateCollection($collection);

        $permissions = CollectionPermission::find()
            ->with(['user', 'group'])
            ->where(['collection_id' => $collection->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return $this->render('manage', $this->viewParams(
            'collection',
            (string)$collection->id,
            (string)$collection->name,
            (string)$collection->permission,
            $permissions,
            ['/collection/view', 'id' => $collection->id]
        ));
    }

    public function actionGrantCollection(string $id): Response
    {
        $collection = $this->findCollection($id);
        $this->assertCanUpdateCollection($collection);

        $permission = $this->upsertPermission(
            CollectionPermission::class,
            'collection_id',
            (string)$collection->id
        );
        Yii::$app->session->setFlash('success', 'Доступ к коллекции обновлён.');

        return $this->redirect(['collection', 'id' => $permission->collection_id]);
    }

    public function actionRevokeCollection(string $id, string $permissionId): Response
    {
        $collection = $this->findCollection($id);
        $this->assertCanUpdateCollection($collection);
        $permission = CollectionPermission::findOne([
            'id' => $permissionId,
            'collection_id' => $collection->id,
        ]);
        if (!$permission) {
            throw new NotFoundHttpException('Разрешение не найдено.');
        }
        $permission->delete();
        Yii::$app->session->setFlash('success', 'Индивидуальное разрешение удалено.');

        return $this->redirect(['collection', 'id' => $collection->id]);
    }

    public function actionDocument(string $id): string
    {
        $document = $this->findDocument($id);
        $this->assertCanUpdateDocument($document);

        $permissions = DocumentPermission::find()
            ->with(['user', 'group'])
            ->where(['document_id' => $document->id])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        return $this->render('manage', $this->viewParams(
            'document',
            (string)$document->id,
            (string)($document->title ?: 'Без названия'),
            $document->collection?->permission ?? 'none',
            $permissions,
            ['/document/view', 'id' => $document->id]
        ));
    }

    public function actionGrantDocument(string $id): Response
    {
        $document = $this->findDocument($id);
        $this->assertCanUpdateDocument($document);

        $permission = $this->upsertPermission(
            DocumentPermission::class,
            'document_id',
            (string)$document->id
        );
        Yii::$app->session->setFlash('success', 'Доступ к документу обновлён.');

        return $this->redirect(['document', 'id' => $permission->document_id]);
    }

    public function actionRevokeDocument(string $id, string $permissionId): Response
    {
        $document = $this->findDocument($id);
        $this->assertCanUpdateDocument($document);
        $permission = DocumentPermission::findOne([
            'id' => $permissionId,
            'document_id' => $document->id,
        ]);
        if (!$permission) {
            throw new NotFoundHttpException('Разрешение не найдено.');
        }
        $permission->delete();
        Yii::$app->session->setFlash('success', 'Индивидуальное разрешение удалено.');

        return $this->redirect(['document', 'id' => $document->id]);
    }

    /**
     * @param class-string<CollectionPermission|DocumentPermission> $modelClass
     */
    private function upsertPermission(string $modelClass, string $resourceColumn, string $resourceId): ActiveRecord
    {
        $subjectType = (string)Yii::$app->request->post('subjectType', '');
        $subjectId = trim((string)Yii::$app->request->post('subjectId', ''));
        $permissionValue = (string)Yii::$app->request->post('permission', 'read');

        if (!in_array($subjectType, ['user', 'group'], true)) {
            throw new BadRequestHttpException('Выберите пользователя или группу.');
        }
        if (!in_array($permissionValue, ['none', 'read', 'read_write'], true)) {
            throw new BadRequestHttpException('Некорректный уровень доступа.');
        }

        if ($subjectType === 'user') {
            $subject = User::findOne([
                'id' => $subjectId,
                'workspace_id' => $this->workspaceId(),
            ]);
        } else {
            $subject = Group::findOne([
                'id' => $subjectId,
                'workspace_id' => $this->workspaceId(),
            ]);
        }
        if (!$subject) {
            throw new BadRequestHttpException('Пользователь или группа не найдены.');
        }

        $subjectColumn = $subjectType === 'user' ? 'user_id' : 'group_id';
        /** @var CollectionPermission|DocumentPermission|null $model */
        $model = $modelClass::findOne([
            $resourceColumn => $resourceId,
            $subjectColumn => $subjectId,
        ]);
        $model ??= new $modelClass();
        $model->setAttribute($resourceColumn, $resourceId);
        $model->user_id = $subjectType === 'user' ? $subjectId : null;
        $model->group_id = $subjectType === 'group' ? $subjectId : null;
        $model->permission = $permissionValue;

        if (!$model->save()) {
            throw new BadRequestHttpException($this->firstError($model->getFirstErrors()));
        }
        return $model;
    }

    private function viewParams(
        string $resourceType,
        string $resourceId,
        string $resourceName,
        string $defaultPermission,
        array $permissions,
        array $backRoute
    ): array {
        return [
            'resourceType' => $resourceType,
            'resourceId' => $resourceId,
            'resourceName' => $resourceName,
            'defaultPermission' => $defaultPermission,
            'permissions' => $permissions,
            'users' => User::find()
                ->where(['workspace_id' => $this->workspaceId(), 'status' => 'active'])
                ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC])
                ->all(),
            'groups' => Group::find()
                ->where(['workspace_id' => $this->workspaceId()])
                ->orderBy(['name' => SORT_ASC])
                ->all(),
            'backRoute' => $backRoute,
        ];
    }

    private function assertCanUpdateCollection(Collection $collection): void
    {
        if (!(new PermissionService())->canUpdateCollection($this->currentUser(), $collection)) {
            throw new ForbiddenHttpException('Недостаточно прав для управления доступом к коллекции.');
        }
    }

    private function assertCanUpdateDocument(Document $document): void
    {
        if (!(new PermissionService())->canUpdateDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Недостаточно прав для управления доступом к документу.');
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

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить разрешение.';
    }
}
