<?php

declare(strict_types=1);

use app\models\Document;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var string $query */
/** @var array<string,float> $scores */
/** @var ArrayDataProvider $provider */
$this->title = 'Поиск';
$documents = $provider->getModels();
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <div class="mb-4">
            <h1 class="h3 mb-1">Поиск по документам</h1>
            <p class="text-body-secondary mb-0">Результаты учитывают права пользователя на коллекции и документы.</p>
        </div>

        <?= Html::beginForm(['/search/index'], 'get', ['class' => 'mb-4']) ?>
        <div class="input-group input-group-lg shadow-sm rounded-3">
            <?= Html::textInput('q', $query, [
                'class' => 'form-control',
                'type' => 'search',
                'autocomplete' => 'off',
                'autofocus' => true,
                'maxlength' => 500,
                'placeholder' => 'Название или текст документа',
                'aria-label' => 'Поисковый запрос',
            ]) ?>
            <?= Html::submitButton('Найти', ['class' => 'btn btn-primary px-4']) ?>
        </div>
        <?= Html::endForm() ?>

        <?php if ($query === ''): ?>
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="display-6 mb-3">⌕</div>
                    <p class="text-body-secondary mb-0">Введите запрос, чтобы найти доступные документы.</p>
                </div>
            </div>
        <?php elseif (!$documents): ?>
            <div class="alert alert-light border shadow-sm" role="status">
                По запросу «<?= Html::encode($query) ?>» доступных документов не найдено.
            </div>
        <?php else: ?>
            <div class="d-flex align-items-center justify-content-between gap-3 mb-2">
                <div class="small text-body-secondary">Найдено: <?= count($documents) ?></div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="list-group list-group-flush">
                    <?php foreach ($documents as $document): ?>
                        <?php /** @var Document $document */ ?>
                        <a class="list-group-item list-group-item-action px-4 py-3" href="<?= Url::to(['/document/view', 'id' => $document->id]) ?>">
                            <div class="d-flex align-items-start justify-content-between gap-3">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= Html::encode($document->title ?: 'Без названия') ?></div>
                                    <div class="small text-body-secondary mb-1">
                                        <?= Html::encode($document->collection?->name ?: 'Без коллекции') ?>
                                    </div>
                                    <div class="search-snippet text-body-secondary">
                                        <?= Html::encode(mb_substr(trim((string)$document->content_text), 0, 260) ?: 'Пустой документ') ?>
                                    </div>
                                </div>
                                <span class="badge text-bg-light border">
                                    <?= number_format($scores[(string)$document->id] ?? 0, 2, ',', ' ') ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-4">
                <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
