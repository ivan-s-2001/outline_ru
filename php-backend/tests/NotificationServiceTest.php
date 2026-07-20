<?php

declare(strict_types=1);

namespace app\tests;

use app\models\BaseRecord;
use app\models\Document;
use app\models\Notification;
use app\models\User;
use app\services\NotificationService;
use PHPUnit\Framework\TestCase;
use Yii;
use yii\db\Transaction;

final class NotificationServiceTest extends TestCase
{
    private Transaction $transaction;
    private User $actor;
    private User $recipient;
    private Document $document;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transaction = Yii::$app->db->beginTransaction();
        $workspaceId = BaseRecord::uuid();
        $actorId = BaseRecord::uuid();
        $recipientId = BaseRecord::uuid();
        $collectionId = BaseRecord::uuid();
        $documentId = BaseRecord::uuid();
        $now = gmdate('Y-m-d H:i:s.u');

        Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
            'id' => $workspaceId,
            'name' => 'Notification test workspace',
            'default_language' => 'ru_RU',
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        foreach ([
            [$actorId, 'actor', 'Автор', 'Тестовый'],
            [$recipientId, 'recipient', 'Получатель', 'Тестовый'],
        ] as [$id, $login, $firstName, $lastName]) {
            Yii::$app->db->createCommand()->insert('{{%users}}', [
                'id' => $id,
                'workspace_id' => $workspaceId,
                'email' => $login . '@example.local',
                'login' => $login,
                'password_hash' => 'not-used',
                'auth_key' => bin2hex(random_bytes(32)),
                'last_name' => $lastName,
                'first_name' => $firstName,
                'middle_name' => 'Уведомительный',
                'role' => 'member',
                'status' => 'active',
                'color' => '#6B7280',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();
        }

        Yii::$app->db->createCommand()->insert('{{%collections}}', [
            'id' => $collectionId,
            'workspace_id' => $workspaceId,
            'name' => 'Общая коллекция',
            'permission' => 'read_write',
            'created_by_id' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        $content = [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [[
                    'type' => 'mention',
                    'attrs' => [
                        'id' => 'mention-instance-1',
                        'type' => 'user',
                        'modelId' => $recipientId,
                        'label' => 'Получатель Тестовый',
                    ],
                ]],
            ]],
        ];
        Yii::$app->db->createCommand()->insert('{{%documents}}', [
            'id' => $documentId,
            'workspace_id' => $workspaceId,
            'collection_id' => $collectionId,
            'title' => 'Документ с упоминанием',
            'content_json' => json_encode($content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'content_text' => '@Получатель Тестовый',
            'revision_number' => 1,
            'created_by_id' => $actorId,
            'updated_by_id' => $actorId,
            'created_at' => $now,
            'updated_at' => $now,
        ])->execute();

        $this->actor = User::findOne($actorId);
        $this->recipient = User::findOne($recipientId);
        $this->document = Document::find()->with('collection')->where(['id' => $documentId])->one();
        self::assertInstanceOf(User::class, $this->actor);
        self::assertInstanceOf(User::class, $this->recipient);
        self::assertInstanceOf(Document::class, $this->document);
    }

    protected function tearDown(): void
    {
        if ($this->transaction->isActive) {
            $this->transaction->rollBack();
        }
        parent::tearDown();
    }

    public function testCreatesOneNotificationForNewMention(): void
    {
        $service = new NotificationService();
        $service->syncDocumentMentions($this->document, $this->actor, []);
        $service->syncDocumentMentions($this->document, $this->actor, []);

        $notifications = Notification::find()->where([
            'user_id' => $this->recipient->id,
            'document_id' => $this->document->id,
            'type' => 'document_mention',
        ])->all();

        self::assertCount(1, $notifications);
        self::assertSame($this->actor->id, $notifications[0]->actor_id);
        self::assertSame('mention-instance-1', $notifications[0]->getDataValue()['mentionId']);
    }

    public function testExistingMentionDoesNotCreateNotification(): void
    {
        $content = $this->document->getContentData();
        (new NotificationService())->syncDocumentMentions(
            $this->document,
            $this->actor,
            $content
        );

        self::assertSame(0, (int)Notification::find()->where([
            'user_id' => $this->recipient->id,
        ])->count());
    }

    public function testMarkAllReadUpdatesInbox(): void
    {
        $service = new NotificationService();
        $service->syncDocumentMentions($this->document, $this->actor, []);
        self::assertSame(1, $service->markAllRead($this->recipient));

        $notification = Notification::find()->where(['user_id' => $this->recipient->id])->one();
        self::assertNotNull($notification?->read_at);
    }
}
