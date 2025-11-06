<?php
namespace app\models;

use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Class Topic
 *
 * @property int $id
 * @property string $title
 * @property int $author_id
 * @property string $datetime
 * @property string|null $excerpt
 * @property string|null $url
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Author $author
 */
class Topic extends ActiveRecord
{
    /**
     * If excerpt is stored as JSON (array), we decode in getter.
     * If you prefer always string, store plain text.
     */

    public static function tableName()
    {
        return '{{%topics}}';
    }

    public function rules()
    {
        return [
            [['title', 'author_id', 'datetime'], 'required'],
            [['author_id'], 'integer'],
            [['excerpt'], 'string'],
            [['datetime', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 512],
            [['url'], 'string', 'max' => 1024],
            // optionally validate that author exists
            ['author_id', 'exist', 'skipOnError' => true, 'targetClass' => Author::class, 'targetAttribute' => ['author_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'title' => 'Заголовок',
            'author_id' => 'Автор',
            'datetime' => 'Дата публикации',
            'excerpt' => 'Анонс',
            'url' => 'Ссылка',
        ];
    }

    /**
     * relation: topic HAS ONE author
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(Author::class, ['id' => 'author_id']);
    }

    /**
     * Setter helper: accepts string or array. If array -> json_encode for storage.
     * Use $topic->excerpt = $value; as usual.
     *
     * @param mixed $value
     */
    public function setExcerpt($value)
    {
        if (is_array($value)) {
            $this->setAttribute('excerpt', json_encode(array_values($value), JSON_UNESCAPED_UNICODE));
        } else {
            $this->setAttribute('excerpt', (string)$value);
        }
    }

    /**
     * Getter helper: returns decoded array if JSON stored, otherwise string.
     * @return array|string|null
     */
    public function getExcerpt()
    {
        $val = $this->getAttribute('excerpt');
        if ($val === null) {
            return null;
        }
        // try json decode
        $decoded = json_decode($val, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        return $val;
    }

    /**
     * convenience method to get excerpt as plain text.
     */
    public function getExcerptText(): string
    {
        $ex = $this->getExcerpt();
        if (is_array($ex)) {
            return implode(' ', $ex);
        }
        return (string)$ex;
    }

    /**
     * Optionally update timestamps on save:
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            // set created_at/updated_at as DATETIME strings
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            if ($insert && empty($this->created_at)) {
                $this->created_at = $now;
            }
            $this->updated_at = $now;
            return true;
        }
        return false;
    }
}
