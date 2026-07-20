<?php

declare(strict_types=1);
use app\assets\AppAsset;
use yii\helpers\Html;
use yii\helpers\Url;

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Html::encode(Yii::$app->language) ?>" data-bs-theme="light">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title ? $this->title . ' — ' . Yii::$app->name : Yii::$app->name) ?></title>
    <?php $this->head() ?>
</head>
<body class="bg-body-tertiary">
<?php $this->beginBody() ?>
<header class="border-bottom bg-body">
    <div class="container-xl d-flex align-items-center justify-content-between gap-3 py-3">
        <a class="d-flex align-items-center gap-2 text-decoration-none text-body fw-semibold" href="<?= Url::to(['/site/login']) ?>">
            <span class="brand-mark brand-mark-sm">O</span>
            <span><?= Html::encode(Yii::$app->name) ?></span>
        </a>
        <span class="small text-body-secondary">Публичный документ</span>
    </div>
</header>
<main class="container-xl py-4 py-lg-5">
    <?= $content ?>
</main>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
