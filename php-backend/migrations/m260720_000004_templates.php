<?php

declare(strict_types=1);
use yii\db\Migration;

final class m260720_000004_templates extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%templates}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'content_json' => $this->json(),
            'content_text' => 'LONGTEXT NULL',
            'created_by_id' => $this->char(36)->notNull(),
            'updated_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);

        $this->createIndex('ix_templates_workspace_name', '{{%templates}}', ['workspace_id', 'name']);
        $this->addForeignKey('fk_templates_workspace', '{{%templates}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_templates_creator', '{{%templates}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_templates_updater', '{{%templates}}', 'updated_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%templates}}');
    }
}
