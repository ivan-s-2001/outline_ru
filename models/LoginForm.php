<?php

namespace app\models;

use Yii;
use yii\base\Model;

class LoginForm extends Model
{
    public string $email = '';
    public string $password = '';
    public bool $rememberMe = true;
    private ?User $_user = null;

    public function rules(): array
    {
        return [
            [['email', 'password'], 'required'],
            [['email'], 'email'],
            [['rememberMe'], 'boolean'],
            [['password'], 'validatePassword'],
        ];
    }

    public function validatePassword(string $attribute): void
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();
            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Неверная почта или пароль.');
            }
        }
    }

    public function login(): bool
    {
        return $this->validate() && Yii::$app->user->login($this->getUser(), $this->rememberMe ? 2592000 : 0);
    }

    private function getUser(): ?User
    {
        if ($this->_user === null) {
            $this->_user = User::findOne(['email' => mb_strtolower(trim($this->email)), 'status' => User::STATUS_ACTIVE]);
        }
        return $this->_user;
    }
}
