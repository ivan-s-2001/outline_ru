<?php

namespace app\models;

use yii\db\ActiveRecord;

class DutyAssignment extends ActiveRecord
{
    public static function tableName(): string { return '{{%duty_assignments}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'duty_date', 'hall', 'duration_minutes', 'created_by'], 'required'],
            [['workspace_id', 'user_id', 'duration_minutes', 'created_by'], 'integer'],
            [['duty_date'], 'date', 'format' => 'php:Y-m-d'],
            [['hall'], 'string', 'max' => 100],
            [['comment'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
