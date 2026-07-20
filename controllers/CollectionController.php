<?php

namespace app\controllers;

use app\models\Collection;
use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class CollectionController extends BaseController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = ['class' => VerbFilter::class, 'actions' => ['delete' => ['post']]];
        return $behaviors;
    }

    public function actionIndex(): string
    {
        $collections = Collection::find()->where(['workspace_id' => $this->currentUser()->workspace_id])->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])->all();
        return $this->render('index', ['collections' => $collections]);
    }

    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    public function actionCreate(): string|\yii\web\Response
    {
        $this->requireManager();
        $model = new Collection([
            'workspace_id' => $this->currentUser()->workspace_id,
            'created_by' => $this->currentUser()->id,
            'permission_mode' => 'workspace',
        ]);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Коллекция создана.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|\yii\web\Response
    {
        $this->requireManager();
        $model = $this->findModel($id);
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Коллекция обновлена.');
            return $this->redirect(['view', 'id' => $model->id]);
        }
        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete(int $id): \yii\web\Response
    {
        $this->requireManager();
        $model = $this->findModel($id);
        if ($model->getDocuments()->count() > 0) {
            Yii::$app->session->setFlash('error', 'Сначала переместите или удалите документы коллекции.');
        } else {
            $model->delete();
            Yii::$app->session->setFlash('success', 'Коллекция удалена.');
        }
        return $this->redirect(['index']);
    }

    private function findModel(int $id): Collection
    {
        $model = Collection::findOne(['id' => $id, 'workspace_id' => $this->currentUser()->workspace_id]);
        if (!$model) {
            throw new NotFoundHttpException('Коллекция не найдена.');
        }
        return $model;
    }
}
