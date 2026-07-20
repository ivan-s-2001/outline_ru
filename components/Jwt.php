<?php

namespace app\components;

use yii\base\InvalidConfigException;

final class Jwt
{
    public static function encode(array $payload, string $secret): string
    {
        if (strlen($secret) < 32) {
            throw new InvalidConfigException('COLLABORATION_SECRET должен содержать не менее 32 символов.');
        }
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [self::base64Url(json_encode($header, JSON_UNESCAPED_SLASHES)), self::base64Url(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))];
        $signature = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = self::base64Url($signature);
        return implode('.', $segments);
    }

    private static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
