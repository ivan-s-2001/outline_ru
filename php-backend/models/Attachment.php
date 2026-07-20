<?php

declare(strict_types=1);
namespace app\models;

use yii\db\ActiveQuery;

final class Attachment extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%attachments}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'name', 'content_type', 'size', 'storage_key'], 'required'],
            [['size'], 'integer', 'min' => 0],
            [['workspace_id', 'document_id', 'user_id'], 'string', 'max' => 36],
            [['name', 'storage_key'], 'string', 'max' => 1024],
            [['content_type'], 'string', 'max' => 255],
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
