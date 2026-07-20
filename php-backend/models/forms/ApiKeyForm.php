<?php

declare(strict_types=1);
namespace app\models\forms;

use yii\base\Model;

final class ApiKeyForm extends Model
{
    public string $name = '';
    public string $permission = 'read';
    public int $expiresInDays = 90;

    public function rules(): array
    {
        return [
            [['name', 'permission'], 'required'],
            [['name'], 'string', 'min' => 2, 'max' => 255],
            [['permission'], 'in', 'range' => ['read', 'write']],
            [['expiresInDays'], 'integer'],
            [['expiresInDays'], 'in', 'range' => [0, 30, 90, 365]],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'name' => 'Название ключа',
            'permission' => 'Уровень доступа',
            'expiresInDays' => 'Срок действия',
        ];
    }
}
