<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class WebhookDelivery extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%webhook_deliveries}}';
    }

    public function rules(): array
    {
        return [
            [['webhook_id', 'event_name', 'payload'], 'required'],
            [['webhook_id', 'event_id'], 'string', 'max' => 36],
            [['event_name'], 'string', 'max' => 255],
            [['payload'], 'safe'],
            [['status'], 'in', 'range' => ['pending', 'processing', 'delivered', 'failed']],
            [['attempts', 'last_status_code'], 'integer'],
            [['next_attempt_at', 'delivered_at'], 'safe'],
            [['last_error'], 'string'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->payload)) {
            $this->payload = json_encode($this->payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return parent::beforeSave($insert);
    }

    public function getPayloadValue(): array
    {
        if (is_array($this->payload)) {
            return $this->payload;
        }
        try {
            $value = json_decode((string)$this->payload, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? $value : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function getWebhook(): ActiveQuery
    {
        return $this->hasOne(Webhook::class, ['id' => 'webhook_id']);
    }
}
