<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Group extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%groups}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name'], 'required'],
            [['workspace_id'], 'string', 'max' => 36],
            [['name'], 'string', 'min' => 1, 'max' => 255],
            [['description'], 'string', 'max' => 2000],
            [['name'], 'unique', 'targetAttribute' => ['workspace_id', 'name'], 'message' => 'Группа с таким названием уже существует.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название группы',
            'description' => 'Описание',
        ];
    }

    public function getMemberships(): ActiveQuery
    {
        return $this->hasMany(GroupUser::class, ['group_id' => 'id']);
    }

    public function getUsers(): ActiveQuery
    {
        return $this->hasMany(User::class, ['id' => 'user_id'])
            ->via('memberships')
            ->orderBy(['last_name' => SORT_ASC, 'first_name' => SORT_ASC]);
    }
}
