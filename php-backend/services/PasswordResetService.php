<?php

declare(strict_types=1);

namespace app\services;

use app\models\BaseRecord;
use app\models\OAuthAccessToken;
use app\models\PasswordResetToken;
use app\models\User;
use RuntimeException;
use Throwable;
use Yii;
use yii\db\Expression;

final class PasswordResetService
{
    public function request(string $identity): void
    {
        $identity = mb_strtolower(trim($identity));
        $user = User::findByLoginOrEmail($identity);
        if (!$user) {
            return;
        }

        PasswordResetToken::updateAll(
            ['used_at' => new Expression('CURRENT_TIMESTAMP(6)')],
            ['user_id' => $user->id, 'used_at' => null]
        );
        $raw = 'rst_' . bin2hex(random_bytes(32));
        $token = new PasswordResetToken([
            'id' => BaseRecord::uuid(),
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $raw),
            'expires_at' => gmdate('Y-m-d H:i:s.u', time() + 3600),
        ]);
        if (!$token->save()) {
            throw new RuntimeException('Не удалось создать ссылку восстановления пароля.');
        }

        $url = rtrim((string)env('APP_URL', 'http://outline.local'), '/')
            . '/password/reset?token=' . rawurlencode($raw);
        $text = "Получен запрос на восстановление пароля.\n\n"
            . "Откройте ссылку в течение одного часа:\n{$url}\n\n"
            . "Если запрос сделали не вы, ничего не предпринимайте.";
        $html = '<p>Получен запрос на восстановление пароля.</p><p><a href="'
            . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">Задать новый пароль</a></p><p>Ссылка действует один час. Если запрос сделали не вы, ничего не предпринимайте.</p>';
        (new MailService())->queue(
            (string)$user->email,
            'Восстановление пароля ' . (string)env('APP_NAME', 'Outline'),
            $text,
            $html
        );
    }

    public function findUsable(string $raw): PasswordResetToken
    {
        $token = PasswordResetToken::find()->with('user')->where([
            'token_hash' => hash('sha256', $raw),
        ])->one();
        if (!$token || !$token->isUsable() || !$token->user || $token->user->status !== 'active') {
            throw new RuntimeException('Ссылка восстановления недействительна или истекла.');
        }
        return $token;
    }

    public function reset(PasswordResetToken $token, string $password): User
    {
        if (!$token->isUsable() || !$token->user) {
            throw new RuntimeException('Ссылка восстановления недействительна или истекла.');
        }
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $claimed = PasswordResetToken::updateAll(
                ['used_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['id' => $token->id, 'used_at' => null]
            );
            if ($claimed !== 1) {
                throw new RuntimeException('Ссылка восстановления уже использована.');
            }

            $user = $token->user;
            $user->setPassword($password);
            $user->auth_key = Yii::$app->security->generateRandomString(64);
            $user->updated_at = gmdate('Y-m-d H:i:s.u');
            if (!$user->save(false, ['password_hash', 'auth_key', 'updated_at'])) {
                throw new RuntimeException('Не удалось сохранить новый пароль.');
            }
            Yii::$app->db->createCommand()->update(
                '{{%api_keys}}',
                ['revoked_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['user_id' => $user->id, 'revoked_at' => null]
            )->execute();
            OAuthAccessToken::updateAll(
                ['revoked_at' => new Expression('CURRENT_TIMESTAMP(6)')],
                ['user_id' => $user->id, 'revoked_at' => null]
            );
            $transaction->commit();
            return $user;
        } catch (Throwable $error) {
            if ($transaction->isActive) {
                $transaction->rollBack();
            }
            throw $error;
        }
    }
}
