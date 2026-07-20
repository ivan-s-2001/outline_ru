<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Document;
use app\services\PermissionService;
use Yii;
use yii\data\ArrayDataProvider;

final class SearchController extends AuthenticatedController
{
    public function actionIndex(): string
    {
        $query = trim((string)Yii::$app->request->get('q', ''));
        $documents = [];
        $scores = [];

        if ($query !== '') {
            $rows = Yii::$app->db->createCommand(
                'SELECT id,
                        MATCH(title, content_text) AGAINST(:query IN NATURAL LANGUAGE MODE) AS relevance
                 FROM {{%documents}}
                 WHERE workspace_id = :workspace
                   AND archived_at IS NULL
                   AND deleted_at IS NULL
                   AND MATCH(title, content_text) AGAINST(:query IN NATURAL LANGUAGE MODE)
                 ORDER BY relevance DESC, updated_at DESC
                 LIMIT 100',
                [
                    ':query' => mb_substr($query, 0, 500),
                    ':workspace' => $this->workspaceId(),
                ]
            )->queryAll();

            $ids = array_column($rows, 'id');
            foreach ($rows as $row) {
                $scores[(string)$row['id']] = (float)$row['relevance'];
            }

            if ($ids) {
                $models = Document::find()
                    ->with('collection')
                    ->where(['id' => $ids, 'workspace_id' => $this->workspaceId()])
                    ->all();
                $byId = [];
                foreach ($models as $model) {
                    $byId[(string)$model->id] = $model;
                }

                $permission = new PermissionService();
                foreach ($ids as $id) {
                    $document = $byId[(string)$id] ?? null;
                    if ($document && $permission->canReadDocument($this->currentUser(), $document)) {
                        $documents[] = $document;
                    }
                }
            }
        }

        return $this->render('index', [
            'query' => $query,
            'scores' => $scores,
            'provider' => new ArrayDataProvider([
                'allModels' => $documents,
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }
}
