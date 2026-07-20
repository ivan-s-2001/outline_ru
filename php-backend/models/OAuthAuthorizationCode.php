<?php

declare(strict_types=1);

namespace app\models;

use JsonException;

final class OAuthAuthorizationCode extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%oauth_authorization_codes}}';
    }

    public function rules(): array
    {
        return [
            [['app_id', 'user_id', 'code_hash', 'redirect_uri', 'scopes', 'expires_at'], 'required'],
            [['app_id', 'user_id'], 'string', 'max' => 36],
            [['code_hash'], 'string', 'length' => 64],
            [['redirect_uri'], 'string', 'max' => 2048],
            [['scopes'], 'safe'],
            [['code_challenge'], 'string', 'max' => 255],
            [['code_challenge_method'], 'string', 'max' => 16],
            [['expires_at', 'used_at'], 'safe'],
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
}
