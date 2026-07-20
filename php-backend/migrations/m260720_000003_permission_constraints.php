<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000003_permission_constraints extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex(
            'ux_collection_permissions_user',
            '{{%collection_permissions}}',
            ['collection_id', 'user_id'],
            true
        );
        $this->createIndex(
            'ux_collection_permissions_group',
            '{{%collection_permissions}}',
            ['collection_id', 'group_id'],
            true
        );
        $this->createIndex(
            'ux_document_permissions_user',
            '{{%document_permissions}}',
            ['document_id', 'user_id'],
            true
        );
        $this->createIndex(
            'ux_document_permissions_group',
            '{{%document_permissions}}',
            ['document_id', 'group_id'],
            true
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('ux_document_permissions_group', '{{%document_permissions}}');
        $this->dropIndex('ux_document_permissions_user', '{{%document_permissions}}');
        $this->dropIndex('ux_collection_permissions_group', '{{%collection_permissions}}');
        $this->dropIndex('ux_collection_permissions_user', '{{%collection_permissions}}');
    }
}
