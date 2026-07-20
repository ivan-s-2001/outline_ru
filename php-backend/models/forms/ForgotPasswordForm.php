<?php

declare(strict_types=1);

namespace app\models\forms;

use yii\base\Model;

final class ForgotPasswordForm extends Model
{
    public string $identity = '';

    public function rules(): array
    {
        return [
            [['identity'], 'required'],
            [['identity'], 'string', 'min' => 3, 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return ['identity' => 'Логин или email'];
    }
}
