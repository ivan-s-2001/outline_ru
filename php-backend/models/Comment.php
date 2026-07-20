<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Comment extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%comments}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'document_id', 'user_id', 'text'], 'required'],
            [['text'], 'string', 'min' => 1, 'max' => 20000],
            [['data'], 'safe'],
            [['workspace_id', 'document_id', 'parent_comment_id', 'user_id'], 'string', 'max' => 36],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'text' => 'Комментарий',
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

    public function getDocument(): ActiveQuery
    {
        return $this->hasOne(Document::class, ['id' => 'document_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_comment_id']);
    }

    public function getReplies(): ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_comment_id' => 'id'])
            ->orderBy(['created_at' => SORT_ASC]);
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }
}
