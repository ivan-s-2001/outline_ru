<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class VacationRequest extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%vacation_requests}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'date_from', 'date_to', 'working_days', 'calendar_source', 'requested_by_id'], 'required'],
            [['workspace_id', 'user_id', 'requested_by_id', 'approved_by_id'], 'string', 'max' => 36],
            [['date_from', 'date_to'], 'safe'],
            [['working_days'], 'number', 'min' => 0, 'max' => 366],
            [['status'], 'in', 'range' => ['pending', 'approved', 'rejected', 'cancelled']],
            [['calendar_source'], 'string', 'max' => 255],
            [['calendar_version'], 'string', 'max' => 120],
            [['comment'], 'string', 'max' => 5000],
            ['date_to', 'compare', 'compareAttribute' => 'date_from', 'operator' => '>=', 'type' => 'string', 'message' => 'Дата окончания не может быть раньше начала.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Сотрудник',
            'date_from' => 'Дата начала',
            'date_to' => 'Дата окончания',
            'working_days' => 'Рабочих дней',
            'status' => 'Статус',
            'comment' => 'Комментарий',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getRequester(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'requested_by_id']);
    }

    public function getApprover(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'approved_by_id']);
    }
}
