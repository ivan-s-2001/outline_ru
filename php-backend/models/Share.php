<?php

declare(strict_types=1);
namespace app\models;

use yii\db\ActiveQuery;

final class Share extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%shares}}';
    }

    public function rules(): array
    {
        return [
            [['document_id', 'created_by_id', 'token'], 'required'],
            [['is_published', 'include_child_documents'], 'boolean'],
            [['document_id', 'created_by_id'], 'string', 'max' => 36],
            [['token'], 'string', 'min' => 48, 'max' => 128],
            [['token'], 'unique'],
        ];
    }

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_id']);
    }
}
