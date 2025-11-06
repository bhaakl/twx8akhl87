<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%authors}}`.
 */
class m251106_135443_create_authors_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%authors}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'email' => $this->string(255)->null(),
            'msg' => $this->text()->null(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-authors-name', '{{%authors}}', 'name');
        $this->createIndex('idx-authors-email', '{{%authors}}', 'email');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%authors}}');
    }
}
