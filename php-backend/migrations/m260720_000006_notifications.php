<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000006_notifications extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%notifications}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'actor_id' => $this->char(36),
            'type' => $this->string(64)->notNull(),
            'document_id' => $this->char(36),
            'comment_id' => $this->char(36),
            'unique_key' => $this->string(255)->notNull(),
            'data' => $this->json(),
            'read_at' => $this->dateTime(6),
            'archived_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);

        $this->createIndex('ux_notifications_unique_key', '{{%notifications}}', 'unique_key', true);
        $this->createIndex(
            'ix_notifications_inbox',
            '{{%notifications}}',
            ['user_id', 'archived_at', 'read_at', 'created_at']
        );
        $this->addForeignKey(
            'fk_notifications_workspace',
            '{{%notifications}}',
            'workspace_id',
            '{{%workspaces}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_notifications_user',
            '{{%notifications}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_notifications_actor',
            '{{%notifications}}',
            'actor_id',
            '{{%users}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_notifications_document',
            '{{%notifications}}',
            'document_id',
            '{{%documents}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk_notifications_comment',
            '{{%notifications}}',
            'comment_id',
            '{{%comments}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%notifications}}');
    }
}
