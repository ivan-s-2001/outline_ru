<?php

declare(strict_types=1);

namespace app\services;

use app\models\Collection;
use app\models\Document;
use app\models\User;
use Yii;

final class PermissionService
{
    private const RANK = [
        'none' => 0,
        'read' => 1,
        'read_write' => 2,
    ];

    public function canReadCollection(User $user, Collection $collection): bool
    {
        return $this->collectionPermission($user, $collection) >= self::RANK['read'];
    }

    public function canUpdateCollection(User $user, Collection $collection): bool
    {
        return $this->collectionPermission($user, $collection) >= self::RANK['read_write'];
    }

    public function canReadDocument(User $user, Document $document): bool
    {
        return $this->documentPermission($user, $document) >= self::RANK['read'];
    }

    public function canUpdateDocument(User $user, Document $document): bool
    {
        return $this->documentPermission($user, $document) >= self::RANK['read_write'];
    }

    public function collectionPermission(User $user, Collection $collection): int
    {
        if ($user->workspace_id !== $collection->workspace_id) {
            return self::RANK['none'];
        }
        if ($user->isAdmin() || $collection->created_by_id === $user->id) {
            return self::RANK['read_write'];
        }

        $explicit = $this->explicitPermission(
            'collection_permissions',
            'collection_id',
            (string)$collection->id,
            (string)$user->id
        );
        if ($explicit !== null) {
            return $explicit;
        }

        return self::RANK[$collection->permission] ?? self::RANK['none'];
    }

    public function documentPermission(User $user, Document $document): int
    {
        if ($user->workspace_id !== $document->workspace_id) {
            return self::RANK['none'];
        }
        if ($user->isAdmin() || $document->created_by_id === $user->id) {
            return self::RANK['read_write'];
        }

        $explicit = $this->explicitPermission(
            'document_permissions',
            'document_id',
            (string)$document->id,
            (string)$user->id
        );
        if ($explicit !== null) {
            return $explicit;
        }

        if ($document->collection) {
            return $this->collectionPermission($user, $document->collection);
        }

        return self::RANK['none'];
    }

    private function explicitPermission(
        string $permissionTable,
        string $modelColumn,
        string $modelId,
        string $userId
    ): ?int {
        $allowedTables = ['collection_permissions', 'document_permissions'];
        $allowedColumns = ['collection_id', 'document_id'];
        if (!in_array($permissionTable, $allowedTables, true) || !in_array($modelColumn, $allowedColumns, true)) {
            return null;
        }

        $sql = sprintf(
            'SELECT p.permission
             FROM {{%%%s}} p
             LEFT JOIN {{%%group_users}} gu
               ON gu.group_id = p.group_id AND gu.user_id = :user
             WHERE p.%s = :model
               AND (p.user_id = :user OR gu.user_id IS NOT NULL)',
            $permissionTable,
            $modelColumn
        );
        $permissions = Yii::$app->db->createCommand($sql, [
            ':user' => $userId,
            ':model' => $modelId,
        ])->queryColumn();

        if (!$permissions) {
            return null;
        }

        $rank = self::RANK['none'];
        foreach ($permissions as $permission) {
            $rank = max($rank, self::RANK[(string)$permission] ?? self::RANK['none']);
        }
        return $rank;
    }
}
