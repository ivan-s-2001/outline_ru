<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

new yii\console\Application([
    'id' => 'outline-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'components' => [
        'cache' => ['class' => yii\caching\ArrayCache::class],
        'security' => ['class' => yii\base\Security::class],
    ],
]);
