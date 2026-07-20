<?php

namespace app\controllers;

use app\models\Collection;
use app\models\Comment;
use app\models\Document;
use app\models\DocumentRevision;
use Yii;
use yii\filters\VerbFilter;
use yii\web\ConflictHttpException;
use yii\web\NotFoundHttpException;

class DocumentController extends BaseController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['post'],
                'comment' => ['post'],
                'resolve-comment' => ['post'],
                'restore' => ['post'],
            ],
        ];
        return $behaviors;
    }

    public function actionView(int $id): string
    {
        $model = $this->findModel($id);
        return $this->render('view', ['model' => $model, 'comment' => new Comment()]);
    }

    public function actionCreate(?int $collection_id = null): string|\yii\web\Response
    {
        $model = new Document([
            'workspace_id' => $this->currentUser()->workspace_id,
            'collection_id' => $collection_id,
            'created_by' => $this->currentUser()->id,
            'updated_by' => $this->currentUser()->id,
            'status' => 'published',
            'version' => 1,
            'content' => '',
        ]);
        return $this->saveDocument($model, true);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        return $this->saveDocument($this->findModel($id), false);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $model = $this->findModel($id);
        if (!$this->currentUser()->canManage() && (int)$model->created_by !== (int)$this->currentUser()->id) {
            throw new \yii\web\ForbiddenHttpException('Удалять документ может автор или руководитель.');
        }
        $collectionId = $model->collection_id;
        $model->delete();
        Yii::$app->session->setFlash('success', 'Документ удалён.');
        return $collectionId ? $this->redirect(['/collection/view', 'id' => $collectionId]) : $this->goHome();
    }

    public function actionHistory(int $id): string
    {
        return $this->render('history', ['model' => $this->findModel($id)]);
    }

    public function actionRestore(int $id, int $revision): \yii\web\Response
    {
        $this->requireManager();
        $model = $this->findModel($id);
        $revisionModel = DocumentRevision::findOne(['id' => $revision, 'document_id' => $model->id]);
        if (!$revisionModel) {
            throw new NotFoundHttpException('Версия не найдена.');
        }
        $model->title = $revisionModel->title;
        $model->content = $revisionModel->content;
        $model->version = (int)$model->version + 1;
        $model->updated_by = $this->currentUser()->id;
        $model->save(false);
        $this->createRevision($model);
        Yii::$app->session->setFlash('success', 'Версия восстановлена.');
        return $this->redirect(['view', 'id' => $model->id]);
    }

    public function actionComment(int $id): \yii\web\Response
    {
        $document = $this->findModel($id);
        $comment = new Comment([
            'document_id' => $document->id,
            'user_id' => $this->currentUser()->id,
        ]);
        if ($comment->load(Yii::$app->request->post()) && $comment->save()) {
            Yii::$app->session->setFlash('success', 'Комментарий добавлен.');
        } else {
            Yii::$app->session->setFlash('error', 'Комментарий не сохранён.');
        }
        return $this->redirect(['view', 'id' => $document->id, '#' => 'comments']);
    }

    public function actionResolveComment(int $id, int $comment): \yii\web\Response
    {
        $document = $this->findModel($id);
        $model = Comment::findOne(['id' => $comment, 'document_id' => $document->id]);
        if (!$model) {
            throw new NotFoundHttpException('Комментарий не найден.');
        }
        if (!$this->currentUser()->canManage() && (int)$model->user_id !== (int)$this->currentUser()->id) {
            throw new \yii\web\ForbiddenHttpException('Недостаточно прав.');
        }
        $model->resolved_at = $model->resolved_at ? null : gmdate('Y-m-d H:i:s');
        $model->save(false, ['resolved_at', 'updated_at']);
        return $this->redirect(['view', 'id' => $document->id, '#' => 'comments']);
    }

    private function saveDocument(Document $model, bool $isNew): string|\yii\web\Response
    {
        $postedVersion = (int)Yii::$app->request->post('version', $model->version);
        if ($model->load(Yii::$app->request->post())) {
            if (!$isNew && $postedVersion !== (int)$model->getOldAttribute('version')) {
                throw new ConflictHttpException('Документ уже изменён другим пользователем. Обновите страницу.');
            }
            $model->workspace_id = $this->currentUser()->workspace_id;
            $model->updated_by = $this->currentUser()->id;
            if (!$isNew) {
                $model->version = (int)$model->getOldAttribute('version') + 1;
            }
            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $this->createRevision($model);
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', $isNew ? 'Документ создан.' : 'Документ сохранён.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
        }
        $collections = Collection::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->orderBy(['name' => SORT_ASC])->all();
        return $this->render('form', compact('model', 'collections'));
    }

    private function createRevision(Document $model): void
    {
        (new DocumentRevision([
            'document_id' => $model->id,
            'version' => $model->version,
            'title' => $model->title,
            'content' => $model->content,
            'created_by' => $this->currentUser()->id,
        ]))->save(false);
    }

    private function findModel(int $id): Document
    {
        $model = Document::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]);
        if (!$model) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $model;
    }
}
