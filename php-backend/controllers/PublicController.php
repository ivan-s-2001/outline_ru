<?php

declare(strict_types=1);
namespace app\controllers;

use app\models\Document;
use app\models\Share;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

final class PublicController extends Controller
{
    public $layout = 'public';

    public function actionShare(string $token, ?string $documentId = null): string
    {
        $share = Share::find()
            ->with('document.collection')
            ->where(['token' => $token, 'is_published' => true])
            ->one();
        if (!$share || !$share->document) {
            throw new NotFoundHttpException('Публичная ссылка не найдена.');
        }

        $root = $share->document;
        if ($root->archived_at !== null || $root->deleted_at !== null) {
            throw new NotFoundHttpException('Документ недоступен.');
        }

        $document = $root;
        if ($documentId !== null && $documentId !== $root->id) {
            if (!$share->include_child_documents) {
                throw new NotFoundHttpException('Дочерние документы не опубликованы.');
            }
            $candidate = Document::find()
                ->with('collection')
                ->where([
                    'id' => $documentId,
                    'workspace_id' => $root->workspace_id,
                    'archived_at' => null,
                    'deleted_at' => null,
                ])
                ->one();
            if (!$candidate || !$this->isDescendant($candidate, $root)) {
                throw new NotFoundHttpException('Опубликованный документ не найден.');
            }
            $document = $candidate;
        }

        $children = [];
        if ($share->include_child_documents) {
            $children = Document::find()
                ->where([
                    'parent_document_id' => $document->id,
                    'workspace_id' => $root->workspace_id,
                    'archived_at' => null,
                    'deleted_at' => null,
                ])
                ->orderBy(['sort_order' => SORT_ASC, 'title' => SORT_ASC])
                ->all();
        }

        return $this->render('share', [
            'share' => $share,
            'root' => $root,
            'document' => $document,
            'children' => $children,
        ]);
    }

    private function isDescendant(Document $candidate, Document $root): bool
    {
        $visited = [];
        $current = $candidate;
        for ($depth = 0; $depth < 100 && $current->parent_document_id; $depth++) {
            $parentId = (string)$current->parent_document_id;
            if ($parentId === $root->id) {
                return true;
            }
            if (isset($visited[$parentId])) {
                return false;
            }
            $visited[$parentId] = true;
            $current = Document::findOne([
                'id' => $parentId,
                'workspace_id' => $root->workspace_id,
                'archived_at' => null,
                'deleted_at' => null,
            ]);
            if (!$current) {
                return false;
            }
        }
        return false;
    }
}
