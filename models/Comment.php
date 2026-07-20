<?php

namespace app\models;

use yii\db\ActiveRecord;

class Comment extends ActiveRecord
{
    public static function tableName(): string { return '{{%comments}}'; }
    public function rules(): array
    {
        return [
            [['document_id', 'user_id', 'body'], 'required'],
            [['document_id', 'user_id', 'parent_id'], 'integer'],
            [['body', 'anchor_json'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
