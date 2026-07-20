<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\OAuthAccessToken;
use app\models\OAuthApp;
use app\models\OAuthAuthorizationCode;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Expression;

final class OAuthService
{
    public function generateClientId(): string
    {
        return 'oc_' . bin2hex(random_bytes(16));
    }

    public function generateClientSecret(): string
    {
        return 'ocs_' . bin2hex(random_bytes(32));
    }

    public function setClientSecret(OAuthApp $app, string $secret): void
    {
        $app->client_secret_hash = Yii::$app->security->generatePasswordHash($secret);
    }

    public function issueAuthorizationCode(
        OAuthApp $app,
        User $user,
        string $redirectUri,
        array $scopes,
        ?string $codeChallenge,
        ?string $codeChallengeMethod
    ): string {
        if (!$app->is_active || $app->workspace_id !== $user->workspace_id) {
            throw new RuntimeException('OAuth-приложение недоступно.');
        }
        if (!$app->allowsRedirect($redirectUri)) {
            throw new RuntimeException('Redirect URI не зарегистрирован для приложения.');
        }
        $scopes = $this->normalizeScopes($scopes);
        if (!$app->allowsScopes($scopes)) {
            throw new RuntimeException('Приложение не имеет запрошенных разрешений.');
        }
        if ($codeChallenge !== null && $codeChallenge !== '') {
            if ($codeChallengeMethod !== 'S256' || !preg_match('/^[A-Za-z0-9_-]{43,128}$/', $codeChallenge)) {
                throw new RuntimeException('Поддерживается только PKCE S256.');
            }
        } elseif (!$app->is_confidential) {
            throw new RuntimeException('Публичное приложение обязано использовать PKCE S256.');
        }

        $raw = 'oac_' . bin2hex(random_bytes(32));
        $code = new OAuthAuthorizationCode([
            'id' => BaseRecord::uuid(),
            'app_id' => $app->id,
            'user_id' => $user->id,
            'code_hash' => hash('sha256', $raw),
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes,
            'code_challenge' => $codeChallenge ?: null,
            'code_challenge_method' => $codeChallenge ? 'S256' : null,
            'expires_at' => gmdate('Y-m-d H:i:s.u', time() + 300),
        ]);
        if (!$code->save()) {
            throw new RuntimeException('Не удалось создать OAuth-код.');
        }
        return $raw;
    }

