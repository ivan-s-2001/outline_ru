<?php

use yii\db\Migration;

class m260720_000001_initial extends Migration
{
    public function safeUp(): void
    {
        $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

        $this->createTable('{{%workspaces}}', [
            'id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(255)->notNull(),
            'slug' => $this->string(100)->notNull()->unique(),
            'settings_json' => 'JSON NULL',
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);

        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'last_name' => $this->string(100)->notNull(),
            'first_name' => $this->string(100)->notNull(),
            'middle_name' => $this->string(100)->notNull(),
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'auth_key' => $this->string(100)->notNull(),
            'role' => "ENUM('admin','manager','member') NOT NULL DEFAULT 'member'",
            'status' => "ENUM('active','disabled') NOT NULL DEFAULT 'active'",
            'color' => $this->string(7)->notNull()->defaultValue('#2563EB'),
            'avatar_path' => $this->string(500),
            'preferences_json' => 'JSON NULL',
            'last_active_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_users_workspace', '{{%users}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->createIndex('idx_users_workspace_status', '{{%users}}', ['workspace_id', 'status']);

        $this->createTable('{{%groups}}', [
            'id' => $this->primaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_groups_workspace', '{{%groups}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_groups_creator', '{{%groups}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('uq_groups_workspace_name', '{{%groups}}', ['workspace_id', 'name'], true);

        $this->createTable('{{%group_users}}', [
            'group_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'PRIMARY KEY(group_id, user_id)',
        ], $options);
        $this->addForeignKey('fk_group_users_group', '{{%group_users}}', 'group_id', '{{%groups}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_group_users_user', '{{%group_users}}', 'user_id', '{{%users}}', 'id', 'CASCADE');

        $this->createTable('{{%collections}}', [
            'id' => $this->primaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'description' => $this->text(),
            'color' => $this->string(20),
            'icon' => $this->string(100),
            'permission_mode' => "ENUM('workspace','private') NOT NULL DEFAULT 'workspace'",
            'sort_order' => $this->integer()->notNull()->defaultValue(0),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_collections_workspace', '{{%collections}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_collections_creator', '{{%collections}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('uq_collections_workspace_name', '{{%collections}}', ['workspace_id', 'name'], true);

        $this->createTable('{{%collection_permissions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'collection_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned(),
            'group_id' => $this->integer()->unsigned(),
            'permission' => "ENUM('read','read_write','admin') NOT NULL",
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_cp_collection', '{{%collection_permissions}}', 'collection_id', '{{%collections}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_cp_user', '{{%collection_permissions}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_cp_group', '{{%collection_permissions}}', 'group_id', '{{%groups}}', 'id', 'CASCADE');
        $this->createIndex('idx_cp_collection_user', '{{%collection_permissions}}', ['collection_id', 'user_id']);
        $this->createIndex('idx_cp_collection_group', '{{%collection_permissions}}', ['collection_id', 'group_id']);

        $this->createTable('{{%documents}}', [
            'id' => $this->primaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'collection_id' => $this->integer()->unsigned(),
            'parent_id' => $this->integer()->unsigned(),
            'title' => $this->string(500)->notNull(),
            'content' => 'LONGTEXT NULL',
            'content_json' => 'JSON NULL',
            'content_text' => 'LONGTEXT NULL',
            'collaboration_state' => 'LONGBLOB NULL',
            'status' => "ENUM('draft','published','archived') NOT NULL DEFAULT 'published'",
            'permission_mode' => "ENUM('inherit','workspace_read','workspace_edit','private') NOT NULL DEFAULT 'inherit'",
            'is_template' => $this->boolean()->notNull()->defaultValue(false),
            'version' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'updated_by' => $this->integer()->unsigned()->notNull(),
            'published_at' => $this->dateTime(),
            'archived_at' => $this->dateTime(),
            'deleted_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_documents_workspace', '{{%documents}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_documents_collection', '{{%documents}}', 'collection_id', '{{%collections}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_documents_parent', '{{%documents}}', 'parent_id', '{{%documents}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_documents_creator', '{{%documents}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->addForeignKey('fk_documents_updater', '{{%documents}}', 'updated_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('idx_documents_workspace_status', '{{%documents}}', ['workspace_id', 'status', 'deleted_at']);
        $this->createIndex('idx_documents_collection', '{{%documents}}', ['collection_id', 'parent_id']);
        $this->execute('ALTER TABLE {{%documents}} ADD FULLTEXT INDEX ft_documents_title_content (title, content_text)');

        $this->createTable('{{%document_permissions}}', [
            'id' => $this->primaryKey()->unsigned(),
            'document_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned(),
            'group_id' => $this->integer()->unsigned(),
            'permission' => "ENUM('read','read_write','admin') NOT NULL",
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_dp_document', '{{%document_permissions}}', 'document_id', '{{%documents}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_dp_user', '{{%document_permissions}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_dp_group', '{{%document_permissions}}', 'group_id', '{{%groups}}', 'id', 'CASCADE');

        $this->createTable('{{%document_revisions}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'document_id' => $this->integer()->unsigned()->notNull(),
            'version' => $this->integer()->unsigned()->notNull(),
            'title' => $this->string(500)->notNull(),
            'content' => 'LONGTEXT NULL',
            'content_json' => 'JSON NULL',
            'content_text' => 'LONGTEXT NULL',
            'collaboration_state' => 'LONGBLOB NULL',
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_revisions_document', '{{%document_revisions}}', 'document_id', '{{%documents}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_revisions_creator', '{{%document_revisions}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('uq_revisions_document_version', '{{%document_revisions}}', ['document_id', 'version'], true);

        $this->createTable('{{%comments}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'document_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'parent_id' => $this->bigInteger()->unsigned(),
            'body' => $this->text()->notNull(),
            'anchor_json' => 'JSON NULL',
            'resolved_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_comments_document', '{{%comments}}', 'document_id', '{{%documents}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_comments_user', '{{%comments}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_comments_parent', '{{%comments}}', 'parent_id', '{{%comments}}', 'id', 'CASCADE');

        $this->createTable('{{%attachments}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'document_id' => $this->integer()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(500)->notNull(),
            'path' => $this->string(1000)->notNull(),
            'mime_type' => $this->string(255)->notNull(),
            'size_bytes' => $this->bigInteger()->unsigned()->notNull(),
            'checksum' => $this->string(64),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_attachments_workspace', '{{%attachments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_attachments_document', '{{%attachments}}', 'document_id', '{{%documents}}', 'id', 'SET NULL');
        $this->addForeignKey('fk_attachments_user', '{{%attachments}}', 'user_id', '{{%users}}', 'id', 'RESTRICT');

        $this->createTable('{{%shares}}', [
            'id' => $this->primaryKey()->unsigned(),
            'document_id' => $this->integer()->unsigned()->notNull(),
            'token' => $this->string(100)->notNull()->unique(),
            'permission' => "ENUM('read','read_write') NOT NULL DEFAULT 'read'",
            'password_hash' => $this->string(255),
            'expires_at' => $this->dateTime(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_shares_document', '{{%shares}}', 'document_id', '{{%documents}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_shares_creator', '{{%shares}}', 'created_by', '{{%users}}', 'id', 'CASCADE');

        $this->createTable('{{%api_keys}}', [
            'id' => $this->primaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'name' => $this->string(255)->notNull(),
            'token_hash' => $this->string(64)->notNull()->unique(),
            'scopes_json' => 'JSON NULL',
            'last_used_at' => $this->dateTime(),
            'expires_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_api_keys_workspace', '{{%api_keys}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_api_keys_user', '{{%api_keys}}', 'user_id', '{{%users}}', 'id', 'CASCADE');

        $this->createTable('{{%notifications}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->string(100)->notNull(),
            'payload_json' => 'JSON NOT NULL',
            'read_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_notifications_workspace', '{{%notifications}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_notifications_user', '{{%notifications}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->createIndex('idx_notifications_user_read', '{{%notifications}}', ['user_id', 'read_at', 'created_at']);

        $this->createTable('{{%events}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned(),
            'name' => $this->string(150)->notNull(),
            'entity_type' => $this->string(100),
            'entity_id' => $this->string(100),
            'payload_json' => 'JSON NULL',
            'ip' => $this->string(64),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_events_workspace', '{{%events}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_events_user', '{{%events}}', 'user_id', '{{%users}}', 'id', 'SET NULL');
        $this->createIndex('idx_events_workspace_created', '{{%events}}', ['workspace_id', 'created_at']);

        $this->createTable('{{%shifts}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'shift_date' => $this->date()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'type' => $this->string(50)->notNull()->defaultValue('work'),
            'status' => $this->string(50)->notNull()->defaultValue('planned'),
            'comment' => $this->text(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_shifts_workspace', '{{%shifts}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_shifts_user', '{{%shifts}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_shifts_creator', '{{%shifts}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('idx_shifts_user_date', '{{%shifts}}', ['user_id', 'shift_date']);

        $this->createTable('{{%absences}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'type' => $this->string(50)->notNull(),
            'date_from' => $this->date()->notNull(),
            'date_to' => $this->date()->notNull(),
            'minutes' => $this->integer(),
            'comment' => $this->text(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_absences_workspace', '{{%absences}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_absences_user', '{{%absences}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_absences_creator', '{{%absences}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');

        $this->createTable('{{%work_time_adjustments}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'work_date' => $this->date()->notNull(),
            'minutes' => $this->integer()->notNull(),
            'reason' => $this->string(255)->notNull(),
            'comment' => $this->text(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_wta_workspace', '{{%work_time_adjustments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_wta_user', '{{%work_time_adjustments}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_wta_creator', '{{%work_time_adjustments}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');

        $this->createTable('{{%vacation_requests}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'date_from' => $this->date()->notNull(),
            'date_to' => $this->date()->notNull(),
            'working_days' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'status' => "ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending'",
            'comment' => $this->text(),
            'reviewed_by' => $this->integer()->unsigned(),
            'reviewed_at' => $this->dateTime(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_vacation_workspace', '{{%vacation_requests}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_vacation_user', '{{%vacation_requests}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_vacation_reviewer', '{{%vacation_requests}}', 'reviewed_by', '{{%users}}', 'id', 'SET NULL');

        $this->createTable('{{%production_calendar_days}}', [
            'id' => $this->primaryKey()->unsigned(),
            'calendar_date' => $this->date()->notNull()->unique(),
            'is_working' => $this->boolean()->notNull(),
            'is_shortened' => $this->boolean()->notNull()->defaultValue(false),
            'name' => $this->string(255),
            'source' => $this->string(255),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);

        $this->createTable('{{%duty_assignments}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'workspace_id' => $this->integer()->unsigned()->notNull(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'duty_date' => $this->date()->notNull(),
            'hall' => $this->string(100)->notNull(),
            'duration_minutes' => $this->integer()->unsigned()->notNull(),
            'comment' => $this->text(),
            'created_by' => $this->integer()->unsigned()->notNull(),
            'created_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->notNull()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], $options);
        $this->addForeignKey('fk_duty_workspace', '{{%duty_assignments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_duty_user', '{{%duty_assignments}}', 'user_id', '{{%users}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_duty_creator', '{{%duty_assignments}}', 'created_by', '{{%users}}', 'id', 'RESTRICT');
        $this->createIndex('idx_duty_date_hall', '{{%duty_assignments}}', ['workspace_id', 'duty_date', 'hall']);
    }

    public function safeDown(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0');
        foreach ([
            'duty_assignments', 'production_calendar_days', 'vacation_requests',
            'work_time_adjustments', 'absences', 'shifts', 'events', 'notifications',
            'api_keys', 'shares', 'attachments', 'comments', 'document_revisions',
            'document_permissions', 'documents', 'collection_permissions', 'collections',
            'group_users', 'groups', 'users', 'workspaces',
        ] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
        $this->execute('SET FOREIGN_KEY_CHECKS=1');
    }
}
