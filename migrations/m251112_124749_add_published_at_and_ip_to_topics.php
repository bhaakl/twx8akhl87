<?php

use yii\db\Migration;

class m251112_124749_add_published_at_and_ip_to_topics extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%topics}}', 'published_at', $this->integer()->notNull()->defaultValue(0));
        // ip — ipv4/ipv6, 45 chars sufficient
        $this->addColumn('{{%topics}}', 'ip', $this->string(45)->null());

        $this->createIndex('idx-topics-author_published', '{{%topics}}', ['author_id', 'published_at']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx-topics-author_published', '{{%topics}}');
        $this->dropColumn('{{%topics}}', 'ip');
        $this->dropColumn('{{%topics}}', 'published_at');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251112_124749_add_published_at_and_ip_to_topics cannot be reverted.\n";

        return false;
    }
    */
}
