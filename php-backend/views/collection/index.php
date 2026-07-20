<?php

declare(strict_types=1);

use app\models\Collection;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
$this->title = 'Коллекции';
$models = $provider->getModels();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Коллекции</h1>
        <p class="text-body-secondary mb-0">Разделяйте документы по командам, процессам и областям знаний.</p>
    </div>
    <a class="btn btn-primary" href="<?= Url::to(['/collection/create']) ?>">Новая коллекция</a>
</div>

<?php if (!$models): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <div class="display-6 mb-3">📚</div>
            <h2 class="h5">Коллекций пока нет</h2>
            <p class="text-body-secondary">Создайте первую коллекцию и добавьте в неё документы.</p>
            <a class="btn btn-primary" href="<?= Url::to(['/collection/create']) ?>">Создать коллекцию</a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($models as $model): ?>
            <?php /** @var Collection $model */ ?>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="card h-100 border-0 shadow-sm text-decoration-none text-body dashboard-stat" href="<?= Url::to(['/collection/view', 'id' => $model->id]) ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <span class="collection-icon" style="--collection-color: <?= Html::encode($model->color ?: '#4E5C6E') ?>">
                                <?= Html::encode($model->icon ?: '📁') ?>
                            </span>
                            <div class="min-w-0">
                                <h2 class="h5 text-truncate mb-1"><?= Html::encode($model->name) ?></h2>
                                <p class="text-body-secondary mb-0 collection-description"><?= Html::encode($model->description ?: 'Без описания') ?></p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4">
        <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
    </div>
<?php endif; ?>
