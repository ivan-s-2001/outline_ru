<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\Invitation;
use app\models\forms\AcceptInvitationForm;
use app\models\forms\InviteForm;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Expression;

final class InvitationService
{
    /** @return array{invitation:Invitation,token:string} */
    public function issue(InviteForm $form, User $actor): array
    {
        if (!$actor->isAdmin()) {
            throw new RuntimeException('Недостаточно прав для приглашения пользователей.');
        }
        if (!$form->validate()) {
            throw new RuntimeException($this->firstError($form->getFirstErrors()));
        }
        $email = mb_strtolower(trim($form->email));
        if (User::find()->where(['email' => $email])->exists()) {
            throw new RuntimeException('Пользователь с таким email уже существует.');
        }

        Invitation::updateAll(
            ['revoked_at' => new Expression('CURRENT_TIMESTAMP(6)')],
            [
                'workspace_id' => $actor->workspace_id,
                'email' => $email,
                'accepted_at' => null,
                'revoked_at' => null,
            ]
        );
        $token = 'inv_' . bin2hex(random_bytes(32));
        $invitation = new Invitation([
            'id' => BaseRecord::uuid(),
            'workspace_id' => $actor->workspace_id,
            'email' => $email,
            'role' => $form->role,
            'token_hash' => hash('sha256', $token),
            'invited_by_id' => $actor->id,
            'expires_at' => gmdate('Y-m-d H:i:s.u', time() + 7 * 86400),
        ]);
        if (!$invitation->save()) {
            throw new RuntimeException($this->firstError($invitation->getFirstErrors()));
        }

        $workspaceName = (string)Yii::$app->db->createCommand(
            'SELECT name FROM {{%workspaces}} WHERE id=:id',
            [':id' => $actor->workspace_id]
        )->queryScalar();
        $url = rtrim((string)env('APP_URL', 'http://outline.local'), '/')
            . '/invite/accept?token=' . rawurlencode($token);
        $subject = 'Приглашение в ' . ($workspaceName ?: env('APP_NAME', 'Outline'));
        $text = "Вас пригласили в рабочее пространство {$workspaceName}.\n\n"
            . "Откройте ссылку и задайте логин и пароль:\n{$url}\n\n"
            . "Ссылка действует 7 дней.";
        $html = '<p>Вас пригласили в рабочее пространство <strong>'
            . htmlspecialchars($workspaceName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</strong>.</p><p><a href="'
            . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">Принять приглашение</a></p><p>Ссылка действует 7 дней.</p>';
        (new MailService())->queue($email, $subject, $text, $html);

        return ['invitation' => $invitation, 'token' => $token];
    }

    public function findUsable(string $rawToken): Invitation
    {
        $invitation = Invitation::findOne(['token_hash' => hash('sha256', $rawToken)]);
        if (!$invitation || !$invitation->isUsable()) {
            throw new RuntimeException('Приглашение недействительно или истекло.');
        }
        return $invitation;
    }

    public function accept(Invitation $invitation, AcceptInvitationForm $form): User
    {
        if (!$invitation->isUsable()) {
            throw new RuntimeException('Приглашение недействительно или истекло.');
        }
        if (!$form->validate()) {
            throw new RuntimeException($this->firstError($form->getFirstErrors()));
        }
        $login = mb_strtolower(trim($form->login));
        if (User::find()->where(['or', ['login' => $login], ['email' => $invitation->email]])->exists()) {
            throw new RuntimeException('Пользователь с таким логином или email уже существует.');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->id = BaseRecord::uuid();
            $user->workspace_id = $invitation->workspace_id;
            $user->login = $login;
            $user->email = mb_strtolower((string)$invitation->email);
            $user->last_name = trim($form->lastName);
            $user->first_name = trim($form->firstName);
            $user->middle_name = trim($form->middleName);
            $user->role = $invitation->role;
            $user->status = 'active';
            $user->auth_key = Yii::$app->security->generateRandomString(64);
            $user->color = $this->userColor((string)$user->id);
            $user->created_at = gmdate('Y-m-d H:i:s.u');
            $user->updated_at = gmdate('Y-m-d H:i:s.u');
            $user->setPassword($form->password);
            if (!$user->save()) {
                throw new RuntimeException($this->firstError($user->getFirstErrors()));
            }
            $claimed = Invitation::updateAll(
                ['accepted_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['id' => $invitation->id, 'accepted_at' => null, 'revoked_at' => null]
            );
            if ($claimed !== 1) {
                throw new RuntimeException('Приглашение уже было использовано.');
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
        return 'Некорректные данные.';
    }
}
