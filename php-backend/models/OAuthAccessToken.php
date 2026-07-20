<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class OAuthAccessToken extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%oauth_access_tokens}}';
    }

    public function rules(): array
    {
        return [
            [['app_id', 'user_id', 'token_hash', 'scopes', 'expires_at'], 'required'],
            [['app_id', 'user_id'], 'string', 'max' => 36],
            [['token_hash', 'refresh_token_hash'], 'string', 'length' => 64],
            [['scopes'], 'safe'],
            [['expires_at', 'refresh_expires_at', 'revoked_at', 'last_used_at'], 'safe'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->scopes)) {
            $this->scopes = json_encode($this->scopes, JSON_THROW_ON_ERROR);
        }
        return parent::beforeSave($insert);
    }

    public function getScopes(): array
    {
        if (is_array($this->scopes)) {
            return $this->scopes;
        }
        try {
            $value = json_decode((string)$this->scopes, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->getScopes(), true);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getApp(): ActiveQuery
    {
        return $this->hasOne(OAuthApp::class, ['id' => 'app_id']);
    }
}
