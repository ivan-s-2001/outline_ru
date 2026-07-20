<?php

declare(strict_types=1);

namespace app\models\forms;

use app\models\User;
use Yii;
use yii\base\Model;

final class LoginForm extends Model
{
    public string $login = '';
    public string $password = '';
    public bool $rememberMe = true;

    private ?User $user = null;

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            [['login'], 'string', 'min' => 3, 'max' => 255],
            [['password'], 'string', 'min' => 1, 'max' => 1024],
            [['rememberMe'], 'boolean'],
            [['password'], 'validatePassword'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => 'Логин',
            'password' => 'Пароль',
            'rememberMe' => 'Запомнить меня',
        ];
    }

    public function validatePassword(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        if (!$user || !$user->validatePassword($this->password)) {
            $this->addError($attribute, 'Неверный логин или пароль.');
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $duration = $this->rememberMe ? 30 * 86400 : 0;
        return Yii::$app->user->login($this->getUser(), $duration);
    }

    public function getUser(): ?User
    {
        if ($this->user === null) {
            $this->user = User::findByLoginOrEmail($this->login);
        }
        return $this->user;
    }
}
