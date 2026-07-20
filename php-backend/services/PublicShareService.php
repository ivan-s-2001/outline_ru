<?php

declare(strict_types=1);
namespace app\services;

use app\models\Document;
use app\models\Share;

final class PublicShareService
{
    public function isPublished(Document $document): bool
    {
        if ($document->archived_at !== null || $document->deleted_at !== null) {
            return false;
        }

        if (Share::find()->where([
            'document_id' => $document->id,
            'is_published' => true,
        ])->exists()) {
            return true;
        }

        $visited = [];
        $current = $document;
        for ($depth = 0; $depth < 100 && $current->parent_document_id; $depth++) {
            $parentId = (string)$current->parent_document_id;
            if (isset($visited[$parentId])) {
                return false;
            }
            $visited[$parentId] = true;

            if (Share::find()->where([
                'document_id' => $parentId,
                'is_published' => true,
                'include_child_documents' => true,
            ])->exists()) {
                return true;
            }

            $current = Document::findOne([
                'id' => $parentId,
                'workspace_id' => $document->workspace_id,
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