    public function exchangeAuthorizationCode(
        string $rawCode,
        string $clientId,
        ?string $clientSecret,
        string $redirectUri,
        ?string $codeVerifier
    ): array {
        $app = OAuthApp::findOne(['client_id' => $clientId, 'is_active' => true]);
        if (!$app) {
            throw new RuntimeException('Неизвестный OAuth client_id.');
        }
        $this->assertClient($app, $clientSecret);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $code = OAuthAuthorizationCode::findOne([
                'code_hash' => hash('sha256', $rawCode),
                'app_id' => $app->id,
            ]);
            if (!$code || $code->used_at !== null || strtotime((string)$code->expires_at) <= time()) {
                throw new RuntimeException('OAuth-код недействителен или истёк.');
            }
            if (!hash_equals((string)$code->redirect_uri, $redirectUri)) {
                throw new RuntimeException('Redirect URI не совпадает с OAuth-кодом.');
            }
            $this->assertPkce($code, $codeVerifier);
            $user = User::findOne([
                'id' => $code->user_id,
                'workspace_id' => $app->workspace_id,
                'status' => 'active',
            ]);
            if (!$user) {
                throw new RuntimeException('Пользователь OAuth-кода недоступен.');
            }

            $claimed = OAuthAuthorizationCode::updateAll(
                ['used_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['id' => $code->id, 'used_at' => null]
            );
            if ($claimed !== 1) {
                throw new RuntimeException('OAuth-код уже был использован.');
            }

            $issued = $this->createTokens($app, $user, $code->getScopes());
            $transaction->commit();
            return $issued;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    public function refresh(
        string $rawRefreshToken,
        string $clientId,
        ?string $clientSecret
    ): array {
        $app = OAuthApp::findOne(['client_id' => $clientId, 'is_active' => true]);
        if (!$app) {
            throw new RuntimeException('Неизвестный OAuth client_id.');
        }
        $this->assertClient($app, $clientSecret);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $existing = OAuthAccessToken::findOne([
                'app_id' => $app->id,
                'refresh_token_hash' => hash('sha256', $rawRefreshToken),
                'revoked_at' => null,
            ]);
            if (!$existing || !$existing->refresh_expires_at || strtotime((string)$existing->refresh_expires_at) <= time()) {
                throw new RuntimeException('Refresh token недействителен или истёк.');
            }
            $user = User::findOne([
                'id' => $existing->user_id,
                'workspace_id' => $app->workspace_id,
                'status' => 'active',
            ]);
            if (!$user) {
                throw new RuntimeException('Пользователь OAuth-токена недоступен.');
            }

            $claimed = OAuthAccessToken::updateAll(
                ['revoked_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['id' => $existing->id, 'revoked_at' => null]
            );
            if ($claimed !== 1) {
                throw new RuntimeException('Refresh token уже был использован или отозван.');
            }

            $issued = $this->createTokens($app, $user, $existing->getScopes());
            $transaction->commit();
            return $issued;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    /** @return array{0:User,1:OAuthAccessToken}|null */
    public function authenticate(string $rawToken): ?array
    {
        if (!str_starts_with($rawToken, 'oat_')) {
            return null;
        }
        $token = OAuthAccessToken::find()
            ->with(['user', 'app'])
            ->where(['token_hash' => hash('sha256', $rawToken), 'revoked_at' => null])
            ->one();
        if (
            !$token ||
            strtotime((string)$token->expires_at) <= time() ||
            !$token->user ||
            $token->user->status !== 'active' ||
            !$token->app ||
            !$token->app->is_active
        ) {
            return null;
        }
        $lastUsed = $token->last_used_at ? strtotime((string)$token->last_used_at) : 0;
        if ($lastUsed < time() - 300) {
            $token->updateAttributes(['last_used_at' => new Expression('CURRENT_TIMESTAMP(6)')]);
        }
        return [$token->user, $token];
    }

    private function createTokens(OAuthApp $app, User $user, array $scopes): array
    {
        $access = 'oat_' . bin2hex(random_bytes(32));
        $refresh = 'ort_' . bin2hex(random_bytes(32));
        $expiresIn = max(300, (int)env('OAUTH_ACCESS_TOKEN_TTL', 3600));
        $refreshTtl = max($expiresIn, (int)env('OAUTH_REFRESH_TOKEN_TTL', 30 * 86400));
        $model = new OAuthAccessToken([
            'id' => BaseRecord::uuid(),
            'app_id' => $app->id,
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $access),
            'refresh_token_hash' => hash('sha256', $refresh),
            'scopes' => $scopes,
            'expires_at' => gmdate('Y-m-d H:i:s.u', time() + $expiresIn),
            'refresh_expires_at' => gmdate('Y-m-d H:i:s.u', time() + $refreshTtl),
        ]);
        if (!$model->save()) {
            throw new RuntimeException('Не удалось выпустить OAuth-токен.');
        }
        return [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => $expiresIn,
            'refresh_token' => $refresh,
            'scope' => implode(' ', $scopes),
        ];
    }

    private function assertClient(OAuthApp $app, ?string $secret): void
    {
        if (!$app->is_confidential) {
            return;
        }
        if (
            !$secret ||
            !$app->client_secret_hash ||
            !Yii::$app->security->validatePassword($secret, (string)$app->client_secret_hash)
        ) {
            throw new RuntimeException('Неверный OAuth client_secret.');
        }
    }

    private function assertPkce(OAuthAuthorizationCode $code, ?string $verifier): void
    {
        if (!$code->code_challenge) {
            return;
        }
        if (!$verifier || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
            throw new RuntimeException('Требуется корректный PKCE code_verifier.');
        }
        $digest = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        if (!hash_equals((string)$code->code_challenge, $digest)) {
            throw new RuntimeException('PKCE-проверка не пройдена.');
        }
    }

    private function normalizeScopes(array $scopes): array
    {
        $allowed = ['read', 'write', 'create'];
        $scopes = array_values(array_unique(array_intersect(
            $allowed,
            array_map(static fn ($scope): string => trim((string)$scope), $scopes)
        )));
        return $scopes ?: ['read'];
    }
}
