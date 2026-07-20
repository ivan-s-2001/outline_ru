<?php

namespace app\models;

use yii\db\ActiveRecord;

class WorkTimeAdjustment extends ActiveRecord
{
    public static function tableName(): string { return '{{%work_time_adjustments}}'; }
    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'work_date', 'minutes', 'reason', 'created_by'], 'required'],
            [['workspace_id', 'user_id', 'minutes', 'created_by'], 'integer'],
            [['work_date'], 'date', 'format' => 'php:Y-m-d'],
            [['reason'], 'string', 'max' => 255],
            [['comment'], 'string'],
        ];
    }
    public function getUser() { return $this->hasOne(User::class, ['id' => 'user_id']); }
}
