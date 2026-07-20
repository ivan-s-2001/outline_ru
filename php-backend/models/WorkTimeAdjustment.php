<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;

final class WorkTimeAdjustment extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%work_time_adjustments}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'user_id', 'work_date', 'minutes', 'created_by_id'], 'required'],
            [['workspace_id', 'user_id', 'created_by_id'], 'string', 'max' => 36],
            [['work_date'], 'safe'],
            [['minutes'], 'integer', 'min' => -1440, 'max' => 1440],
            [['kind'], 'in', 'range' => ['overtime', 'undertime', 'manual']],
            [['comment'], 'string', 'max' => 5000],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'user_id' => 'Сотрудник',
            'work_date' => 'Дата',
            'minutes' => 'Минуты',
            'kind' => 'Тип',
            'comment' => 'Комментарий',
        ];
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
