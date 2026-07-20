<?php

declare(strict_types=1);

use yii\db\Migration;

final class m260720_000002_add_user_login extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%users}}', 'login', $this->string(120)->null()->after('email'));
        $this->execute("UPDATE {{%users}} SET login = LOWER(SUBSTRING_INDEX(email, '@', 1)) WHERE login IS NULL");
        $duplicates = (int)$this->db->createCommand(
            'SELECT COUNT(*) FROM (SELECT login FROM {{%users}} GROUP BY login HAVING COUNT(*) > 1) AS duplicate_logins'
        )->queryScalar();
        if ($duplicates > 0) {
            $this->execute("UPDATE {{%users}} SET login = CONCAT(login, '-', LEFT(id, 8))");
        }
        $this->alterColumn('{{%users}}', 'login', $this->string(120)->notNull());
        $this->createIndex('ux_users_login', '{{%users}}', 'login', true);
    }

    public function safeDown(): void
    {
        $this->dropIndex('ux_users_login', '{{%users}}');
        $this->dropColumn('{{%users}}', 'login');
    }
}
