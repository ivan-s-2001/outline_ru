<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Collection extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%collections}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'permission', 'created_by_id'], 'required'],
            [['description'], 'string', 'max' => 2000],
            [['name'], 'string', 'min' => 1, 'max' => 255],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/', 'message' => 'Укажите цвет в формате #RRGGBB.'],
            [['icon'], 'string', 'max' => 255],
            [['permission'], 'in', 'range' => ['none', 'read', 'read_write']],
            [['workspace_id', 'created_by_id'], 'string', 'max' => 36],
            [['sort_order'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название',
            'description' => 'Описание',
            'color' => 'Цвет',
            'icon' => 'Иконка или emoji',
            'permission' => 'Доступ по умолчанию',
        ];
    }

    public function getDocuments(): ActiveQuery
    {
        return $this->hasMany(Document::class, ['collection_id' => 'id'])
            ->andWhere(['deleted_at' => null, 'archived_at' => null]);
    }

    public function getCreator(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by_id']);
    }
}
