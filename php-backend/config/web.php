<?php

declare(strict_types=1);

use app\models\User;
use yii\caching\FileCache;
use yii\log\FileTarget;
use yii\redis\Cache as RedisCache;
use yii\redis\Connection as RedisConnection;
use yii\redis\Session as RedisSession;
use yii\web\JsonParser;

$components = [
    'request' => [
        'cookieValidationKey' => (string)env('COOKIE_VALIDATION_KEY', 'replace-me'),
        'enableCsrfValidation' => true,
        'parsers' => [
            'application/json' => JsonParser::class,
        ],
    ],
    'response' => [
        'charset' => 'UTF-8',
    ],
    'user' => [
        'identityClass' => User::class,
        'enableAutoLogin' => true,
        'loginUrl' => ['/site/login'],
        'identityCookie' => [
            'name' => '_outline_identity',
            'httpOnly' => true,
            'sameSite' => yii\web\Cookie::SAME_SITE_LAX,
            'secure' => (bool)env('SESSION_COOKIE_SECURE', false),
        ],
    ],
    'session' => [
        'name' => 'outline-session',
        'cookieParams' => [
            'httponly' => true,
            'samesite' => yii\web\Cookie::SAME_SITE_LAX,
            'secure' => (bool)env('SESSION_COOKIE_SECURE', false),
        ],
    ],
    'cache' => ['class' => FileCache::class],
    'db' => require __DIR__ . '/db.php',
    'urlManager' => [
        'enablePrettyUrl' => true,
        'showScriptName' => false,
        'enableStrictParsing' => true,
        'rules' => [
            'GET health' => 'site/health',
            'GET,POST install' => 'site/install',
            'GET,POST login' => 'site/login',
            'POST logout' => 'site/logout',
            'GET dashboard' => 'site/dashboard',

            'GET collections' => 'collection/index',
            'GET,POST collections/create' => 'collection/create',
            'GET collections/<id:[0-9a-fA-F-]{36}>' => 'collection/view',
            'GET,POST collections/<id:[0-9a-fA-F-]{36}>/edit' => 'collection/update',
            'POST collections/<id:[0-9a-fA-F-]{36}>/archive' => 'collection/archive',

            'GET,POST documents/create' => 'document/create',
            'GET documents/<id:[0-9a-fA-F-]{36}>' => 'document/view',
            'GET,POST documents/<id:[0-9a-fA-F-]{36}>/edit' => 'document/update',
            'POST documents/<id:[0-9a-fA-F-]{36}>/archive' => 'document/archive',
            'POST documents/<documentId:[0-9a-fA-F-]{36}>/comments' => 'comment/create',
            'POST documents/<documentId:[0-9a-fA-F-]{36}>/comments/<parentId:[0-9a-fA-F-]{36}>/reply' => 'comment/create',
            'POST comments/<id:[0-9a-fA-F-]{36}>/update' => 'comment/update',
            'POST comments/<id:[0-9a-fA-F-]{36}>/resolve' => 'comment/resolve',
            'POST comments/<id:[0-9a-fA-F-]{36}>/delete' => 'comment/delete',

            'POST api/<method:[A-Za-z0-9._-]+>' => 'api/dispatch',
            'OPTIONS api/<method:[A-Za-z0-9._-]+>' => 'api/options',
        ],
    ],
    'assetManager' => [
        'appendTimestamp' => true,
        'linkAssets' => false,
    ],
    'log' => [
        'traceLevel' => env('APP_DEBUG', false) ? 3 : 0,
        'targets' => [[
            'class' => FileTarget::class,
            'levels' => ['error', 'warning', 'info'],
            'logVars' => [],
        ]],
    ],
];

if ((bool)env('REDIS_ENABLED', true)) {
    $components['redis'] = [
        'class' => RedisConnection::class,
        'hostname' => (string)env('REDIS_HOST', '127.0.0.1'),
        'port' => (int)env('REDIS_PORT', 6379),
        'database' => (int)env('REDIS_DATABASE', 4),
    ];
    $components['cache'] = ['class' => RedisCache::class, 'redis' => 'redis'];
    $components['session'] = [
        'class' => RedisSession::class,
        'redis' => 'redis',
        'name' => 'outline-session',
        'cookieParams' => $components['session']['cookieParams'],
    ];
}

return [
    'id' => 'outline-yii',
    'name' => (string)env('APP_NAME', 'Outline'),
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\\controllers',
    'defaultRoute' => 'site/dashboard',
    'language' => 'ru-RU',
    'sourceLanguage' => 'en-US',
    'timeZone' => 'Europe/Moscow',
    'components' => $components,
];
