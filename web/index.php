<?php

require dirname(__DIR__) . '/config/env.php';

defined('YII_DEBUG') or define('YII_DEBUG', (bool)env('APP_DEBUG', false));
defined('YII_ENV') or define('YII_ENV', (string)env('APP_ENV', 'prod'));

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/yiisoft/yii2/Yii.php';

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config))->run();
