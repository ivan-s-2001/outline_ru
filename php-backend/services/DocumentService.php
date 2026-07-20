<?php

declare(strict_types=1);
namespace app\services;

use app\models\Document;
use app\models\Revision;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;

final class DocumentService
{
    public function save(Document $document, User $user): Document
    {
        $created = $document->isNewRecord;
        if ($created) {
            $document->workspace_id = $user->workspace_id;
            $document->created_by_id = $user->id;
            $document->revision_number = 0;
        }
        $document->updated_by_id = $user->id;
        $document->revision_number = (int)$document->revision_number + 1;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$document->save()) {
                throw new RuntimeException($this->firstError($document->getFirstErrors()));
            }

            $revision = new Revision();
            $revision->document_id = $document->id;
            $revision->user_id = $user->id;
            $revision->title = $document->title;
            $revision->content_json = $document->content_json;
            $revision->content_text = $document->content_text;
            $revision->yjs_state = $document->yjs_state;
            $revision->revision_number = $document->revision_number;

            if (!$revision->save()) {
                throw new RuntimeException($this->firstError($revision->getFirstErrors()));
            }

            $transaction->commit();
            (new AuditService())->record(
                (string)$document->workspace_id,
                $created ? 'document.created' : 'document.updated',
                $user,
                (string)$document->id,
                [
                    'title' => $document->title,
                    'collectionId' => $document->collection_id,
                    'parentDocumentId' => $document->parent_document_id,
                    'revision' => (int)$document->revision_number,
                ]
            );
            return $document;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить документ.';
    }
}
