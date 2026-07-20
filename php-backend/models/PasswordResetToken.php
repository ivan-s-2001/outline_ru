<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class PasswordResetToken extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%password_reset_tokens}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'token_hash', 'expires_at'], 'required'],
            [['user_id'], 'string', 'max' => 36],
            [['token_hash'], 'string', 'length' => 64],
            [['expires_at', 'used_at'], 'safe'],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && strtotime((string)$this->expires_at) > time();
    }
}
