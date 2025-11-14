<?php

use yii\db\Migration;

class m251114_022147_add_management_tokens_and_softdelete_to_topics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%topics}}', 'edit_token', $this->string(64)->null());
        $this->addColumn('{{%topics}}', 'delete_token', $this->string(64)->null());
        // deleted_at: unix timestamp when soft-deleted (nullable)
        $this->addColumn('{{%topics}}', 'deleted_at', $this->integer()->null());

        $this->createIndex('idx-topics-edit_token', '{{%topics}}', 'edit_token');
        $this->createIndex('idx-topics-delete_token', '{{%topics}}', 'delete_token');
        $this->createIndex('idx-topics-deleted_at', '{{%topics}}', 'deleted_at');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-topics-edit_token', '{{%topics}}');
        $this->dropIndex('idx-topics-delete_token', '{{%topics}}');
        $this->dropIndex('idx-topics-deleted_at', '{{%topics}}');

        $this->dropColumn('{{%topics}}', 'edit_token');
        $this->dropColumn('{{%topics}}', 'delete_token');
        $this->dropColumn('{{%topics}}', 'deleted_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251114_022147_add_management_tokens_and_softdelete_to_topics cannot be reverted.\n";

        return false;
    }
    */
}
