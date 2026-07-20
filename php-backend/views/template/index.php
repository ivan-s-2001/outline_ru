<?php

declare(strict_types=1);
use app\models\Template;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
/** @var bool $canCreate */
$this->title = 'Шаблоны';
$models = $provider->getModels();
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Шаблоны</h1>
        <p class="text-body-secondary mb-0">Повторно используемая структура и содержимое документов.</p>
    </div>
    <?php if ($canCreate): ?>
        <a class="btn btn-primary" href="<?= Url::to(['/template/create']) ?>">Новый шаблон</a>
    <?php endif; ?>
</div>

<div class="row g-3">
    <?php foreach ($models as $model): ?>
        <?php /** @var Template $model */ ?>
        <div class="col-12 col-md-6 col-xl-4">
            <article class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column">
                    <h2 class="h5 text-break"><?= Html::encode($model->name) ?></h2>
                    <p class="text-body-secondary flex-grow-1">
                        <?= Html::encode($model->description ?: 'Без описания') ?>
                    </p>
                    <div class="small text-body-secondary mb-3">
                        Автор: <?= Html::encode($model->creator?->getFullName() ?: 'Система') ?>
                    </div>
                    <a class="btn btn-outline-primary stretched-link" href="<?= Url::to(['/template/view', 'id' => $model->id]) ?>">Открыть</a>
                </div>
            </article>
        </div>
    <?php endforeach; ?>

    <?php if (!$models): ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center">
                    <div class="display-6 mb-3">📄</div>
                    <h2 class="h5">Шаблонов пока нет</h2>
                    <p class="text-body-secondary">Создайте основу для типовых документов.</p>
                    <?php if ($canCreate): ?>
                        <a class="btn btn-primary" href="<?= Url::to(['/template/create']) ?>">Создать шаблон</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
