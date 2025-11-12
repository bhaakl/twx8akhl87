<?php

namespace app\models\form;

class AuthorForm extends \yii\base\Model
{
    public $name;
    public $email;
    public $msg;
    public $verifyCode;

    public function rules()
    {
        return [
            [['name', 'email', 'msg'], 'required', 'message' => 'Поле {attribute} не должно быть пустым'],
            ['email', 'email', 'message' => 'Введите корректный E-mail'],
             ['name', 'string',
                'min' => 2,
                'max' => 150,
                'tooShort' => 'Имя должно быть не короче {min} символов.',
                'tooLong'  => 'Имя должно быть не длиннее {max} символов.',
                'message'  => 'Поле {attribute} должно быть строкой.',
            ],
            ['msg', 'string',
                'min' => 5,
                'max' => 1000,
                'tooShort' => 'Поле {attribute} должно содержать минимум {min} символов.',
                'tooLong'  => 'Поле {attribute} должно содержать максимум {max} символов.',
                'message'  => 'Поле {attribute} должно быть строкой.',
            ],
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
}