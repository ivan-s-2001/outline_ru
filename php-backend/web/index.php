<?php

declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$config = require dirname(__DIR__) . '/config/web.php';
(new yii\web\Application($config))->run();
