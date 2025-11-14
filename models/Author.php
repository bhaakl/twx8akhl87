<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class Author
 *
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $msg
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Topic[] $topics
 */
class Author extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%authors}}';
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Имя автора',
            'email' => 'E-mail',
            'msg' => 'Сообщение',
        ];
    }

    /**
     * relation: author has many topics
     * @return \yii\db\ActiveQuery
     */
    public function getTopics()
    {
        return $this->hasMany(Topic::class, ['author_id' => 'id'])->inverseOf('author');
    }

    /**
     * beforeSave: created_at & updated_at setting
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = (new \DateTime())->format('Y-m-d H:i:s');
        if ($insert) {
            $this->created_at = $now;
        } else {
            $this->updated_at = $now;
        }

        return true;
    }
}
