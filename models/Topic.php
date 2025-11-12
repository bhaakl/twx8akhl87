<?php
namespace app\models;

use yii\db\ActiveRecord;
use app\helpers\SvAppHelper; 

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
            [['title', 'author_id', 'datetime'], 'required', 'message' => 'Поле {attribute} не должно быть пустым'],
            [['author_id'], 'integer'],
            [['excerpt'], 'string'],
            [['datetime', 'created_at', 'updated_at'], 'safe'],
            [['title'], 'string', 'max' => 512],
            [['url'], 'string', 'max' => 1024],
            // author exists validate
            ['author_id', 'exist', 'skipOnError' => false, 'targetClass' => Author::class, 'targetAttribute' => ['author_id' => 'id']],
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
     * Setter helper for Author
     */
    public function setAuthor(Author $author)
    {
        if ($author->save()) {
            $this->link('author', $author);
            return true;
        } 
        SvAppHelper::showErrors($this, 'Topic error');
        return false;
    }

    /**
     * Setter helper: accepts string or array. If array -> implode for storage.
     *
     * @param mixed $value
     */
    public function setExcerpt($value)
    {
        if (is_array($value)) {
            $phs = array_values(array_filter($value, function($val) {
                return trim($val) !== '';
            }));
            $ex = implode("\n", $phs);
            $this->setAttribute('excerpt', $ex);
        } else $this->setAttribute('excerpt', (string)$value);
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
            $result = array_values(array_filter($ex, function($value) {
                return trim($value) !== '';
            }));
            return implode('\n', $result);
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
