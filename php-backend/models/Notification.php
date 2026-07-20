<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Notification extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%notifications}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'type', 'unique_key'], 'required'],
            [['data'], 'safe'],
            [['workspace_id', 'user_id', 'actor_id', 'document_id', 'comment_id'], 'string', 'max' => 36],
            [['type'], 'string', 'max' => 64],
            [['unique_key'], 'string', 'max' => 255],
            [['read_at', 'archived_at'], 'safe'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->data)) {
            $this->data = json_encode(
                $this->data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        return parent::beforeSave($insert);
    }

    public function getDataValue(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        if (!is_string($this->data) || $this->data === '') {
            return [];
        }
        try {
            $value = json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? $value : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function getActor(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'actor_id']);
    }

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }

    public function getComment(): ActiveQuery
    {
        return $this->hasOne(Comment::class, ['id' => 'comment_id']);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}
