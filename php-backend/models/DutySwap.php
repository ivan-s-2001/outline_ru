<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class DutySwap extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%duty_swaps}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'from_assignment_id', 'to_assignment_id', 'requested_by_id'], 'required'],
            [['workspace_id', 'from_assignment_id', 'to_assignment_id', 'requested_by_id', 'approved_by_id'], 'string', 'max' => 36],
            [['status'], 'in', 'range' => ['pending', 'approved', 'rejected', 'cancelled']],
        ];
    }

    public function getFromAssignment(): ActiveQuery
    {
        return $this->hasOne(DutyAssignment::class, ['id' => 'from_assignment_id']);
    }

    public function getToAssignment(): ActiveQuery
    {
        return $this->hasOne(DutyAssignment::class, ['id' => 'to_assignment_id']);
    }
}
