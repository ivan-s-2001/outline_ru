<?php

declare(strict_types=1);

namespace app\models;

use JsonException;

final class OAuthApp extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%oauth_apps}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'client_id', 'redirect_uris', 'scopes', 'created_by_id'], 'required'],
            [['workspace_id', 'created_by_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['client_id'], 'string', 'max' => 128],
            [['client_secret_hash'], 'string', 'max' => 255],
            [['redirect_uris', 'scopes'], 'safe'],
            [['is_confidential', 'is_active'], 'boolean'],
        ];
    }

    public function beforeSave($insert): bool
    {
        foreach (['redirect_uris', 'scopes'] as $attribute) {
            if (is_array($this->{$attribute})) {
                $this->{$attribute} = json_encode($this->{$attribute}, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            }
        }
        return parent::beforeSave($insert);
    }

    public function getRedirectUris(): array
    {
        return $this->decodeArray($this->redirect_uris);
    }

    public function getScopes(): array
    {
        return $this->decodeArray($this->scopes);
    }

    public function allowsRedirect(string $uri): bool
    {
        return in_array($uri, $this->getRedirectUris(), true);
    }

    public function allowsScopes(array $scopes): bool
    {
        return array_diff($scopes, $this->getScopes()) === [];
    }

    private function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'is_string'));
        }
        try {
            $decoded = json_decode((string)$value, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? array_values(array_filter($decoded, 'is_string')) : [];
        } catch (JsonException) {
            return [];
        }
    }
}
