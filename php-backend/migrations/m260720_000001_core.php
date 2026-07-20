<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000001_core extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%workspaces}}', [
            'id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'domain' => $this->string(255),
            'default_language' => $this->string(16)->notNull()->defaultValue('ru_RU'),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);

        $this->createTable('{{%users}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'email' => $this->string(255)->notNull(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(64)->notNull(),
            'last_name' => $this->string(120)->notNull(),
            'first_name' => $this->string(120)->notNull(),
            'middle_name' => $this->string(120)->notNull(),
            'role' => $this->string(32)->notNull()->defaultValue('member'),
            'status' => $this->string(32)->notNull()->defaultValue('active'),
            'avatar_url' => $this->text(),
            'color' => $this->string(16)->notNull()->defaultValue('#6B7280'),
            'last_active_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_users_email', '{{%users}}', 'email', true);
        $this->createIndex('ix_users_workspace', '{{%users}}', 'workspace_id');
        $this->addForeignKey('fk_users_workspace', '{{%users}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%groups}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_groups_workspace', '{{%groups}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%group_users}}', [
            'group_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[group_id]], [[user_id]])',
        ], $this->options);
        $this->addForeignKey('fk_group_users_group', '{{%group_users}}', 'group_id', '{{%groups}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_group_users_user', '{{%group_users}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%collections}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'color' => $this->string(16)->notNull()->defaultValue('#4E5C6E'),
            'icon' => $this->string(255),
            'permission' => $this->string(32)->notNull()->defaultValue('read_write'),
            'sort_order' => $this->string(255),
            'created_by_id' => $this->char(36)->notNull(),
            'archived_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_collections_workspace', '{{%collections}}', 'workspace_id');
        $this->addForeignKey('fk_collections_workspace', '{{%collections}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_collections_creator', '{{%collections}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%collection_permissions}}', [
            'id' => $this->char(36)->notNull(),
            'collection_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36),
            'group_id' => $this->char(36),
            'permission' => $this->string(32)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_cp_collection', '{{%collection_permissions}}', 'collection_id', '{{%collections}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_cp_user', '{{%collection_permissions}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_cp_group', '{{%collection_permissions}}', 'group_id', '{{%groups}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%documents}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'collection_id' => $this->char(36),
            'parent_document_id' => $this->char(36),
            'title' => $this->string(1024)->notNull()->defaultValue(''),
            'content_json' => $this->json(),
            'content_text' => $this->longText(),
            'yjs_state' => 'LONGBLOB NULL',
            'revision_number' => $this->integer()->notNull()->defaultValue(0),
            'sort_order' => $this->string(255),
            'created_by_id' => $this->char(36)->notNull(),
            'updated_by_id' => $this->char(36)->notNull(),
            'published_at' => $this->dateTime(6),
            'archived_at' => $this->dateTime(6),
            'deleted_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_documents_workspace', '{{%documents}}', 'workspace_id');
        $this->createIndex('ix_documents_collection', '{{%documents}}', 'collection_id');
        $this->createIndex('ix_documents_parent', '{{%documents}}', 'parent_document_id');
        $this->addForeignKey('fk_documents_workspace', '{{%documents}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_documents_collection', '{{%documents}}', 'collection_id', '{{%collections}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_documents_parent', '{{%documents}}', 'parent_document_id', '{{%documents}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_documents_creator', '{{%documents}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_documents_updater', '{{%documents}}', 'updated_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->execute('ALTER TABLE {{%documents}} ADD FULLTEXT INDEX ft_documents_search (`title`, `content_text`)');

        $this->createTable('{{%document_permissions}}', [
            'id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36),
            'group_id' => $this->char(36),
            'permission' => $this->string(32)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_dp_document', '{{%document_permissions}}', 'document_id', '{{%documents}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_dp_user', '{{%document_permissions}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_dp_group', '{{%document_permissions}}', 'group_id', '{{%groups}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%revisions}}', [
            'id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'title' => $this->string(1024)->notNull(),
            'content_json' => $this->json(),
            'content_text' => $this->longText(),
            'yjs_state' => 'LONGBLOB NULL',
            'revision_number' => $this->integer()->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_revisions_document', '{{%revisions}}', ['document_id', 'revision_number']);
        $this->addForeignKey('fk_revisions_document', '{{%revisions}}', 'document_id', '{{%documents}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_revisions_user', '{{%revisions}}', 'user_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%comments}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36)->notNull(),
            'parent_comment_id' => $this->char(36),
            'user_id' => $this->char(36)->notNull(),
            'data' => $this->json(),
            'text' => $this->longText()->notNull(),
            'resolved_at' => $this->dateTime(6),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_comments_document', '{{%comments}}', 'document_id');
        $this->addForeignKey('fk_comments_workspace', '{{%comments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_comments_document', '{{%comments}}', 'document_id', '{{%documents}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_comments_parent', '{{%comments}}', 'parent_comment_id', '{{%comments}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_comments_user', '{{%comments}}', 'user_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%attachments}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36),
            'user_id' => $this->char(36)->notNull(),
            'name' => $this->string(1024)->notNull(),
            'content_type' => $this->string(255)->notNull(),
            'size' => $this->bigInteger()->notNull(),
            'storage_key' => $this->string(1024)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_attachments_workspace', '{{%attachments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_attachments_document', '{{%attachments}}', 'document_id', '{{%documents}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_attachments_user', '{{%attachments}}', 'user_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%shares}}', [
            'id' => $this->char(36)->notNull(),
            'document_id' => $this->char(36)->notNull(),
            'created_by_id' => $this->char(36)->notNull(),
            'token' => $this->string(128)->notNull(),
            'is_published' => $this->boolean()->notNull()->defaultValue(true),
            'include_child_documents' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_shares_token', '{{%shares}}', 'token', true);
        $this->addForeignKey('fk_shares_document', '{{%shares}}', 'document_id', '{{%documents}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_shares_user', '{{%shares}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%events}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36),
            'name' => $this->string(255)->notNull(),
            'model_id' => $this->char(36),
            'ip' => $this->string(64),
            'data' => $this->json(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_events_workspace_created', '{{%events}}', ['workspace_id', 'created_at']);
        $this->addForeignKey('fk_events_workspace', '{{%events}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_events_user', '{{%events}}', 'user_id', '{{%users}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        foreach ([
            'events', 'shares', 'attachments', 'comments', 'revisions',
            'document_permissions', 'documents', 'collection_permissions',
            'collections', 'group_users', 'groups', 'users', 'workspaces',
        ] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
    }
}
