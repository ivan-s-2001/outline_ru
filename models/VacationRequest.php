<?php

namespace app\models;

use yii\db\ActiveRecord;

class VacationRequest extends ActiveRecord
{
    public static function tableName(): string { return '{{%vacation_requests}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'date_from', 'date_to', 'status'], 'required'],
            [['workspace_id', 'user_id', 'working_days', 'reviewed_by'], 'integer'],
            [['date_from', 'date_to'], 'date', 'format' => 'php:Y-m-d'],
            [['date_to'], 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'string'],
            [['status'], 'in', 'range' => ['pending', 'approved', 'rejected', 'cancelled']],
            [['comment'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
