<?php

namespace app\models;

use yii\db\ActiveRecord;
use app\helpers\SvAppHelper;
use yii\helpers\Url;

/**
 * Class Topic
 *
 * @property int $id
 * @property string $title
 * @property int $author_id
 * @property string $datetime
 * @property string|null $excerpt
 * @property int $published_at
 * @property string|null $ip
 * @property string|null $url
 * @property string|null $created_at
 * @property string|null $updated_at
 *
 * @property Author $author
 */
class Topic extends ActiveRecord
{
    const RATE_SECONDS = 180; // 3 минуты

    const EDIT_WINDOW_SECONDS = 43200; // 12 часов = 43200 секунд
    const DELETE_WINDOW_SECONDS = 1209600; // 14 дней = 14*24*3600 = 1209600

    public static function tableName()
    {
        return '{{%topics}}';
    }

    public function rules()
    {
        return [
            // allowing tags: b, i, s
            ['excerpt', 'filter',
                'filter' => function ($value) {
                    if ($value === null || $value === '') {
                        return $value;
                    }
                    return \yii\helpers\HtmlPurifier::process($value, [
                        'HTML.Allowed' => 'b,i,s',
                        'AutoFormat.RemoveEmpty' => true,
                    ]);
                },
            ],
            [['title', 'datetime'], 'required', 'message' => 'Поле {attribute} не должно быть пустым'],
            [['author_id'], 'integer'],
            [['excerpt'], 'string'],
            [['title', 'excerpt'], 'filter', 'filter' => 'trim'],
            // запрет сообщения на whitespace
            ['excerpt', 'validateNotBlank'],
            // rate-limit: кастомная валидация
            ['author_id', 'validateRateLimit'],
            [['published_at'], 'integer'],
            [['ip'], 'string', 'max' => 45],
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
            'excerpt' => 'Содержимое',
            'published_at' => 'Дата публикации (unix)',
            'ip' => 'IP',
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
     * whitespace validator.
     */
    public function validateNotBlank($attribute, $params)
    {
        $value = $this->$attribute;
        if ($value === null) {
            return;
        }
        if (is_array($value)) {
            $value = implode(' ', $value);
        }
        // trim whitespace (including NBSP)
        $stripped = preg_replace('/\s+/u', '', $value);
        if ($stripped === '') {
            $this->addError($attribute, 'Сообщение не может состоять только из пробелов.');
        }
    }

    /**
     * rate limiting validator - no more than 1 message per RATE_SECONDS for a single author.
     *
     * Проверяет последний сохранённый топик данного автора (по author_id).
     * Если последний топик был опубликован позже, чем (now - RATE_SECONDS), добавляет ошибку с указанием времени,
     * когда можно опубликовать следующее сообщение.
     */
    public function validateRateLimit($attribute, $params)
    {
        if (empty($this->author_id)) {
            return;
        }

        $last = self::find()
            ->where(['author_id' => $this->author_id])
            ->andWhere(['>', 'published_at', 0])
            ->orderBy(['published_at' => SORT_DESC])
            ->limit(1)
            ->one();

        if ($last && $last->published_at > 0) {
            $allowedAt = (int)$last->published_at + self::RATE_SECONDS;
            $now = time();
            if ($allowedAt > $now) {
                $when = \Yii::$app->formatter->asDatetime($allowedAt, 'php:H:i:s');
                $wait = $allowedAt - $now;

                $minutes = floor($wait / 60);
                $seconds = $wait % 60;
                $this->addError($attribute, "Вы можете опубликовать следующее сообщение не ранее $when (через {$minutes}м {$seconds}с).");
            }
        }
    }

     /**
     * beforeSave: published_at (unix) / ip when insert scenario / created_at & updated_at setting
     */
    public function beforeSave($insert)
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($insert) {
            if (empty($this->published_at)) {
                $this->published_at = time();
            }
            $ip = \Yii::$app->request->userIP ?? null;
            if ($ip) {
                $this->ip = $ip;
            }
        } else {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            if ($insert && empty($this->created_at)) {
                $this->created_at = $now;
            }
            $this->updated_at = $now;
        }

        return true;
    }

     /**
     * Генерирует приватные токены (edit и delete) и сохраняет их в объекте (без сохранения в БД).
     */
    public function generateManagementTokens()
    {
        $sec = \Yii::$app->security;
        // (base62-ish)
        $this->edit_token = $sec->generateRandomString(48);
        $this->delete_token = $sec->generateRandomString(48);
    }

    /**
     * Проверка возможности редактирования: не удалён, имеется edit_token и время публикации в пределах 12 часов.
     */
    public function canEdit(): bool
    {
        if ($this->deleted_at) {
            return false;
        }
        if (empty($this->edit_token) || empty($this->published_at)) {
            return false;
        }
        $now = time();
        return ($this->published_at + self::EDIT_WINDOW_SECONDS) >= $now;
    }

    /**
     * Проверка возможности удаления: не удалён, имеется delete_token и время публикации в пределах 14 дней.
     */
    public function canDelete(): bool
    {
        if ($this->deleted_at) {
            return false;
        }
        if (empty($this->delete_token) || empty($this->published_at)) {
            return false;
        }
        $now = time();
        return ($this->published_at + self::DELETE_WINDOW_SECONDS) >= $now;
    }

    /**
     * Возвращает публичные URLs (полные) для управления постом
     */
    public function getEditUrl($absolute = true)
    {
        return Url::to(['site/edit-topic', 'token' => $this->edit_token], $absolute);
    }

    public function getDeleteUrl($absolute = true)
    {
        return Url::to(['site/delete-confirm', 'token' => $this->delete_token], $absolute);
    }

    /**
     * Soft-delete: устанавливаем deleted_at = time() и сохраняем. Не физически удаляем.
     */
    public function softDelete()
    {
        $this->deleted_at = time();
        return $this->save(false, ['deleted_at', 'updated_at']); // save without validation
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
            $phs = array_values(array_filter($value, function ($val) {
                return trim($val) !== '';
            }));
            $ex = implode("\n", $phs);
            $this->setAttribute('excerpt', $ex);
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
            $result = array_values(array_filter($ex, function ($value) {
                return trim($value) !== '';
            }));
            return implode('\n', $result);
        }
        return (string)$ex;
    }
}
