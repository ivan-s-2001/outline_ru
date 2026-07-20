<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class ResetPasswordForm extends Model
{
    public string $password = '';
    public string $passwordRepeat = '';

    public function rules(): array
    {
        return [
            [['password', 'passwordRepeat'], 'required'],
            [['password'], 'string', 'min' => 10, 'max' => 1024],
            [['passwordRepeat'], 'compare', 'compareAttribute' => 'password', 'message' => 'Пароли не совпадают.'],
        ];
    }

    public function attributeLabels(): array
    {
        return ['password' => 'Новый пароль', 'passwordRepeat' => 'Повторите пароль'];
    }
}
