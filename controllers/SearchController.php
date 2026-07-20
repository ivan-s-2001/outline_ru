<?php

namespace app\controllers;

use app\models\Document;
use Yii;

class SearchController extends BaseController
{
    public function actionIndex(string $q = ''): string
    {
        $q = trim($q);
        $documents = [];
        if ($q !== '') {
            try {
                $documents = Document::findBySql(
                    'SELECT * FROM {{%documents}} WHERE workspace_id=:workspace AND status="published" AND MATCH(title, content_text) AGAINST (:query IN NATURAL LANGUAGE MODE) ORDER BY MATCH(title, content_text) AGAINST (:query IN NATURAL LANGUAGE MODE) DESC LIMIT 100',
                    [':workspace' => $this->currentUser()->workspace_id, ':query' => $q]
                )->all();
            } catch (\Throwable $e) {
                Yii::warning($e->getMessage(), __METHOD__);
                $documents = Document::find()
                    ->where(['workspace_id' => $this->currentUser()->workspace_id, 'status' => 'published'])
                    ->andWhere(['or', ['like', 'title', $q], ['like', 'content_text', $q]])
                    ->orderBy(['updated_at' => SORT_DESC])
                    ->limit(100)
                    ->all();
            }
        }
        return $this->render('index', compact('q', 'documents'));
    }
}
