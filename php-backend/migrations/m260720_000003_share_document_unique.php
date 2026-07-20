<?php

declare(strict_types=1);
use yii\db\Migration;

final class m260720_000003_share_document_unique extends Migration
{
    public function safeUp(): void
    {
        $this->createIndex('ux_shares_document', '{{%shares}}', 'document_id', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('ux_shares_document', '{{%shares}}');
    }
}
