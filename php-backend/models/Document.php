<?php

declare(strict_types=1);

namespace app\models;

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
