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
    public $verifyCode;
    public static function tableName()
    {
        return '{{%authors}}';
    }

    public function rules()
    {
        return [
            [['name', 'email', 'msg'], 'required'],
            [['email'], 'email'],
            [['msg'], 'string'],
            [['name', 'email'], 'string', 'max' => 255],
            ['verifyCode', 'captcha'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'name' => 'Имя автора',
            'email' => 'E-mail',
            'msg' => 'Сообщение',
            'verifyCode' => 'Код с картинки',
        ];
    }

    /**
     * relation: author has many topics
     * @return \yii\db\ActiveQuery
     */
    public function getTopics()
    {
        return $this->hasMany(Topic::class, ['author_id' => 'id']);
    }
}