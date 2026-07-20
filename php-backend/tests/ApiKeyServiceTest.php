<?php

declare(strict_types=1);
namespace app\tests;

use app\models\ApiKey;
use app\models\BaseRecord;
use app\models\Event;
use app\models\User;
use app\services\ApiKeyService;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Transaction;

final class ApiKeyServiceTest extends TestCase
{
    private Transaction $transaction;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction = Yii::$app->db->beginTransaction();
        $workspaceId = BaseRecord::uuid();
        $userId = BaseRecord::uuid();
        $suffix = substr(str_replace('-', '', $userId), 0, 12);
        $now = gmdate('Y-m-d H:i:s.u');

        Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
            'id' => $workspaceId,
            'name' => 'API test workspace',
            'default_language' => 'ru_RU',
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();
        Yii::$app->db->createCommand()->insert('{{%users}}', [
            'id' => $userId,
            'workspace_id' => $workspaceId,
            'email' => 'api-' . $suffix . '@example.local',
            'login' => 'api-' . $suffix,
            'password_hash' => 'not-used-in-this-test',
            'auth_key' => bin2hex(random_bytes(32)),
            'last_name' => 'Тестов',
            'first_name' => 'Api',
            'middle_name' => 'Ключевич',
            'role' => 'member',
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
        parent::tearDown();
    }

    public function testCreatesHashedReadKeyAndAuthenticatesIt(): void
    {
        [$key, $secret] = (new ApiKeyService())->create(
            $this->user,
            'Read integration',
            'read',
            90
        );

        self::assertStringStartsWith('ol_yii_', $secret);
        self::assertSame(hash('sha256', $secret), $key->token_hash);
        self::assertNotSame($secret, $key->token_hash);
        self::assertFalse($key->canWrite());

        $authenticated = (new ApiKeyService())->authenticate($secret);
        self::assertNotNull($authenticated);
        self::assertSame($this->user->id, $authenticated[0]->id);
        self::assertSame($key->id, $authenticated[1]->id);
        self::assertSame(1, (int)Event::find()->where([
            'workspace_id' => $this->user->workspace_id,
            'name' => 'api_key.created',
            'model_id' => $key->id,
        ])->count());
    }

    public function testRevokedWriteKeyStopsAuthenticating(): void
    {
        [$key, $secret] = (new ApiKeyService())->create(
            $this->user,
            'Write integration',
            'write',
            0
        );
        self::assertTrue($key->canWrite());

        (new ApiKeyService())->revoke($key, $this->user);
        self::assertNull((new ApiKeyService())->authenticate($secret));

        $stored = ApiKey::findOne($key->id);
        self::assertNotNull($stored?->revoked_at);
        self::assertSame(1, (int)Event::find()->where([
            'workspace_id' => $this->user->workspace_id,
            'name' => 'api_key.revoked',
            'model_id' => $key->id,
        ])->count());
    }

    public function testExpiredAndMalformedKeysAreRejected(): void
    {
        [$key, $secret] = (new ApiKeyService())->create(
            $this->user,
            'Expired integration',
            'read',
            30
        );
        $key->updateAttributes(['expires_at' => '2000-01-01 00:00:00.000000']);

        self::assertNull((new ApiKeyService())->authenticate($secret));
        self::assertNull((new ApiKeyService())->authenticate('not-a-key'));
    }
}
