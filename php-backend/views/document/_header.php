<?php

declare(strict_types=1);

use app\models\Document;
use app\models\Favorite;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Document $model */
/** @var bool $canUpdate */
$currentUser = Yii::$app->user->identity instanceof User ? Yii::$app->user->identity : null;
$isFavorite = $currentUser && Favorite::find()->where([
    'workspace_id' => $currentUser->workspace_id,
    'user_id' => $currentUser->id,
    'document_id' => $model->id,
])->exists();
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div class="min-w-0">
        <?php if ($model->collection): ?>
            <a class="small text-decoration-none" href="<?= Url::to(['/collection/view', 'id' => $model->collection_id]) ?>"><?= Html::encode($model->collection->name) ?></a>
        <?php endif; ?>
        <h1 class="display-6 fw-semibold text-break mt-1 mb-2"><?= Html::encode($model->title ?: 'Без названия') ?></h1>
        <div class="small text-body-secondary">Ревизия <?= (int)$model->revision_number ?> · обновлено <?= Html::encode((string)$model->updated_at) ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?= Html::beginForm(['/library/toggle-favorite', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
        <?= Html::submitButton($isFavorite ? '★ В избранном' : '☆ В избранное', ['class' => 'btn btn-outline-secondary']) ?>
        <?= Html::endForm() ?>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/revision/index', 'documentId' => $model->id]) ?>">История</a>
        <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Экспорт</button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= Url::to(['/export/document', 'id' => $model->id, 'format' => 'md']) ?>">Markdown</a></li>
                <li><a class="dropdown-item" href="<?= Url::to(['/export/document', 'id' => $model->id, 'format' => 'html']) ?>">HTML</a></li>
                <li><a class="dropdown-item" href="<?= Url::to(['/export/document', 'id' => $model->id, 'format' => 'json']) ?>">ProseMirror JSON</a></li>
            </ul>
        </div>
        <?php if ($canUpdate): ?>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/share/manage', 'documentId' => $model->id]) ?>">Публикация</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/access/document', 'id' => $model->id]) ?>">Доступ</a>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/document/update', 'id' => $model->id]) ?>">Редактировать</a>
            <a class="btn btn-primary" href="<?= Url::to(['/document/create', 'parentId' => $model->id, 'collectionId' => $model->collection_id]) ?>">Дочерний документ</a>
            <?= Html::beginForm(['/library/trash-document', 'id' => $model->id], 'post', ['class' => 'd-inline']) ?>
            <?= Html::submitButton('В корзину', ['class' => 'btn btn-outline-danger', 'data-confirm' => 'Переместить документ и дочерние документы в корзину?']) ?>
            <?= Html::endForm() ?>
        <?php endif; ?>
    </div>
</div>
