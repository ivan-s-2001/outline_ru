<?php

namespace app\models;

use yii\db\ActiveRecord;

class ProductionCalendarDay extends ActiveRecord
{
    public static function tableName(): string { return '{{%production_calendar_days}}'; }
    public function rules(): array
    {
        return [
            [['calendar_date', 'is_working'], 'required'],
            [['calendar_date'], 'date', 'format' => 'php:Y-m-d'],
            [['is_working', 'is_shortened'], 'boolean'],
            [['name', 'source'], 'string', 'max' => 255],
        ];
    }
}
