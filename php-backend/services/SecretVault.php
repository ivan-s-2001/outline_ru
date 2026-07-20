<?php

declare(strict_types=1);

namespace app\services;

use RuntimeException;
use Yii;

final class SecretVault
{
    public function encrypt(string $value): string
    {
        $encrypted = Yii::$app->security->encryptByPassword($value, $this->key());
        if ($encrypted === false) {
            throw new RuntimeException('Не удалось зашифровать секрет.');
        }
        return base64_encode($encrypted);
    }

    public function decrypt(string $value): string
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw new RuntimeException('Секрет интеграции повреждён.');
        }
        $plain = Yii::$app->security->decryptByPassword($decoded, $this->key());
        if ($plain === false) {
            throw new RuntimeException('Не удалось расшифровать секрет интеграции.');
        }
        return $plain;
    }

    private function key(): string
    {
        $key = (string)env('APP_SECRET', env('COOKIE_VALIDATION_KEY', ''));
        if (strlen($key) < 32) {
            throw new RuntimeException('APP_SECRET должен содержать не менее 32 символов.');
        }
        return $key;
    }
}
