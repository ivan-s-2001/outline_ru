<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000010_mail extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%invitations}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'email' => $this->string(255)->notNull(),
            'role' => $this->string(32)->notNull()->defaultValue('member'),
            'token_hash' => $this->char(64)->notNull(),
            'invited_by_id' => $this->char(36)->notNull(),
            'expires_at' => $this->dateTime(6)->notNull(),
            'accepted_at' => $this->dateTime(6),
            'revoked_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_invitations_token', '{{%invitations}}', 'token_hash', true);
        $this->createIndex('ix_invitations_workspace_email', '{{%invitations}}', ['workspace_id', 'email']);
        $this->addForeignKey('fk_invitations_workspace', '{{%invitations}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_invitations_inviter', '{{%invitations}}', 'invited_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%password_reset_tokens}}', [
            'id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'expires_at' => $this->dateTime(6)->notNull(),
            'used_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_password_reset_token', '{{%password_reset_tokens}}', 'token_hash', true);
        $this->createIndex('ix_password_reset_user', '{{%password_reset_tokens}}', ['user_id', 'used_at', 'expires_at']);
        $this->addForeignKey('fk_password_reset_user', '{{%password_reset_tokens}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%email_jobs}}', [
            'id' => $this->char(36)->notNull(),
            'to_email' => $this->string(255)->notNull(),
            'subject' => $this->string(998)->notNull(),
            'text_body' => 'LONGTEXT NOT NULL',
            'html_body' => 'LONGTEXT NULL',
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'next_attempt_at' => $this->dateTime(6),
            'last_error' => $this->text(),
            'sent_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_email_jobs_queue', '{{%email_jobs}}', ['status', 'next_attempt_at', 'created_at']);
    }

    public function safeDown(): void
    {
        foreach (['email_jobs', 'password_reset_tokens', 'invitations'] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
    }
}
