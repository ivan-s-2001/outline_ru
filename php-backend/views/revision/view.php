<?php

declare(strict_types=1);
use app\assets\EditorAsset;
use app\models\Document;
use app\models\Revision;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var Revision $revision */
/** @var Document $document */
/** @var bool $canUpdate */
$this->title = sprintf('Версия %d — %s', (int)$revision->revision_number, $revision->title ?: 'Без названия');
$contentJson = Json::encode($revision->getContentData());
$editorJs = Yii::getAlias('@webroot/editor/outline-editor.js');
$editorCss = Yii::getAlias('@webroot/editor/outline-editor.css');
if (is_file($editorJs) && is_file($editorCss)) {
    EditorAsset::register($this);
}
?>
<div class="document-view-shell">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="<?= Url::to(['/revision/index', 'documentId' => $document->id]) ?>">← Ко всей истории</a>
            <h1 class="h3 mt-2 mb-1">Версия <?= Html::encode((string)$revision->revision_number) ?></h1>
            <div class="text-body-secondary">
                <?= Html::encode($revision->user?->getFullName() ?: 'Система') ?> · <?= Html::encode((string)$revision->created_at) ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/document/view', 'id' => $document->id]) ?>">Текущая версия</a>
            <?php if ($canUpdate && (int)$revision->revision_number !== (int)$document->revision_number): ?>
                <?= Html::beginForm(['/revision/restore', 'id' => $revision->id], 'post') ?>
                <?= Html::submitButton('Восстановить эту версию', [
                    'class' => 'btn btn-primary',
                    'data-confirm' => 'Восстановить эту версию как новую ревизию?',
                ]) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </div>
    </div>

    <article class="card border-0 shadow-sm editor-card">
        <div class="card-body p-4 p-lg-5">
            <h2 class="display-6 fw-semibold text-break mb-4"><?= Html::encode($revision->title ?: 'Без названия') ?></h2>
            <div
                id="outline-rich-editor"
                class="outline-rich-editor"
                data-document-id="<?= Html::encode($document->id) ?>"
                data-editor-mode="read"
                data-content-json="<?= Html::encode($contentJson) ?>"
            ></div>
            <div class="document-rendered-fallback" data-editor-fallback>
                <?php if (trim((string)$revision->content_text) === ''): ?>
                    <p class="text-body-secondary fst-italic">Эта версия не содержит текста.</p>
                <?php else: ?>
                    <?= nl2br(Html::encode((string)$revision->content_text)) ?>
                <?php endif; ?>
            </div>
        </div>
    </article>
</div>
