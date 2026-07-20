<?php

declare(strict_types=1);

return [
    'id' => 'outline-yii-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\\commands',
    'language' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'components' => [
        'db' => require __DIR__ . '/db.php',
        'cache' => ['class' => yii\caching\FileCache::class],
        'log' => [
            'targets' => [[
                'class' => yii\log\FileTarget::class,
                'levels' => ['error', 'warning', 'info'],
                'logVars' => [],
            ]],
        ],
    ],
    'controllerMap' => [
        'migrate' => [
            'class' => yii\console\controllers\MigrateController::class,
            'migrationPath' => '@app/migrations',
        ],
    ],
];
