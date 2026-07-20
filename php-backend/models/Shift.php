<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Shift extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%shifts}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'work_date', 'start_time', 'end_time', 'created_by_id', 'updated_by_id'], 'required'],
            [['workspace_id', 'user_id', 'shift_type_id', 'created_by_id', 'updated_by_id'], 'string', 'max' => 36],
            [['work_date', 'start_time', 'end_time'], 'safe'],
            [['break_minutes'], 'integer', 'min' => 0, 'max' => 720],
            [['status'], 'in', 'range' => ['planned', 'confirmed', 'cancelled']],
            [['comment'], 'string', 'max' => 5000],
            ['end_time', 'compare', 'compareAttribute' => 'start_time', 'operator' => '>', 'type' => 'string', 'message' => 'Окончание должно быть позже начала.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Сотрудник',
            'shift_type_id' => 'Тип смены',
            'work_date' => 'Дата',
            'start_time' => 'Начало',
            'end_time' => 'Окончание',
            'break_minutes' => 'Перерыв, минут',
            'status' => 'Статус',
            'comment' => 'Комментарий',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getShiftType(): ActiveQuery
    {
        return $this->hasOne(ShiftType::class, ['id' => 'shift_type_id']);
    }

    public function durationMinutes(): int
    {
        $start = strtotime('1970-01-01 ' . (string)$this->start_time);
        $end = strtotime('1970-01-01 ' . (string)$this->end_time);
        return max(0, (int)(($end - $start) / 60) - (int)$this->break_minutes);
    }
}
