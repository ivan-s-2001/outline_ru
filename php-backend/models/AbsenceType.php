<?php

declare(strict_types=1);

namespace app\models;

final class AbsenceType extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%absence_types}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'code', 'color'], 'required'],
            [['workspace_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 120],
            [['code'], 'string', 'max' => 32],
            [['color'], 'match', 'pattern' => '/^#[0-9A-Fa-f]{6}$/'],
            [['is_paid', 'is_active'], 'boolean'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название',
            'code' => 'Код',
            'color' => 'Цвет',
            'is_paid' => 'Оплачиваемое',
            'is_active' => 'Активен',
        ];
    }
}
