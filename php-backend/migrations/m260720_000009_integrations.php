<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000009_integrations extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%webhooks}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'url' => $this->string(2048)->notNull(),
            'events' => $this->json(),
            'secret_encrypted' => 'LONGTEXT NOT NULL',
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_webhooks_workspace', '{{%webhooks}}', ['workspace_id', 'is_active']);
        $this->addForeignKey('fk_webhooks_workspace', '{{%webhooks}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_webhooks_creator', '{{%webhooks}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%webhook_deliveries}}', [
            'id' => $this->char(36)->notNull(),
            'webhook_id' => $this->char(36)->notNull(),
            'event_id' => $this->char(36),
            'event_name' => $this->string(255)->notNull(),
            'payload' => $this->json()->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'next_attempt_at' => $this->dateTime(6),
            'last_status_code' => $this->integer(),
            'last_error' => $this->text(),
            'delivered_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_webhook_deliveries_queue', '{{%webhook_deliveries}}', ['status', 'next_attempt_at', 'created_at']);
        $this->addForeignKey('fk_webhook_delivery_webhook', '{{%webhook_deliveries}}', 'webhook_id', '{{%webhooks}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_webhook_delivery_event', '{{%webhook_deliveries}}', 'event_id', '{{%events}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%oauth_apps}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'client_id' => $this->string(128)->notNull(),
            'client_secret_hash' => $this->string(255),
            'redirect_uris' => $this->json()->notNull(),
            'scopes' => $this->json()->notNull(),
            'is_confidential' => $this->boolean()->notNull()->defaultValue(true),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_oauth_apps_client_id', '{{%oauth_apps}}', 'client_id', true);
        $this->addForeignKey('fk_oauth_apps_workspace', '{{%oauth_apps}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_oauth_apps_creator', '{{%oauth_apps}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%oauth_authorization_codes}}', [
            'id' => $this->char(36)->notNull(),
            'app_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'code_hash' => $this->char(64)->notNull(),
            'redirect_uri' => $this->string(2048)->notNull(),
            'scopes' => $this->json()->notNull(),
            'code_challenge' => $this->string(255),
            'code_challenge_method' => $this->string(16),
            'expires_at' => $this->dateTime(6)->notNull(),
            'used_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_oauth_codes_hash', '{{%oauth_authorization_codes}}', 'code_hash', true);
        $this->addForeignKey('fk_oauth_codes_app', '{{%oauth_authorization_codes}}', 'app_id', '{{%oauth_apps}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_oauth_codes_user', '{{%oauth_authorization_codes}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%oauth_access_tokens}}', [
            'id' => $this->char(36)->notNull(),
            'app_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'token_hash' => $this->char(64)->notNull(),
            'refresh_token_hash' => $this->char(64),
            'scopes' => $this->json()->notNull(),
            'expires_at' => $this->dateTime(6)->notNull(),
            'refresh_expires_at' => $this->dateTime(6),
            'revoked_at' => $this->dateTime(6),
            'last_used_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_oauth_tokens_hash', '{{%oauth_access_tokens}}', 'token_hash', true);
        $this->createIndex('ux_oauth_refresh_hash', '{{%oauth_access_tokens}}', 'refresh_token_hash', true);
        $this->addForeignKey('fk_oauth_tokens_app', '{{%oauth_access_tokens}}', 'app_id', '{{%oauth_apps}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_oauth_tokens_user', '{{%oauth_access_tokens}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%integrations}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'service' => $this->string(64)->notNull(),
            'type' => $this->string(64)->notNull(),
            'name' => $this->string(255)->notNull(),
            'settings' => $this->json(),
            'credentials_encrypted' => 'LONGTEXT NULL',
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_integrations_workspace', '{{%integrations}}', ['workspace_id', 'service', 'type']);
        $this->addForeignKey('fk_integrations_workspace', '{{%integrations}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_integrations_creator', '{{%integrations}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
    }

    public function safeDown(): void
    {
        foreach (['integrations', 'oauth_access_tokens', 'oauth_authorization_codes', 'oauth_apps', 'webhook_deliveries', 'webhooks'] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
    }
}
