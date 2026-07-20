<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Revision extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%revisions}}';
    }

    public function rules(): array
    {
        return [
            [['document_id', 'user_id', 'title', 'revision_number'], 'required'],
            [['content_json'], 'safe'],
            [['content_text'], 'string'],
            [['revision_number'], 'integer', 'min' => 0],
            [['document_id', 'user_id'], 'string', 'max' => 36],
            [['title'], 'string', 'max' => 1024],
        ];
    }

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
