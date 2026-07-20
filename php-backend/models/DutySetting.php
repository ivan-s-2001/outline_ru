<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class DutySetting extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%duty_settings}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'date_from', 'date_to', 'start_time', 'end_time', 'slot_minutes', 'created_by_id'], 'required'],
            [['workspace_id', 'created_by_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['date_from', 'date_to', 'start_time', 'end_time'], 'safe'],
            [['slot_minutes'], 'integer', 'min' => 10, 'max' => 240],
            [['status'], 'in', 'range' => ['draft', 'published', 'archived']],
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'string'],
            ['end_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>', 'type' => 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название',
            'date_from' => 'С',
            'date_to' => 'По',
            'start_time' => 'Начало дежурств',
            'end_time' => 'Окончание дежурств',
            'slot_minutes' => 'Шаг, минут',
            'status' => 'Статус',
        ];
    }

    public function getParticipants(): ActiveQuery
    {
        return $this->hasMany(DutyParticipant::class, ['duty_setting_id' => 'id']);
    }

    public function getAssignments(): ActiveQuery
    {
        return $this->hasMany(DutyAssignment::class, ['duty_setting_id' => 'id'])
            ->orderBy(['duty_date' => SORT_ASC, 'start_time' => SORT_ASC]);
    }
}
