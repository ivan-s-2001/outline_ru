<?php

namespace app\models;

use yii\db\ActiveRecord;

class Absence extends ActiveRecord
{
    public static function tableName(): string { return '{{%absences}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'type', 'date_from', 'date_to', 'created_by'], 'required'],
            [['workspace_id', 'user_id', 'minutes', 'created_by'], 'integer'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['date_to'], 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'string'],
            [['type'], 'in', 'range' => ['vacation', 'sick', 'day_off', 'training', 'other']],
            [['comment'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
