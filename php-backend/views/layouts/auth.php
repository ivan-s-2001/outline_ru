<?php

declare(strict_types=1);

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Html::encode(Yii::$app->language) ?>" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title ?: Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body class="auth-page">
<?php $this->beginBody() ?>
<main class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="auth-shell w-100">
        <div class="text-center mb-4">
            <div class="brand-mark mx-auto mb-3" aria-hidden="true">O</div>
            <h1 class="h4 mb-1"><?= Html::encode(Yii::$app->name) ?></h1>
            <p class="text-body-secondary mb-0">База знаний и управление командой</p>
        </div>
        <?= $content ?>
    </div>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
