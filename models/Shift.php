<?php

namespace app\models;

use yii\db\ActiveRecord;

class Shift extends ActiveRecord
{
    public static function tableName(): string { return '{{%shifts}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'shift_date', 'start_time', 'end_time', 'created_by'], 'required'],
            [['workspace_id', 'user_id', 'created_by'], 'integer'],
            [['shift_date'], 'date', 'format' => 'php:Y-m-d'],
            [['start_time', 'end_time'], 'date', 'format' => 'php:H:i'],
            [['type', 'status'], 'string', 'max' => 50],
            [['comment'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
