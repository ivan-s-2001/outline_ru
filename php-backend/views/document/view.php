<?php

declare(strict_types=1);
use app\assets\EditorAsset;
use app\models\Attachment;
use app\models\Comment;
use app\models\Document;
use app\models\Revision;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var Document $model */
/** @var Revision[] $revisions */
/** @var Document[] $children */
/** @var Comment[] $comments */
/** @var Comment $commentForm */
/** @var bool $canUpdate */
$this->title = $model->title ?: 'Без названия';
$contentJson = Json::encode($model->getContentData());
$attachments = $model->attachments;
$formatSize = static function (int $bytes): string {
    if ($bytes < 1024) {
        return $bytes . ' Б';
    }
    if ($bytes < 1024 * 1024) {
        return number_format($bytes / 1024, 1, ',', ' ') . ' КБ';
    }
    if ($bytes < 1024 * 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1, ',', ' ') . ' МБ';
    }
    return number_format($bytes / 1024 / 1024 / 1024, 1, ',', ' ') . ' ГБ';
};
$editorJs = Yii::getAlias('@webroot/editor/outline-editor.js');
$editorCss = Yii::getAlias('@webroot/editor/outline-editor.css');
if (is_file($editorJs) && is_file($editorCss)) {
    EditorAsset::register($this);
}
?>
<div class="document-view-shell">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div class="min-w-0">
            <?php if ($model->collection): ?>
                <a class="small text-decoration-none" href="<?= Url::to(['/collection/view', 'id' => $model->collection_id]) ?>">
                    <?= Html::encode($model->collection->name) ?>
                </a>
            <?php endif; ?>
            <h1 class="display-6 fw-semibold text-break mt-1 mb-2"><?= Html::encode($model->title ?: 'Без названия') ?></h1>
            <div class="small text-body-secondary">
                Ревизия <?= Html::encode((string)$model->revision_number) ?> · обновлено <?= Html::encode((string)$model->updated_at) ?>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/revision/index', 'documentId' => $model->id]) ?>">История</a>
            <?php if ($canUpdate): ?>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/share/manage', 'documentId' => $model->id]) ?>">Публикация</a>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/access/document', 'id' => $model->id]) ?>">Доступ</a>
                <a class="btn btn-outline-secondary" href="<?= Url::to(['/document/update', 'id' => $model->id]) ?>">Редактировать</a>
                <a class="btn btn-primary" href="<?= Url::to(['/document/create', 'parentId' => $model->id, 'collectionId' => $model->collection_id]) ?>">Дочерний документ</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-9">
            <article class="card border-0 shadow-sm editor-card">
                <div class="card-body p-4 p-lg-5">
                    <div
                        id="outline-rich-editor"
                        class="outline-rich-editor"
                        data-document-id="<?= Html::encode($model->id) ?>"
                        data-editor-mode="read"
                        data-content-json="<?= Html::encode($contentJson) ?>"
                    ></div>
                    <div class="document-rendered-fallback" data-editor-fallback>
                        <?php if (trim((string)$model->content_text) === ''): ?>
                            <p class="text-body-secondary fst-italic">Документ пока пуст.</p>
                        <?php else: ?>
                            <?= nl2br(Html::encode((string)$model->content_text)) ?>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <?php if ($attachments): ?>
                <section class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-body border-0 pt-4 px-4">
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <h2 class="h5 mb-0">Вложения</h2>
                            <span class="badge text-bg-secondary"><?= count($attachments) ?></span>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($attachments as $attachment): ?>
                            <?php /** @var Attachment $attachment */ ?>
                            <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <a class="fw-semibold text-decoration-none text-break" href="<?= Url::to(['/attachment/download', 'id' => $attachment->id]) ?>" target="_blank" rel="noopener noreferrer">
                                        <?= Html::encode((string)$attachment->name) ?>
                                    </a>
                                    <div class="small text-body-secondary">
                                        <?= Html::encode((string)$attachment->content_type) ?> · <?= Html::encode($formatSize((int)$attachment->size)) ?>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 ms-auto">
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= Url::to(['/attachment/download', 'id' => $attachment->id, 'download' => 1]) ?>">Скачать</a>
                                    <?php if ($canUpdate): ?>
                                        <?= Html::beginForm(['/attachment/delete', 'id' => $attachment->id], 'post') ?>
                                        <?= Html::submitButton('Удалить', [
                                            'class' => 'btn btn-outline-danger btn-sm',
                                            'data-confirm' => 'Удалить вложение из хранилища?',
                                        ]) ?>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($children): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Дочерние документы</h2></div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($children as $child): ?>
                            <a class="list-group-item list-group-item-action px-4 py-3" href="<?= Url::to(['/document/view', 'id' => $child->id]) ?>">
                                <?= Html::encode($child->title ?: 'Без названия') ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?= $this->render('_comments', [
                'document' => $model,
                'comments' => $comments,
                'commentForm' => $commentForm,
                'canUpdate' => $canUpdate,
            ]) ?>
        </div>

        <aside class="col-12 col-xl-3">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h6 mb-0">Последние версии</h2></div>
                <div class="list-group list-group-flush">
                    <?php foreach ($revisions as $revision): ?>
                        <a class="list-group-item list-group-item-action px-4 py-3" href="<?= Url::to(['/revision/view', 'id' => $revision->id]) ?>">
                            <div class="fw-semibold">Версия <?= Html::encode((string)$revision->revision_number) ?></div>
                            <div class="small text-body-secondary"><?= Html::encode((string)$revision->created_at) ?></div>
                        </a>
                    <?php endforeach; ?>
                    <?php if (!$revisions): ?>
                        <div class="list-group-item px-4 py-3 text-body-secondary">Версий пока нет.</div>
                    <?php endif; ?>
                    <a class="list-group-item list-group-item-action px-4 py-3 text-primary" href="<?= Url::to(['/revision/index', 'documentId' => $model->id]) ?>">Вся история</a>
                </div>
            </div>

            <?php if ($canUpdate): ?>
                <?= Html::beginForm(['/document/archive', 'id' => $model->id], 'post', ['data-confirm' => 'Переместить документ в архив?']) ?>
                <?= Html::submitButton('Архивировать документ', ['class' => 'btn btn-outline-danger btn-sm w-100']) ?>
                <?= Html::endForm() ?>
            <?php endif; ?>
        </aside>
    </div>
</div>
