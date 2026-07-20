<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class DutyAssignment extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%duty_assignments}}';
    }

    public function rules(): array
    {
        return [
            [['duty_setting_id', 'user_id', 'duty_date', 'start_time', 'end_time', 'share_coefficient'], 'required'],
            [['duty_setting_id', 'user_id'], 'string', 'max' => 36],
            [['duty_date', 'start_time', 'end_time'], 'safe'],
            [['share_coefficient'], 'number', 'min' => 0, 'max' => 10],
            [['status'], 'in', 'range' => ['assigned', 'completed', 'cancelled']],
            [['comment'], 'string', 'max' => 5000],
            ['end_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>', 'type' => 'string'],
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getSetting(): ActiveQuery
    {
        return $this->hasOne(DutySetting::class, ['id' => 'duty_setting_id']);
    }
}
