<?php

declare(strict_types=1);

return [
    'class' => yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%d;dbname=%s',
        env('DB_HOST', '127.0.0.1'),
        (int)env('DB_PORT', 3306),
        env('DB_NAME', 'outline')
    ),
    'username' => (string)env('DB_USER', 'root'),
    'password' => (string)env('DB_PASSWORD', ''),
    'charset' => (string)env('DB_CHARSET', 'utf8mb4'),
    'attributes' => [
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ],
    'enableSchemaCache' => !env('APP_DEBUG', false),
    'schemaCacheDuration' => 3600,
    'schemaCache' => 'cache',
];
