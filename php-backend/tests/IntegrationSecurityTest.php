<?php

declare(strict_types=1);

namespace app\tests;

use app\models\BaseRecord;
use app\models\Event;
use app\models\OAuthAccessToken;
use app\models\OAuthApp;
use app\models\User;
use app\models\Webhook;
use app\models\WebhookDelivery;
use app\services\OAuthService;
use app\services\SecretVault;
use app\services\WebhookService;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yii;
use yii\db\Transaction;

final class IntegrationSecurityTest extends TestCase
{
    private Transaction $transaction;
    private string $workspaceId;
    private User $user;
    private string|false $previousSecret;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousSecret = getenv('APP_SECRET');
        putenv('APP_SECRET=integration-test-secret-key-with-more-than-32-characters');
        $this->transaction = Yii::$app->db->beginTransaction();
        $this->workspaceId = BaseRecord::uuid();
        $userId = BaseRecord::uuid();
        $now = gmdate('Y-m-d H:i:s.u');

        Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
            'id' => $this->workspaceId,
            'name' => 'Integration security tests',
            'default_language' => 'ru_RU',
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
        Yii::$app->db->createCommand()->insert('{{%users}}', [
            'id' => $userId,
            'workspace_id' => $this->workspaceId,
            'email' => 'integration@example.local',
            'login' => 'integration-user',
            'password_hash' => 'not-used',
            'auth_key' => bin2hex(random_bytes(32)),
            'last_name' => 'Интеграционный',
            'first_name' => 'Пользователь',
            'middle_name' => 'Тестовый',
            'role' => 'admin',
            'status' => 'active',
            'color' => '#6B7280',
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
        $this->user = User::findOne($userId);
        self::assertInstanceOf(User::class, $this->user);
    }

    protected function tearDown(): void
    {
        if ($this->transaction->isActive) {
            $this->transaction->rollBack();
        }
        if ($this->previousSecret === false) {
            putenv('APP_SECRET');
        } else {
            putenv('APP_SECRET=' . $this->previousSecret);
        }
        parent::tearDown();
    }

    public function testOAuthPkceCodeIsSingleUseAndRefreshRotates(): void
    {
        $service = new OAuthService();
        $app = new OAuthApp([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId,
            'name' => 'Public test app',
            'client_id' => $service->generateClientId(),
            'redirect_uris' => ['https://client.example.local/callback'],
            'scopes' => ['read', 'write'],
            'is_confidential' => false,
            'is_active' => true,
            'created_by_id' => $this->user->id,
        ]);
        self::assertTrue($app->save(), json_encode($app->getErrors(), JSON_UNESCAPED_UNICODE));

        $verifier = str_repeat('a', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $code = $service->issueAuthorizationCode(
            $app,
            $this->user,
            'https://client.example.local/callback',
            ['read', 'write'],
            $challenge,
            'S256'
        );
        self::assertStringStartsWith('oac_', $code);

        $tokens = $service->exchangeAuthorizationCode(
            $code,
            (string)$app->client_id,
            null,
            'https://client.example.local/callback',
            $verifier
        );
        self::assertStringStartsWith('oat_', $tokens['access_token']);
        self::assertStringStartsWith('ort_', $tokens['refresh_token']);
        self::assertSame('read write', $tokens['scope']);
        self::assertNotNull($service->authenticate($tokens['access_token']));

        $this->expectException(RuntimeException::class);
        $service->exchangeAuthorizationCode(
            $code,
            (string)$app->client_id,
            null,
            'https://client.example.local/callback',
            $verifier
        );
    }

    public function testOAuthRejectsWrongPkceAndRefreshTokenCannotBeReused(): void
    {
        $service = new OAuthService();
        $app = new OAuthApp([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId,
            'name' => 'Confidential test app',
            'client_id' => $service->generateClientId(),
            'redirect_uris' => ['https://client.example.local/callback'],
            'scopes' => ['read'],
            'is_confidential' => true,
            'is_active' => true,
            'created_by_id' => $this->user->id,
        ]);
        $clientSecret = $service->generateClientSecret();
        $service->setClientSecret($app, $clientSecret);
        self::assertTrue($app->save(), json_encode($app->getErrors(), JSON_UNESCAPED_UNICODE));

        $verifier = str_repeat('b', 43);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $code = $service->issueAuthorizationCode(
            $app,
            $this->user,
            'https://client.example.local/callback',
            ['read'],
            $challenge,
            'S256'
        );
        try {
            $service->exchangeAuthorizationCode(
                $code,
                (string)$app->client_id,
                $clientSecret,
                'https://client.example.local/callback',
                str_repeat('c', 43)
            );
            self::fail('Wrong PKCE verifier must be rejected');
        } catch (RuntimeException) {
            self::assertSame(0, (int)OAuthAccessToken::find()->where(['app_id' => $app->id])->count());
        }

        $tokens = $service->exchangeAuthorizationCode(
            $code,
            (string)$app->client_id,
            $clientSecret,
            'https://client.example.local/callback',
            $verifier
        );
        $refreshed = $service->refresh(
            $tokens['refresh_token'],
            (string)$app->client_id,
            $clientSecret
        );
        self::assertNotSame($tokens['access_token'], $refreshed['access_token']);

        $this->expectException(RuntimeException::class);
        $service->refresh(
            $tokens['refresh_token'],
            (string)$app->client_id,
            $clientSecret
        );
    }

    public function testWebhookSecretIsEncryptedAndMatchingEventIsQueuedOnce(): void
    {
        $secret = (new WebhookService())->generateSecret();
        $encrypted = (new WebhookService())->encryptSecret($secret);
        self::assertNotSame($secret, $encrypted);
        self::assertSame($secret, (new SecretVault())->decrypt($encrypted));

        $webhook = new Webhook([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId,
            'name' => 'Audit webhook',
            'url' => 'https://hooks.example.com/outline',
            'events' => ['document.updated'],
            'secret_encrypted' => $encrypted,
            'is_active' => true,
            'created_by_id' => $this->user->id,
        ]);
        self::assertTrue($webhook->save(), json_encode($webhook->getErrors(), JSON_UNESCAPED_UNICODE));

        $event = new Event([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $this->workspaceId,
            'user_id' => $this->user->id,
            'name' => 'document.updated',
            'model_id' => BaseRecord::uuid(),
            'data' => ['revision' => 2],
        ]);
        self::assertTrue($event->save(), json_encode($event->getErrors(), JSON_UNESCAPED_UNICODE));

        $service = new WebhookService();
        self::assertSame(1, $service->enqueue($event));
        self::assertSame(1, (int)WebhookDelivery::find()->where([
            'webhook_id' => $webhook->id,
            'event_id' => $event->id,
            'event_name' => 'document.updated',
        ])->count());
    }
}
