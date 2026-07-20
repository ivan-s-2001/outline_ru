<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\Event;
use app\models\Webhook;
use app\models\WebhookDelivery;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Expression;

final class WebhookService
{
    public function generateSecret(): string
    {
        return 'whsec_' . bin2hex(random_bytes(32));
    }

    public function encryptSecret(string $secret): string
    {
        return (new SecretVault())->encrypt($secret);
    }

    public function enqueue(Event $event): int
    {
        $webhooks = Webhook::find()->where([
            'workspace_id' => $event->workspace_id,
            'is_active' => true,
        ])->all();
        $count = 0;
        foreach ($webhooks as $webhook) {
            if (!$webhook->accepts((string)$event->name)) {
                continue;
            }
            $delivery = new WebhookDelivery([
                'id' => BaseRecord::uuid(),
                'webhook_id' => $webhook->id,
                'event_id' => $event->id,
                'event_name' => $event->name,
                'payload' => [
                    'id' => $event->id,
                    'event' => $event->name,
                    'workspaceId' => $event->workspace_id,
                    'actorId' => $event->user_id,
                    'modelId' => $event->model_id,
                    'data' => $event->getDataArray(),
                    'createdAt' => $event->created_at,
                ],
                'status' => 'pending',
                'attempts' => 0,
                'next_attempt_at' => gmdate('Y-m-d H:i:s.u'),
            ]);
            if ($delivery->save()) {
                $count++;
            }
        }
        return $count;
    }

    public function run(int $limit = 50): array
    {
        $deliveries = WebhookDelivery::find()
            ->with('webhook')
            ->where(['in', 'status', ['pending', 'failed']])
            ->andWhere(['or', ['next_attempt_at' => null], ['<=', 'next_attempt_at', new Expression('CURRENT_TIMESTAMP(6)')]])
            ->orderBy(['created_at' => SORT_ASC])
            ->limit(max(1, min(500, $limit)))
            ->all();
        $result = ['delivered' => 0, 'failed' => 0, 'skipped' => 0];
        foreach ($deliveries as $delivery) {
            if (!$delivery->webhook || !$delivery->webhook->is_active) {
                $delivery->updateAttributes(['status' => 'failed', 'last_error' => 'Webhook disabled or removed']);
                $result['skipped']++;
                continue;
            }
            try {
                if ($this->deliver($delivery)) {
                    $result['delivered']++;
                } else {
                    $result['failed']++;
                }
            } catch (Throwable $error) {
                $this->fail($delivery, $error->getMessage());
                $result['failed']++;
            }
        }
        return $result;
    }

    public function deliver(WebhookDelivery $delivery): bool
    {
        $webhook = $delivery->webhook ?? Webhook::findOne($delivery->webhook_id);
        if (!$webhook || !$webhook->is_active) {
            throw new RuntimeException('Webhook отключён.');
        }
        (new UrlGuard())->assertPublicHttpUrl((string)$webhook->url);
        $secret = (new SecretVault())->decrypt((string)$webhook->secret_encrypted);
        $body = json_encode(
            $delivery->getPayloadValue(),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        $signature = hash_hmac('sha256', $body, $secret);
        $delivery->updateAttributes([
            'status' => 'processing',
            'attempts' => (int)$delivery->attempts + 1,
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Outline-Yii-Webhook/1.0',
            'X-Outline-Event: ' . $delivery->event_name,
            'X-Outline-Delivery: ' . $delivery->id,
            'X-Outline-Signature: sha256=' . $signature,
        ];
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'timeout' => 10,
                'ignore_errors' => true,
                'follow_location' => 0,
                'max_redirects' => 0,
            ],
        ]);
        $response = @file_get_contents((string)$webhook->url, false, $context);
        $statusCode = $this->statusCode($http_response_header ?? []);
        if ($statusCode >= 200 && $statusCode < 300) {
            $delivery->updateAttributes([
                'status' => 'delivered',
                'last_status_code' => $statusCode,
                'last_error' => null,
                'delivered_at' => new Expression('CURRENT_TIMESTAMP(6)'),
                'next_attempt_at' => null,
                'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            ]);
            return true;
        }

        $preview = is_string($response) ? mb_substr(trim($response), 0, 1000) : 'No response body';
        $this->fail($delivery, sprintf('HTTP %d: %s', $statusCode, $preview), $statusCode);
        return false;
    }

    private function fail(WebhookDelivery $delivery, string $error, ?int $statusCode = null): void
    {
        $attempts = max(1, (int)$delivery->attempts);
        $permanent = $attempts >= 8;
        $delay = min(86400, 60 * (2 ** min(10, $attempts - 1)));
        $delivery->updateAttributes([
            'status' => 'failed',
            'last_status_code' => $statusCode,
            'last_error' => mb_substr($error, 0, 10000),
            'next_attempt_at' => $permanent ? null : gmdate('Y-m-d H:i:s.u', time() + $delay),
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        Yii::warning(['message' => 'Webhook delivery failed', 'deliveryId' => $delivery->id, 'error' => $error], __METHOD__);
    }

    private function statusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', (string)$header, $match)) {
                return (int)$match[1];
            }
        }
        return 0;
    }
}
