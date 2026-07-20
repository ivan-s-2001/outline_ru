<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class InstallForm extends Model
{
    public string $workspaceName = '';
    public string $login = '';
    public string $email = '';
    public string $fullName = '';
    public string $password = '';
    public string $passwordRepeat = '';

    public function rules(): array
    {
        return [
            [['workspaceName', 'login', 'email', 'fullName', 'password', 'passwordRepeat'], 'required'],
            [['workspaceName'], 'string', 'min' => 2, 'max' => 255],
            [['login'], 'string', 'min' => 3, 'max' => 120],
            [['login'], 'match', 'pattern' => '/^[a-zA-Z0-9._-]+$/', 'message' => 'Используйте латинские буквы, цифры, точку, дефис и подчёркивание.'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['fullName'], 'validateFullName'],
            [['password'], 'string', 'min' => 10, 'max' => 1024],
            [['passwordRepeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'workspaceName' => 'Название рабочего пространства',
            'login' => 'Логин администратора',
            'email' => 'Email администратора',
            'fullName' => 'ФИО администратора',
            'password' => 'Пароль',
            'passwordRepeat' => 'Повторите пароль',
        ];
    }

    public function validateFullName(string $attribute): void
    {
        $parts = preg_split('/\s+/u', trim($this->$attribute)) ?: [];
        if (count($parts) < 3) {
            $this->addError($attribute, 'Пишите ФИО через пробел: фамилия имя отчество.');
        }
    }

    public function splitFullName(): array
    {
        $parts = preg_split('/\s+/u', trim($this->fullName)) ?: [];
        $lastName = (string)array_shift($parts);
        $firstName = (string)array_shift($parts);
        $middleName = implode(' ', $parts);
        return [$lastName, $firstName, $middleName];
    }
}
