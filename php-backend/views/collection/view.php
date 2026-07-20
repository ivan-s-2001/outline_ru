<?php

declare(strict_types=1);

use app\models\Collection;
use app\models\Document;
use yii\data\BaseDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Collection $model */
/** @var BaseDataProvider $documents */
/** @var bool $canUpdate */
$this->title = $model->name;
$items = $documents->getModels();
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-start gap-3 min-w-0">
        <span class="collection-icon collection-icon-lg" style="--collection-color: <?= Html::encode($model->color ?: '#4E5C6E') ?>">
            <?= Html::encode($model->icon ?: '📁') ?>
        </span>
        <div class="min-w-0">
            <h1 class="h3 mb-1 text-break"><?= Html::encode($model->name) ?></h1>
            <p class="text-body-secondary mb-0"><?= Html::encode($model->description ?: 'Без описания') ?></p>
        </div>
    </div>
    <?php if ($canUpdate): ?>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/collection/update', 'id' => $model->id]) ?>">Настройки</a>
            <a class="btn btn-primary" href="<?= Url::to(['/document/create', 'collectionId' => $model->id]) ?>">Новый документ</a>
        </div>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-body border-0 pt-4 px-4">
        <div class="d-flex align-items-center justify-content-between">
            <h2 class="h5 mb-0">Документы</h2>
            <span class="badge text-bg-secondary"><?= count($items) ?></span>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (!$items): ?>
            <div class="text-center py-5 px-3">
                <div class="display-6 mb-3">📝</div>
                <h3 class="h5">Документов пока нет</h3>
                <?php if ($canUpdate): ?>
                    <p class="text-body-secondary">Создайте первый документ в этой коллекции.</p>
                    <a class="btn btn-primary" href="<?= Url::to(['/document/create', 'collectionId' => $model->id]) ?>">Создать документ</a>
                <?php else: ?>
                    <p class="text-body-secondary mb-0">В доступной части коллекции пока нет документов.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($items as $document): ?>
                    <?php /** @var Document $document */ ?>
                    <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-3 px-4 py-3" href="<?= Url::to(['/document/view', 'id' => $document->id]) ?>">
                        <div class="min-w-0">
                            <div class="fw-semibold text-truncate"><?= Html::encode($document->title ?: 'Без названия') ?></div>
                            <div class="small text-body-secondary text-truncate"><?= Html::encode(mb_substr(trim((string)$document->content_text), 0, 140) ?: 'Пустой документ') ?></div>
                        </div>
                        <span class="text-body-secondary" aria-hidden="true">›</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canUpdate): ?>
    <div class="mt-4 border-top pt-4">
        <?= Html::beginForm(['/collection/archive', 'id' => $model->id], 'post', ['data-confirm' => 'Переместить коллекцию в архив?']) ?>
        <?= Html::submitButton('Архивировать коллекцию', ['class' => 'btn btn-outline-danger btn-sm']) ?>
        <?= Html::endForm() ?>
    </div>
<?php endif; ?>
