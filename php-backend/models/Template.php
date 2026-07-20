<?php

declare(strict_types=1);
namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Template extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%templates}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'created_by_id', 'updated_by_id'], 'required'],
            [['description'], 'string', 'max' => 2000],
            [['content_json'], 'safe'],
            [['content_text'], 'string'],
            [['name'], 'string', 'min' => 1, 'max' => 255],
            [['workspace_id', 'created_by_id', 'updated_by_id'], 'string', 'max' => 36],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название шаблона',
            'description' => 'Описание',
            'content_text' => 'Содержимое',
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->content_json)) {
            $this->content_json = json_encode(
                $this->content_json,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        return parent::beforeSave($insert);
    }

    public function getContentData(): array
    {
        if (is_array($this->content_json)) {
            return $this->content_json;
        }
        if (!is_string($this->content_json) || trim($this->content_json) === '') {
            return ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
        }

        try {
            $decoded = json_decode($this->content_json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded)
                ? $decoded
                : ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
        } catch (JsonException) {
            return ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
        }
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_id']);
    }

    public function getUpdater(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'updated_by_id']);
    }
}
