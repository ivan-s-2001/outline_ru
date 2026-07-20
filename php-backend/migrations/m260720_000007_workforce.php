<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000007_workforce extends Migration
{
    private string $options = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';

    public function safeUp(): void
    {
        $this->createTable('{{%shift_types}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(120)->notNull(),
            'code' => $this->string(32)->notNull(),
            'color' => $this->string(16)->notNull()->defaultValue('#4E5C6E'),
            'default_start_time' => $this->time(),
            'default_end_time' => $this->time(),
            'default_break_minutes' => $this->integer()->notNull()->defaultValue(0),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_shift_types_workspace_code', '{{%shift_types}}', ['workspace_id', 'code'], true);
        $this->addForeignKey('fk_shift_types_workspace', '{{%shift_types}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%shifts}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'shift_type_id' => $this->char(36),
            'work_date' => $this->date()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'break_minutes' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->string(32)->notNull()->defaultValue('planned'),
            'comment' => $this->text(),
            'created_by_id' => $this->char(36)->notNull(),
            'updated_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_shifts_workspace_date', '{{%shifts}}', ['workspace_id', 'work_date']);
        $this->createIndex('ix_shifts_user_date', '{{%shifts}}', ['user_id', 'work_date']);
        $this->addForeignKey('fk_shifts_workspace', '{{%shifts}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_shifts_user', '{{%shifts}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_shifts_type', '{{%shifts}}', 'shift_type_id', '{{%shift_types}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk_shifts_creator', '{{%shifts}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_shifts_updater', '{{%shifts}}', 'updated_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%absence_types}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(120)->notNull(),
            'code' => $this->string(32)->notNull(),
            'color' => $this->string(16)->notNull()->defaultValue('#DC3545'),
            'is_paid' => $this->boolean()->notNull()->defaultValue(false),
            'is_active' => $this->boolean()->notNull()->defaultValue(true),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_absence_types_workspace_code', '{{%absence_types}}', ['workspace_id', 'code'], true);
        $this->addForeignKey('fk_absence_types_workspace', '{{%absence_types}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%absences}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'absence_type_id' => $this->char(36)->notNull(),
            'date_from' => $this->date()->notNull(),
            'date_to' => $this->date()->notNull(),
            'start_time' => $this->time(),
            'end_time' => $this->time(),
            'status' => $this->string(32)->notNull()->defaultValue('approved'),
            'comment' => $this->text(),
            'created_by_id' => $this->char(36)->notNull(),
            'approved_by_id' => $this->char(36),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_absences_workspace_range', '{{%absences}}', ['workspace_id', 'date_from', 'date_to']);
        $this->createIndex('ix_absences_user_range', '{{%absences}}', ['user_id', 'date_from', 'date_to']);
        $this->addForeignKey('fk_absences_workspace', '{{%absences}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_absences_user', '{{%absences}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_absences_type', '{{%absences}}', 'absence_type_id', '{{%absence_types}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_absences_creator', '{{%absences}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_absences_approver', '{{%absences}}', 'approved_by_id', '{{%users}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%work_time_adjustments}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'work_date' => $this->date()->notNull(),
            'minutes' => $this->integer()->notNull(),
            'kind' => $this->string(32)->notNull()->defaultValue('manual'),
            'comment' => $this->text(),
            'created_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_adjustments_user_date', '{{%work_time_adjustments}}', ['user_id', 'work_date']);
        $this->addForeignKey('fk_adjustments_workspace', '{{%work_time_adjustments}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_adjustments_user', '{{%work_time_adjustments}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_adjustments_creator', '{{%work_time_adjustments}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%production_calendar_days}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'calendar_date' => $this->date()->notNull(),
            'is_working' => $this->boolean()->notNull(),
            'is_shortened' => $this->boolean()->notNull()->defaultValue(false),
            'name' => $this->string(255),
            'source' => $this->string(255)->notNull()->defaultValue('manual'),
            'source_version' => $this->string(120),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_production_calendar_day', '{{%production_calendar_days}}', ['workspace_id', 'calendar_date'], true);
        $this->addForeignKey('fk_production_calendar_workspace', '{{%production_calendar_days}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%vacation_allowances}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'allowance_year' => $this->integer()->notNull(),
            'allowance_days' => $this->decimal(6, 2)->notNull()->defaultValue(20),
            'carried_days' => $this->decimal(6, 2)->notNull()->defaultValue(0),
            'comment' => $this->text(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_vacation_allowance', '{{%vacation_allowances}}', ['workspace_id', 'user_id', 'allowance_year'], true);
        $this->addForeignKey('fk_vacation_allowance_workspace', '{{%vacation_allowances}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_vacation_allowance_user', '{{%vacation_allowances}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%vacation_requests}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'date_from' => $this->date()->notNull(),
            'date_to' => $this->date()->notNull(),
            'working_days' => $this->decimal(6, 2)->notNull(),
            'status' => $this->string(32)->notNull()->defaultValue('approved'),
            'comment' => $this->text(),
            'calendar_source' => $this->string(255)->notNull(),
            'calendar_version' => $this->string(120),
            'requested_by_id' => $this->char(36)->notNull(),
            'approved_by_id' => $this->char(36),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_vacation_requests_user_range', '{{%vacation_requests}}', ['user_id', 'date_from', 'date_to']);
        $this->addForeignKey('fk_vacation_requests_workspace', '{{%vacation_requests}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_vacation_requests_user', '{{%vacation_requests}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_vacation_requests_requester', '{{%vacation_requests}}', 'requested_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_vacation_requests_approver', '{{%vacation_requests}}', 'approved_by_id', '{{%users}}', 'id', 'SET NULL', 'CASCADE');

        $this->createTable('{{%duty_settings}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'name' => $this->string(255)->notNull(),
            'date_from' => $this->date()->notNull(),
            'date_to' => $this->date()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'slot_minutes' => $this->integer()->notNull()->defaultValue(10),
            'status' => $this->string(32)->notNull()->defaultValue('draft'),
            'created_by_id' => $this->char(36)->notNull(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_duty_settings_workspace', '{{%duty_settings}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_settings_creator', '{{%duty_settings}}', 'created_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');

        $this->createTable('{{%duty_participants}}', [
            'id' => $this->char(36)->notNull(),
            'duty_setting_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'coefficient' => $this->decimal(7, 3)->notNull()->defaultValue(1),
            'is_excluded' => $this->boolean()->notNull()->defaultValue(false),
            'schedule_snapshot' => $this->json(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ux_duty_participant', '{{%duty_participants}}', ['duty_setting_id', 'user_id'], true);
        $this->addForeignKey('fk_duty_participant_setting', '{{%duty_participants}}', 'duty_setting_id', '{{%duty_settings}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_participant_user', '{{%duty_participants}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%duty_assignments}}', [
            'id' => $this->char(36)->notNull(),
            'duty_setting_id' => $this->char(36)->notNull(),
            'user_id' => $this->char(36)->notNull(),
            'duty_date' => $this->date()->notNull(),
            'start_time' => $this->time()->notNull(),
            'end_time' => $this->time()->notNull(),
            'share_coefficient' => $this->decimal(7, 3)->notNull()->defaultValue(1),
            'status' => $this->string(32)->notNull()->defaultValue('assigned'),
            'comment' => $this->text(),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->createIndex('ix_duty_assignments_date', '{{%duty_assignments}}', ['duty_setting_id', 'duty_date', 'start_time']);
        $this->addForeignKey('fk_duty_assignment_setting', '{{%duty_assignments}}', 'duty_setting_id', '{{%duty_settings}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_assignment_user', '{{%duty_assignments}}', 'user_id', '{{%users}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%duty_swaps}}', [
            'id' => $this->char(36)->notNull(),
            'workspace_id' => $this->char(36)->notNull(),
            'from_assignment_id' => $this->char(36)->notNull(),
            'to_assignment_id' => $this->char(36)->notNull(),
            'requested_by_id' => $this->char(36)->notNull(),
            'approved_by_id' => $this->char(36),
            'status' => $this->string(32)->notNull()->defaultValue('pending'),
            'created_at' => $this->dateTime(6)->notNull(),
            'updated_at' => $this->dateTime(6)->notNull(),
            'PRIMARY KEY ([[id]])',
        ], $this->options);
        $this->addForeignKey('fk_duty_swaps_workspace', '{{%duty_swaps}}', 'workspace_id', '{{%workspaces}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_swaps_from', '{{%duty_swaps}}', 'from_assignment_id', '{{%duty_assignments}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_swaps_to', '{{%duty_swaps}}', 'to_assignment_id', '{{%duty_assignments}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_duty_swaps_requester', '{{%duty_swaps}}', 'requested_by_id', '{{%users}}', 'id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_duty_swaps_approver', '{{%duty_swaps}}', 'approved_by_id', '{{%users}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        foreach ([
            'duty_swaps',
            'duty_assignments',
            'duty_participants',
            'duty_settings',
            'vacation_requests',
            'vacation_allowances',
            'production_calendar_days',
            'work_time_adjustments',
            'absences',
            'absence_types',
            'shifts',
            'shift_types',
        ] as $table) {
            $this->dropTable('{{%' . $table . '}}');
        }
    }
}
