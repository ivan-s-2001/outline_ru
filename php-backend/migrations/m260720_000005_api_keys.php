<?php

declare(strict_types=1);
use yii\db\Migration;

final class m260720_000005_api_keys extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%api_keys}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'token_prefix' => $this->string(24)->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'permission' => $this->string(16)->notNull()->defaultValue('read'),
            'last_used_at' => $this->dateTime(6),
            'expires_at' => $this->dateTime(6),
            'revoked_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);

        $this->createIndex('ux_api_keys_hash', '{{%api_keys}}', 'token_hash', true);
        $this->createIndex('ix_api_keys_user_active', '{{%api_keys}}', ['user_id', 'revoked_at']);
        $this->addForeignKey('fk_api_keys_workspace', '{{%api_keys}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_api_keys_user', '{{%api_keys}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%api_keys}}');
    }
}
