<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class DocumentPermission extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%document_permissions}}';
    }

    public function rules(): array
    {
        return [
            [['document_id', 'permission'], 'required'],
            [['document_id', 'user_id', 'group_id'], 'string', 'max' => 36],
            [['permission'], 'in', 'range' => ['none', 'read', 'read_write']],
            [['user_id'], 'validateSubject'],
        ];
    }

    public function validateSubject(string $attribute): void
    {
        $hasUser = $this->user_id !== null && $this->user_id !== '';
        $hasGroup = $this->group_id !== null && $this->group_id !== '';
        if ($hasUser === $hasGroup) {
            $this->addError($attribute, 'Укажите одного пользователя или одну группу.');
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

    public function getGroup(): ActiveQuery
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }
}
