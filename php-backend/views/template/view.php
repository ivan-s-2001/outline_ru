<?php

declare(strict_types=1);
use app\assets\EditorAsset;
use app\models\Template;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var Template $model */
/** @var bool $canManage */
/** @var bool $canUse */
$this->title = $model->name;
$contentJson = Json::encode($model->getContentData());
$editorJs = Yii::getAlias('@webroot/editor/outline-editor.js');
$editorCss = Yii::getAlias('@webroot/editor/outline-editor.css');
if (is_file($editorJs) && is_file($editorCss)) {
    EditorAsset::register($this);
}
?>
<div class="document-view-shell">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="<?= Url::to(['/template/index']) ?>">← Все шаблоны</a>
            <h1 class="h3 mt-2 mb-1"><?= Html::encode($model->name) ?></h1>
            <p class="text-body-secondary mb-0"><?= Html::encode($model->description ?: 'Без описания') ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if ($canManage): ?>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/template/update', 'id' => $model->id]) ?>">Редактировать</a>
            <?php endif; ?>
            <?php if ($canUse): ?>
                <a class="btn btn-primary" href="<?= Url::to(['/template/use', 'id' => $model->id]) ?>">Создать документ</a>
            <?php endif; ?>
        </div>
    </div>

    <article class="card border-0 shadow-sm editor-card">
        <div class="card-body p-4 p-lg-5">
            <div
                id="outline-rich-editor"
                class="outline-rich-editor"
                data-editor-mode="read"
                data-collaboration="false"
                data-content-json="<?= Html::encode($contentJson) ?>"
            ></div>
            <div class="document-rendered-fallback" data-editor-fallback>
                <?php if (trim((string)$model->content_text) === ''): ?>
                    <p class="text-body-secondary fst-italic">Шаблон пока пуст.</p>
                <?php else: ?>
                    <?= nl2br(Html::encode((string)$model->content_text)) ?>
                <?php endif; ?>
            </div>
        </div>
    </article>

    <?php if ($canManage): ?>
        <div class="border-top mt-4 pt-4">
            <?= Html::beginForm(['/template/delete', 'id' => $model->id], 'post') ?>
            <?= Html::submitButton('Удалить шаблон', [
                'class' => 'btn btn-outline-danger',
                'data-confirm' => 'Удалить шаблон? Уже созданные документы не изменятся.',
            ]) ?>
            <?= Html::endForm() ?>
        </div>
    <?php endif; ?>
</div>
