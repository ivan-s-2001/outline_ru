<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class DutyParticipant extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%duty_participants}}';
    }

    public function rules(): array
    {
        return [
            [['duty_setting_id', 'user_id', 'coefficient'], 'required'],
            [['duty_setting_id', 'user_id'], 'string', 'max' => 36],
            [['coefficient'], 'number', 'min' => 0, 'max' => 10],
            [['is_excluded'], 'boolean'],
            [['schedule_snapshot'], 'safe'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->schedule_snapshot)) {
            $this->schedule_snapshot = json_encode(
                $this->schedule_snapshot,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        return parent::beforeSave($insert);
    }

    public function getScheduleSnapshotData(): array
    {
        if (is_array($this->schedule_snapshot)) {
            return $this->schedule_snapshot;
        }
        if (!is_string($this->schedule_snapshot) || $this->schedule_snapshot === '') {
            return [];
        }
        try {
            $value = json_decode($this->schedule_snapshot, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? $value : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getSetting(): ActiveQuery
    {
        return $this->hasOne(DutySetting::class, ['id' => 'duty_setting_id']);
    }
}
