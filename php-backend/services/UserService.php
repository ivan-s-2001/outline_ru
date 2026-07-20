<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\forms\UserForm;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;

final class UserService
{
    public function save(UserForm $form, User $actor, ?User $target = null): User
    {
        if (!$actor->isAdmin()) {
            throw new RuntimeException('Недостаточно прав для управления пользователями.');
        }
        if (!$form->validate()) {
            throw new RuntimeException($this->firstError($form->getFirstErrors()));
        }

        $isNew = $target === null;
        $target ??= new User();
        if (!$isNew && $target->workspace_id !== $actor->workspace_id) {
            throw new RuntimeException('Пользователь относится к другому рабочему пространству.');
        }

        if (!$isNew) {
            $this->assertOwnerContinuity($target, $form);
            if ($target->id === $actor->id && $form->status !== 'active') {
                throw new RuntimeException('Нельзя заблокировать собственную учётную запись.');
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($isNew) {
                $target->id = BaseRecord::uuid();
                $target->workspace_id = $actor->workspace_id;
                $target->auth_key = Yii::$app->security->generateRandomString(64);
                $target->color = $this->userColor((string)$target->id);
                $target->created_at = gmdate('Y-m-d H:i:s.u');
            }

            $target->login = mb_strtolower(trim($form->login));
            $target->email = mb_strtolower(trim($form->email));
            $target->last_name = trim($form->lastName);
            $target->first_name = trim($form->firstName);
            $target->middle_name = trim($form->middleName);
            $target->role = $form->role;
            $target->status = $form->status;
            $target->updated_at = gmdate('Y-m-d H:i:s.u');

            if ($form->password !== '') {
                $target->setPassword($form->password);
                $target->auth_key = Yii::$app->security->generateRandomString(64);
            }

            if (!$target->save()) {
                throw new RuntimeException($this->firstError($target->getFirstErrors()));
            }

            $transaction->commit();
            return $target;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }

    private function assertOwnerContinuity(User $target, UserForm $form): void
    {
        if ($target->role !== 'owner') {
            return;
        }
        if ($form->role === 'owner' && $form->status === 'active') {
            return;
        }

        $otherOwners = User::find()
            ->where([
                'workspace_id' => $target->workspace_id,
                'role' => 'owner',
                'status' => 'active',
            ])
            ->andWhere(['<>', 'id', $target->id])
            ->count();
        if ((int)$otherOwners === 0) {
            throw new RuntimeException('В рабочем пространстве должен остаться хотя бы один активный владелец.');
        }
    }

    private function userColor(string $id): string
    {
        $palette = ['#4E5C6E', '#3B82F6', '#8B5CF6', '#0F766E', '#B45309', '#BE123C'];
        return $palette[hexdec(substr(hash('sha256', $id), 0, 2)) % count($palette)];
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить пользователя.';
    }
}
