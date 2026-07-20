<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Invitation extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%invitations}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'email', 'role', 'token_hash', 'invited_by_id', 'expires_at'], 'required'],
            [['workspace_id', 'invited_by_id'], 'string', 'max' => 36],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['role'], 'in', 'range' => ['admin', 'member', 'viewer']],
            [['token_hash'], 'string', 'length' => 64],
            [['expires_at', 'accepted_at', 'revoked_at'], 'safe'],
        ];
    }

    public function getInviter(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'invited_by_id']);
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && strtotime((string)$this->expires_at) > time();
    }
}
