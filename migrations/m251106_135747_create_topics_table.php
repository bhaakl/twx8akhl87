<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%topics}}`.
 */
class m251106_135747_create_topics_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%topics}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(512)->notNull(),
            'author_id' => $this->integer()->notNull(),
            'datetime' => $this->dateTime()->notNull(),     
            'excerpt' => $this->text()->null(),              // текст или JSON
            'url' => $this->string(1024)->null(),
            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->dateTime()->null(),
        ]);

        $this->createIndex('idx-topics-author_id', '{{%topics}}', 'author_id');
        $this->createIndex('idx-topics-datetime', '{{%topics}}', 'datetime');

        // FK
        $this->addForeignKey(
            'fk-topics-author_id',
            '{{%topics}}',
            'author_id',
            '{{%authors}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }


    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-topics-author_id', '{{%topics}}');
        $this->dropTable('{{%topics}}');
    }
}
