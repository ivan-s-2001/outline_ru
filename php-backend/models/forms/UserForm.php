<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\User;
use yii\base\Model;

final class UserForm extends Model
{
    public ?string $id = null;
    public string $login = '';
    public string $email = '';
    public string $lastName = '';
    public string $firstName = '';
    public string $middleName = '';
    public string $role = 'member';
    public string $status = 'active';
    public string $password = '';
    public string $passwordRepeat = '';

    public function rules(): array
    {
        return [
            [['login', 'email', 'lastName', 'firstName', 'middleName', 'role', 'status'], 'required'],
            [['login'], 'string', 'min' => 3, 'max' => 120],
            [['login'], 'match', 'pattern' => '/^[a-zA-Z0-9._-]+$/', 'message' => 'Используйте латинские буквы, цифры, точку, дефис и подчёркивание.'],
            [['login'], 'validateUniqueLogin'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['email'], 'validateUniqueEmail'],
            [['lastName', 'firstName', 'middleName'], 'string', 'min' => 1, 'max' => 120],
            [['role'], 'in', 'range' => ['owner', 'admin', 'member', 'viewer']],
            [['status'], 'in', 'range' => ['active', 'suspended']],
            [['password'], 'string', 'min' => 10, 'max' => 1024, 'skipOnEmpty' => true],
            [['password'], 'required', 'when' => fn (): bool => $this->id === null, 'enableClientValidation' => false],
            [['passwordRepeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'Логин',
            'email' => 'Email',
            'lastName' => 'Фамилия',
            'firstName' => 'Имя',
            'middleName' => 'Отчество',
            'role' => 'Роль',
            'status' => 'Статус',
            'password' => $this->id === null ? 'Пароль' : 'Новый пароль',
            'passwordRepeat' => 'Повторите пароль',
        ];
    }

    public function validateUniqueLogin(string $attribute): void
    {
        $query = User::find()->where(['login' => mb_strtolower(trim($this->$attribute))]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'Этот логин уже занят.');
        }
    }

    public function validateUniqueEmail(string $attribute): void
    {
        $query = User::find()->where(['email' => mb_strtolower(trim($this->$attribute))]);
        if ($this->id !== null) {
            $query->andWhere(['<>', 'id', $this->id]);
        }
        if ($query->exists()) {
            $this->addError($attribute, 'Этот email уже используется.');
        }
    }

    public static function fromUser(User $user): self
    {
        $form = new self();
        $form->id = (string)$user->id;
        $form->login = (string)$user->login;
        $form->email = (string)$user->email;
        $form->lastName = (string)$user->last_name;
        $form->firstName = (string)$user->first_name;
        $form->middleName = (string)$user->middle_name;
        $form->role = (string)$user->role;
        $form->status = (string)$user->status;
        return $form;
    }
}
