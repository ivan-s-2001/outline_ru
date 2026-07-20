<?php

namespace app\models;

use yii\db\ActiveRecord;

class Workspace extends ActiveRecord
{
    public static function tableName(): string { return '{{%workspaces}}'; }
    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 100],
            [['slug'], 'unique'],
        ];
    }
}
