<?php

declare(strict_types=1);
namespace app\services;

use app\models\Event;
use app\models\User;
use Yii;

final class AuditService
{
    public function record(
        string $workspaceId,
        string $name,
        ?User $user = null,
        ?string $modelId = null,
        array $data = []
    ): void {
        $ip = null;
        if (Yii::$app->has('request')) {
            $ip = Yii::$app->request->userIP;
        }

        $event = new Event([
            'workspace_id' => $workspaceId,
            'user_id' => $user?->id,
            'name' => $name,
            'model_id' => $modelId,
            'ip' => $ip,
            'data' => $this->sanitize($data),
        ]);
        if (!$event->save()) {
            Yii::warning([
                'message' => 'Unable to save audit event',
                'event' => $name,
                'errors' => $event->getErrors(),
            ], __METHOD__);
        }
    }

    private function sanitize(array $data): array
    {
        $blocked = ['password', 'passwordHash', 'token', 'secret', 'authorization'];
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array(mb_strtolower((string)$key), array_map('mb_strtolower', $blocked), true)) {
                continue;
            }
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
