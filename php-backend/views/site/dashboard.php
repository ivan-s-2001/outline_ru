<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\helpers\Url;

/** @var int $collectionsCount */
/** @var int $documentsCount */
/** @var int $usersCount */
$this->title = 'Главная';
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Рабочее пространство</h1>
        <p class="text-body-secondary mb-0">Документы, знания и управление командой.</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/collection/create']) ?>">Новая коллекция</a>
        <a class="btn btn-primary" href="<?= Url::to(['/document/create']) ?>">Новый документ</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        ['label' => 'Коллекции', 'value' => $collectionsCount, 'href' => ['/collection/index']],
        ['label' => 'Документы', 'value' => $documentsCount, 'href' => ['/document/create']],
        ['label' => 'Пользователи', 'value' => $usersCount, 'href' => '#'],
    ] as $stat): ?>
        <div class="col-12 col-md-4">
            <a class="card h-100 border-0 shadow-sm text-decoration-none text-body dashboard-stat" href="<?= is_array($stat['href']) ? Url::to($stat['href']) : Html::encode($stat['href']) ?>">
                <div class="card-body">
                    <div class="text-body-secondary small mb-2"><?= Html::encode($stat['label']) ?></div>
                    <div class="display-6 fw-semibold"><?= Html::encode((string)$stat['value']) ?></div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <h2 class="h5">Перенос функциональности Outline</h2>
        <p class="text-body-secondary">Основная оболочка работает на Yii и Bootstrap 5. Следующими модулями подключаются коллекции, документы, редактор блоков, комментарии и совместное редактирование.</p>
        <div class="progress" role="progressbar" aria-label="Готовность первого вертикального среза" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100">
            <div class="progress-bar" style="width: 20%">20%</div>
        </div>
    </div>
</div>
