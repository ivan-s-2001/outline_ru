<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class Absence extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%absences}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'absence_type_id', 'date_from', 'date_to', 'created_by_id'], 'required'],
            [['workspace_id', 'user_id', 'absence_type_id', 'created_by_id', 'approved_by_id'], 'string', 'max' => 36],
            [['date_from', 'date_to', 'start_time', 'end_time'], 'safe'],
            [['status'], 'in', 'range' => ['pending', 'approved', 'rejected', 'cancelled']],
            [['comment'], 'string', 'max' => 5000],
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'string', 'message' => 'Дата окончания не может быть раньше начала.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Сотрудник',
            'absence_type_id' => 'Тип отсутствия',
            'date_from' => 'С',
            'date_to' => 'По',
            'start_time' => 'Начало части дня',
            'end_time' => 'Окончание части дня',
            'status' => 'Статус',
            'comment' => 'Комментарий',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getAbsenceType(): ActiveQuery
    {
        return $this->hasOne(AbsenceType::class, ['id' => 'absence_type_id']);
    }
}
