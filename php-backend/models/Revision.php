<?php

declare(strict_types=1);
namespace app\models;

use JsonException;
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
            [['content_json', 'yjs_state'], 'safe'],
            [['content_text'], 'string'],
            [['revision_number'], 'integer', 'min' => 0],
            [['document_id', 'user_id'], 'string', 'max' => 36],
            [['title'], 'string', 'max' => 1024],
        ];
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

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
