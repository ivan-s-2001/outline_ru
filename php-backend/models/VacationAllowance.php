<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class VacationAllowance extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%vacation_allowances}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'allowance_year', 'allowance_days'], 'required'],
            [['workspace_id', 'user_id'], 'string', 'max' => 36],
            [['allowance_year'], 'integer', 'min' => 2000, 'max' => 2200],
            [['allowance_days', 'carried_days'], 'number', 'min' => -365, 'max' => 365],
            [['comment'], 'string', 'max' => 5000],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Сотрудник',
            'allowance_year' => 'Год',
            'allowance_days' => 'Дней отпуска',
            'carried_days' => 'Перенесено дней',
            'comment' => 'Комментарий',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function totalDays(): float
    {
        return (float)$this->allowance_days + (float)$this->carried_days;
    }
}
