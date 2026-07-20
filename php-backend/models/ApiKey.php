<?php

declare(strict_types=1);
namespace app\models;

use yii\db\ActiveQuery;

final class ApiKey extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%api_keys}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'name', 'token_prefix', 'token_hash', 'permission'], 'required'],
            [['last_used_at', 'expires_at', 'revoked_at'], 'safe'],
            [['name'], 'string', 'min' => 1, 'max' => 255],
            [['workspace_id', 'user_id'], 'string', 'max' => 36],
            [['token_prefix'], 'string', 'max' => 24],
            [['token_hash'], 'string', 'length' => 64],
            [['token_hash'], 'unique'],
            [['permission'], 'in', 'range' => ['read', 'write']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название ключа',
            'permission' => 'Доступ',
            'expires_at' => 'Срок действия',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }
        return $this->expires_at === null || strtotime((string)$this->expires_at) > time();
    }

    public function canWrite(): bool
    {
        return $this->permission === 'write' && $this->isActive();
    }
}
