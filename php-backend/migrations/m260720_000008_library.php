<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000008_library extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%favorites}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36)->notNull(),
            'sort_order' => $this->string(255),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_favorites_user_document', '{{%favorites}}', ['user_id', 'document_id'], true);
        $this->createIndex('ix_favorites_workspace_user', '{{%favorites}}', ['workspace_id', 'user_id']);
        $this->addForeignKey('fk_favorites_workspace', '{{%favorites}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_favorites_user', '{{%favorites}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_favorites_document', '{{%favorites}}', 'document_id', '{{%documents}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%favorites}}');
    }
}
