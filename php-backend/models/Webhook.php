<?php

declare(strict_types=1);

namespace app\models;

use JsonException;
use yii\db\ActiveQuery;

final class Webhook extends BaseRecord
{
    public static function tableName(): string
    {
        return '{{%webhooks}}';
    }

    public function rules(): array
    {
        return [
            [['workspace_id', 'name', 'url', 'events', 'secret_encrypted', 'created_by_id'], 'required'],
            [['workspace_id', 'created_by_id'], 'string', 'max' => 36],
            [['name'], 'string', 'max' => 255],
            [['url'], 'url', 'validSchemes' => ['https', 'http']],
            [['events'], 'safe'],
            [['secret_encrypted'], 'string'],
            [['is_active'], 'boolean'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (is_array($this->events)) {
            $this->events = json_encode($this->events, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return parent::beforeSave($insert);
    }

    public function getEventsValue(): array
    {
        if (is_array($this->events)) {
            return $this->events;
        }
        try {
            $value = json_decode((string)$this->events, true, 512, JSON_THROW_ON_ERROR);
            return is_array($value) ? array_values(array_filter($value, 'is_string')) : [];
        } catch (JsonException) {
            return [];
        }
    }

    public function accepts(string $event): bool
    {
        $events = $this->getEventsValue();
        return in_array('*', $events, true) || in_array($event, $events, true);
    }

    public function getDeliveries(): ActiveQuery
    {
        return $this->hasMany(WebhookDelivery::class, ['webhook_id' => 'id']);
    }
}
