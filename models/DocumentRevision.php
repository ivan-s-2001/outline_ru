<?php

namespace app\models;

use yii\db\ActiveRecord;

class DocumentRevision extends ActiveRecord
{
    public static function tableName(): string { return '{{%document_revisions}}'; }
    public function rules(): array
    {
        return [
            [['document_id', 'version', 'title', 'created_by'], 'required'],
            [['document_id', 'version', 'created_by'], 'integer'],
            [['title'], 'string', 'max' => 500],
            [['content', 'content_json', 'content_text'], 'string'],
        ];
    }
    public function getCreator() { return $this->hasOne(User::class, ['id' => 'created_by']); }
}
