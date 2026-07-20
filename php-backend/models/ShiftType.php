<?php

declare(strict_types=1);

namespace app\models;

final class ShiftType extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%shift_types}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'code', 'color'], 'required'],
            [['workspace_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 120],
            [['code'], 'string', 'max' => 32],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
            [['default_start_time', 'default_end_time'], 'safe'],
            [['default_break_minutes'], 'integer', 'min' => 0, 'max' => 720],
            [['is_active'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название',
            'code' => 'Код',
            'color' => 'Цвет',
            'default_start_time' => 'Начало',
            'default_end_time' => 'Окончание',
            'default_break_minutes' => 'Перерыв, минут',
            'is_active' => 'Активен',
        ];
    }
}
