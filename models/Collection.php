<?php

namespace app\models;

use yii\db\ActiveRecord;

class Collection extends ActiveRecord
{
    public static function tableName(): string { return '{{%collections}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'created_by'], 'required'],
            [['workspace_id', 'created_by', 'sort_order'], 'integer'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['color'], 'string', 'max' => 20],
            [['permission_mode'], 'in', 'range' => ['workspace', 'private']],
        ];
    }
    public function getDocuments() { return $this->hasMany(Document::class, ['collection_id' => 'id'])->andWhere(['deleted_at' => null])->orderBy(['title' => SORT_ASC]); }
}
