<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

final class GroupUser extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%group_users}}';
    }

    public static function primaryKey(): array
    {
        return ['group_id', 'user_id'];
    }

    public function rules(): array
    {
        return [
            [['group_id', 'user_id'], 'required'],
            [['group_id', 'user_id'], 'string', 'max' => 36],
            [['group_id', 'user_id'], 'unique', 'targetAttribute' => ['group_id', 'user_id'], 'message' => 'Пользователь уже состоит в группе.'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if ($insert && !$this->created_at) {
            $this->created_at = gmdate('Y-m-d H:i:s.u');
        }
        return parent::beforeSave($insert);
    }

    public function getGroup(): ActiveQuery
    {
        return $this->hasOne(Group::class, ['id' => 'group_id']);
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
