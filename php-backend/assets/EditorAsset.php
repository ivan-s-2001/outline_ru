<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

final class EditorAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';

    public $css = [
        'editor/outline-editor.css',
    ];

    public $js = [
        'editor/outline-editor.js',
    ];

    public $jsOptions = [
        'type' => 'module',
    ];

    public $depends = [
        AppAsset::class,
    ];
}
