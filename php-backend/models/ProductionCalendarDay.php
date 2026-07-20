<?php

declare(strict_types=1);

namespace app\models;

final class ProductionCalendarDay extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%production_calendar_days}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'calendar_date', 'is_working', 'source'], 'required'],
            [['workspace_id'], 'string', 'max' => 36],
            [['calendar_date'], 'safe'],
            [['is_working', 'is_shortened'], 'boolean'],
            [['name', 'source'], 'string', 'max' => 255],
            [['source_version'], 'string', 'max' => 120],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'calendar_date' => 'Дата',
            'is_working' => 'Рабочий день',
            'is_shortened' => 'Сокращённый день',
            'name' => 'Название',
            'source' => 'Источник',
            'source_version' => 'Версия источника',
        ];
    }
}
