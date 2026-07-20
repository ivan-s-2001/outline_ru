<?php

declare(strict_types=1);

namespace app\services;

use app\models\forms\InstallForm;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;

final class AuthService
{
    public function install(InstallForm $form): User
    {
        if (User::find()->exists()) {
            throw new RuntimeException('Установка уже выполнена.');
        }
        if (!$form->validate()) {
            throw new RuntimeException($this->firstError($form->getFirstErrors()));
        }

        [$lastName, $firstName, $middleName] = $form->splitFullName();
        $workspaceId = $this->uuid();
        $userId = $this->uuid();
        $now = gmdate('Y-m-d H:i:s.u');

        $transaction = Yii::$app->db->beginTransaction();
        try {
            Yii::$app->db->createCommand()->insert('{{%workspaces}}', [
                'id' => $workspaceId,
                'name' => trim($form->workspaceName),
                'default_language' => 'ru_RU',
                'created_at' => $now,
                'updated_at' => $now,
            ])->execute();

            $user = new User();
            $user->id = $userId;
            $user->workspace_id = $workspaceId;
            $user->login = mb_strtolower(trim($form->login));
            $user->email = mb_strtolower(trim($form->email));
            $user->last_name = $lastName;
            $user->first_name = $firstName;
            $user->middle_name = $middleName;
            $user->role = 'owner';
            $user->status = 'active';
            $user->auth_key = Yii::$app->security->generateRandomString(64);
            $user->color = '#4E5C6E';
            $user->created_at = $now;
            $user->updated_at = $now;
            $user->setPassword($form->password);

            if (!$user->save()) {
                throw new RuntimeException($this->firstError($user->getFirstErrors()));
            }

            $transaction->commit();
            return $user;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось выполнить операцию.';
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12)
        );
    }
}
