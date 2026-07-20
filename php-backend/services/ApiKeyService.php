<?php

declare(strict_types=1);
namespace app\services;

use app\models\ApiKey;
use app\models\User;
use RuntimeException;
use yii\db\Expression;

final class ApiKeyService
{
    public function create(
        User $user,
        string $name,
        string $permission,
        int $expiresInDays = 0
    ): array {
        $secret = 'ol_yii_' . $this->base64Url(random_bytes(32));
        $expiresAt = $expiresInDays > 0
            ? gmdate('Y-m-d H:i:s.u', time() + $expiresInDays * 86400)
            : null;

        $model = new ApiKey([
            'workspace_id' => $user->workspace_id,
            'user_id' => $user->id,
            'name' => trim($name),
            'token_prefix' => mb_substr($secret, 0, 18),
            'token_hash' => hash('sha256', $secret),
            'permission' => $permission,
            'expires_at' => $expiresAt,
        ]);
        if (!$model->save()) {
            throw new RuntimeException($this->firstError($model->getFirstErrors()));
        }

        (new AuditService())->record(
            (string)$user->workspace_id,
            'api_key.created',
            $user,
            (string)$model->id,
            [
                'name' => $model->name,
                'permission' => $model->permission,
                'prefix' => $model->token_prefix,
                'expiresAt' => $model->expires_at,
            ]
        );

        return [$model, $secret];
    }

    public function authenticate(string $secret): ?array
    {
        $secret = trim($secret);
        if (!preg_match('/^ol_yii_[A-Za-z0-9_-]{40,80}$/', $secret)) {
            return null;
        }

        $model = ApiKey::find()
            ->with('user')
            ->where(['token_hash' => hash('sha256', $secret), 'revoked_at' => null])
            ->one();
        if (!$model || !$model->isActive() || !$model->user || $model->user->status !== 'active') {
            return null;
        }
        if ($model->workspace_id !== $model->user->workspace_id) {
            return null;
        }

        $lastUsed = $model->last_used_at ? strtotime((string)$model->last_used_at) : false;
        if ($lastUsed === false || $lastUsed < time() - 300) {
            $model->updateAttributes(['last_used_at' => new Expression('CURRENT_TIMESTAMP(6)')]);
        }

        return [$model->user, $model];
    }

    public function revoke(ApiKey $model, User $actor): void
    {
        if ($model->revoked_at !== null) {
            return;
        }
        $model->updateAttributes([
            'revoked_at' => new Expression('CURRENT_TIMESTAMP(6)'),
            'updated_at' => new Expression('CURRENT_TIMESTAMP(6)'),
        ]);
        (new AuditService())->record(
            (string)$model->workspace_id,
            'api_key.revoked',
            $actor,
            (string)$model->id,
            [
                'name' => $model->name,
                'permission' => $model->permission,
                'prefix' => $model->token_prefix,
            ]
        );
    }

    private function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось создать API-ключ.';
    }
}
