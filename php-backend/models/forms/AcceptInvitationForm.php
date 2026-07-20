<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class AcceptInvitationForm extends Model
{
    public string $login = '';
    public string $lastName = '';
    public string $firstName = '';
    public string $middleName = '';
    public string $password = '';
    public string $passwordRepeat = '';

    public function rules(): array
    {
        return [
            [['login', 'lastName', 'firstName', 'middleName', 'password', 'passwordRepeat'], 'required'],
            [['login'], 'match', 'pattern' => '/^[a-zA-Z0-9._-]+$/', 'message' => 'Логин может содержать латинские буквы, цифры, точку, дефис и подчёркивание.'],
            [['login'], 'string', 'min' => 3, 'max' => 120],
            [['lastName', 'firstName', 'middleName'], 'string', 'min' => 1, 'max' => 120],
            [['password'], 'string', 'min' => 10, 'max' => 1024],
            [['passwordRepeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'Логин',
            'lastName' => 'Фамилия',
            'firstName' => 'Имя',
            'middleName' => 'Отчество',
            'password' => 'Пароль',
            'passwordRepeat' => 'Повторите пароль',
        ];
    }
}
