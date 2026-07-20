<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class InviteForm extends Model
{
    public string $email = '';
    public string $role = 'member';

    public function rules(): array
    {
        return [
            [['email', 'role'], 'required'],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['role'], 'in', 'range' => ['admin', 'member', 'viewer']],
        ];
    }

    public function attributeLabels(): array
    {
        return ['email' => 'Email', 'role' => 'Роль'];
    }
}
