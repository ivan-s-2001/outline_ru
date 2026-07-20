<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

final class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'css/app.css',
    ];

    public $js = [
        'js/app.js',
    ];

    public $depends = [
        yii\web\YiiAsset::class,
        yii\bootstrap5\BootstrapAsset::class,
        yii\bootstrap5\BootstrapPluginAsset::class,
    ];
}
