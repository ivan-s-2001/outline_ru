<?php

declare(strict_types=1);
namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Event extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%events}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name'], 'required'],
            [['data'], 'safe'],
            [['workspace_id', 'user_id', 'model_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 64],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->data)) {
            $this->data = json_encode(
                $this->data,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        }
        return parent::beforeSave($insert);
    }

    public function getDataArray(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        if (!is_string($this->data) || trim($this->data) === '') {
            return [];
        }
        try {
            $decoded = json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function getUser(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }
}
