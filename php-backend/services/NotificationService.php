<?php

declare(strict_types=1);

namespace app\services;

use app\models\Comment;
use app\models\Document;
use app\models\Notification;
use app\models\User;
use Throwable;
use Yii;
use yii\db\Expression;

final class NotificationService
{
    public function syncDocumentMentions(
        Document $document,
        User $actor,
        array $previousContent = []
    ): void {
        $previous = $this->userMentions($previousContent);
        $current = $this->userMentions($document->getContentData());
        $permissions = new PermissionService();

        foreach ($current as $mentionId => $userId) {
            if (isset($previous[$mentionId]) || $userId === (string)$actor->id) {
                continue;
            }

            $recipient = User::findOne([
                'id' => $userId,
                'workspace_id' => $document->workspace_id,
                'status' => 'active',
            ]);
            if (!$recipient || !$permissions->canReadDocument($recipient, $document)) {
                continue;
            }

            $this->create(
                workspaceId: (string)$document->workspace_id,
                userId: (string)$recipient->id,
                actorId: (string)$actor->id,
                type: 'document_mention',
                uniqueKey: sprintf(
                    'mention:%s:%s:%s',
                    (string)$document->id,
                    $mentionId,
                    (string)$recipient->id
                ),
                documentId: (string)$document->id,
                data: [
                    'mentionId' => $mentionId,
                    'documentTitle' => (string)$document->title,
                ]
            );
        }
    }

    public function notifyComment(Comment $comment, User $actor, Document $document): void
    {
        $recipientId = null;
        $type = 'document_comment';
        $uniqueSuffix = (string)$comment->id;

        if ($comment->parent_comment_id) {
            $parent = Comment::findOne([
                'id' => $comment->parent_comment_id,
                'document_id' => $document->id,
            ]);
            $recipientId = $parent?->user_id;
            $type = 'comment_reply';
        } else {
            $recipientId = $document->created_by_id;
        }

        if (!$recipientId || $recipientId === $actor->id) {
            return;
        }

        $recipient = User::findOne([
            'id' => $recipientId,
            'workspace_id' => $document->workspace_id,
            'status' => 'active',
        ]);
        if (!$recipient || !(new PermissionService())->canReadDocument($recipient, $document)) {
            return;
        }

        $this->create(
            workspaceId: (string)$document->workspace_id,
            userId: (string)$recipient->id,
            actorId: (string)$actor->id,
            type: $type,
            uniqueKey: sprintf('%s:%s:%s', $type, $uniqueSuffix, (string)$recipient->id),
            documentId: (string)$document->id,
            commentId: (string)$comment->id,
            data: ['documentTitle' => (string)$document->title]
        );
    }

    public function markAllRead(User $user): int
    {
        return Notification::updateAll(
            ['read_at' => new Expression('CURRENT_TIMESTAMP(6)')],
            [
                'and',
                ['workspace_id' => $user->workspace_id, 'user_id' => $user->id, 'read_at' => null],
                ['archived_at' => null],
            ]
        );
    }

    /** @return array<string,string> */
    public function userMentions(array $document): array
    {
        $result = [];
        $walk = function (mixed $value, string $path = '0') use (&$walk, &$result): void {
            if (!is_array($value)) {
                return;
            }

            if (($value['type'] ?? null) === 'mention' && is_array($value['attrs'] ?? null)) {
                $attrs = $value['attrs'];
                if (($attrs['type'] ?? null) === 'user') {
                    $userId = trim((string)($attrs['modelId'] ?? ''));
                    $mentionId = trim((string)($attrs['id'] ?? ''));
                    if ($userId !== '' && preg_match('/^[0-9a-fA-F-]{36}$/', $userId)) {
                        if ($mentionId === '') {
                            $mentionId = hash('sha256', $path . ':' . $userId);
                        }
                        $result[$mentionId] = $userId;
                    }
                }
            }

            foreach ($value as $key => $child) {
                if (is_array($child)) {
                    $walk($child, $path . '.' . (string)$key);
                }
            }
        };
        $walk($document);
        return $result;
    }

    private function create(
        string $workspaceId,
        string $userId,
        ?string $actorId,
        string $type,
        string $uniqueKey,
        ?string $documentId = null,
        ?string $commentId = null,
        array $data = []
    ): void {
        if (Notification::find()->where(['unique_key' => $uniqueKey])->exists()) {
            return;
        }

        $notification = new Notification([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'document_id' => $documentId,
            'comment_id' => $commentId,
            'unique_key' => $uniqueKey,
            'data' => $data,
        ]);

        try {
            if (!$notification->save()) {
                Yii::warning([
                    'message' => 'Unable to save notification',
                    'errors' => $notification->getErrors(),
                ], __METHOD__);
            }
        } catch (Throwable $error) {
            // A concurrent save may win the unique-key race. Other database
            // failures are logged without failing the document transaction.
            Yii::warning([
                'message' => 'Unable to save notification',
                'error' => $error->getMessage(),
            ], __METHOD__);
        }
    }
}
