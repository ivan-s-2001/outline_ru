<?php

declare(strict_types=1);

use app\models\Group;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
$this->title = 'Группы';
$groups = $provider->getModels();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Группы пользователей</h1>
        <p class="text-body-secondary mb-0">Назначайте права коллекций и документов сразу нескольким сотрудникам.</p>
    </div>
    <a class="btn btn-primary" href="<?= Url::to(['/group/create']) ?>">Новая группа</a>
</div>

<div class="card border-0 shadow-sm">
    <?php if (!$groups): ?>
        <div class="card-body text-center py-5">
            <div class="display-6 mb-3">👥</div>
            <h2 class="h5">Групп пока нет</h2>
            <p class="text-body-secondary">Создайте группу для отдела, проекта или роли.</p>
            <a class="btn btn-primary" href="<?= Url::to(['/group/create']) ?>">Создать группу</a>
        </div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($groups as $group): ?>
                <?php /** @var Group $group */ ?>
                <a class="list-group-item list-group-item-action d-flex align-items-center justify-content-between gap-3 px-4 py-3" href="<?= Url::to(['/group/view', 'id' => $group->id]) ?>">
                    <div class="min-w-0">
                        <div class="fw-semibold text-truncate"><?= Html::encode($group->name) ?></div>
                        <div class="small text-body-secondary text-truncate"><?= Html::encode($group->description ?: 'Без описания') ?></div>
                    </div>
                    <span class="badge rounded-pill text-bg-secondary"><?= count($group->users) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="mt-4">
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
