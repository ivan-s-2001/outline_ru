<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Document extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%documents}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'title', 'created_by_id', 'updated_by_id'], 'required'],
            [['content_json'], 'safe'],
            [['content_text'], 'string'],
            [['revision_number'], 'integer', 'min' => 0],
            [['title'], 'string', 'max' => 1024],
            [['workspace_id', 'collection_id', 'parent_document_id', 'created_by_id', 'updated_by_id'], 'string', 'max' => 36],
            [['sort_order'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => 'Название документа',
            'collection_id' => 'Коллекция',
            'parent_document_id' => 'Родительский документ',
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
            return ['type' => 'doc', 'content' => []];
        }

        try {
            $decoded = json_decode($this->content_json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : ['type' => 'doc', 'content' => []];
        } catch (JsonException) {
            return ['type' => 'doc', 'content' => []];
        }
    }

    public function getCollection(): ActiveQuery
    {
        return $this->hasOne(Collection::class, ['id' => 'collection_id']);
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_document_id']);
    }

    public function getChildren(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_document_id' => 'id'])
            ->andWhere(['deleted_at' => null, 'archived_at' => null]);
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_id']);
    }
}
