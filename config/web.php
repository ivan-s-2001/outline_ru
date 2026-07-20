<?php

require_once __DIR__ . '/env.php';

use app\models\User;
use yii\caching\FileCache;
use yii\log\FileTarget;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection as RedisConnection;
use yii\redis\Session as RedisSession;
use yii\web\ErrorHandler;

$components = [
    'request' => [
        'cookieValidationKey' => (string)env('COOKIE_VALIDATION_KEY', 'replace-me'),
        'enableCsrfValidation' => true,
    ],
    'cache' => ['class' => FileCache::class],
    'user' => [
        'identityClass' => User::class,
        'enableAutoLogin' => true,
        'loginUrl' => ['/site/login'],
        'identityCookie' => ['name' => '_outline_identity', 'httpOnly' => true, 'sameSite' => 'Lax'],
    ],
    'session' => [
        'name' => 'outline-php-session',
        'cookieParams' => ['httponly' => true, 'samesite' => 'Lax'],
    ],
    'errorHandler' => [
        'class' => ErrorHandler::class,
        'errorAction' => 'site/error',
    ],
    'db' => require __DIR__ . '/db.php',
    'urlManager' => [
        'enablePrettyUrl' => true,
        'showScriptName' => false,
        'rules' => [
            '' => 'site/index',
            'login' => 'site/login',
            'logout' => 'site/logout',
            'install' => 'site/install',
            'collections' => 'collection/index',
            'collections/create' => 'collection/create',
            'collections/<id:\\d+>' => 'collection/view',
            'collections/<id:\\d+>/edit' => 'collection/update',
            'documents/create' => 'document/create',
            'documents/<id:\\d+>' => 'document/view',
            'documents/<id:\\d+>/edit' => 'document/update',
            'documents/<id:\\d+>/history' => 'document/history',
            'documents/<document:\\d+>/collaboration-token' => 'collaboration/token',
            'search' => 'search/index',
            'schedule' => 'schedule/index',
            'vacation' => 'vacation/index',
            'duty' => 'duty/index',
            'admin/users' => 'admin/users',
            'admin/users/create' => 'admin/user',
            'admin/users/<id:\\d+>' => 'admin/user',
        ],
    ],
    'log' => [
        'traceLevel' => env('APP_DEBUG', false) ? 3 : 0,
        'targets' => [[
            'class' => FileTarget::class,
            'levels' => ['error', 'warning', 'info'],
        ]],
    ],
];

if ((bool)env('REDIS_ENABLED', false)) {
    $components['redis'] = [
        'class' => RedisConnection::class,
        'hostname' => env('REDIS_HOST', '127.0.0.1'),
        'port' => (int)env('REDIS_PORT', 6379),
        'database' => (int)env('REDIS_DATABASE', 4),
    ];
    $components['cache'] = ['class' => RedisCache::class, 'redis' => 'redis'];
    $components['session'] = ['class' => RedisSession::class, 'redis' => 'redis', 'name' => 'outline-php-session'];
}

return [
    'id' => 'outline-php',
    'name' => (string)env('APP_NAME', 'Outline PHP'),
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'sourceLanguage' => 'ru-RU',
    'timeZone' => 'Europe/Moscow',
    'bootstrap' => ['log'],
    'components' => $components,
    'params' => require __DIR__ . '/params.php',
];
