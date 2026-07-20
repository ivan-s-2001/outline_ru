<?php

declare(strict_types=1);
namespace app\services;

use app\models\Event;
use app\models\User;
use Yii;
use yii\web\Request as WebRequest;

final class AuditService
{
    public function record(
        string $workspaceId,
        string $name,
        ?User $user = null,
        ?string $modelId = null,
        array $data = []
    ): void {
        $request = Yii::$app->has('request') ? Yii::$app->request : null;
        $ip = $request instanceof WebRequest ? $request->userIP : null;

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
        $blocked = ['password', 'passwordhash', 'token', 'secret', 'authorization'];
        $result = [];
        foreach ($data as $key => $value) {
            if (in_array(mb_strtolower((string)$key), $blocked, true)) {
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
