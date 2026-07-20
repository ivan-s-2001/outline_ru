<?php

declare(strict_types=1);
use app\assets\EditorAsset;
use app\models\Document;
use app\models\Share;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var Share $share */
/** @var Document $root */
/** @var Document $document */
/** @var Document[] $children */
$this->title = $document->title ?: 'Без названия';
$contentJson = Json::encode($document->getContentData());
$editorJs = Yii::getAlias('@webroot/editor/outline-editor.js');
$editorCss = Yii::getAlias('@webroot/editor/outline-editor.css');
if (is_file($editorJs) && is_file($editorCss)) {
    EditorAsset::register($this);
}
?>
<div class="row justify-content-center">
    <div class="col-12 col-xl-10">
        <?php if ($document->id !== $root->id): ?>
            <a class="small text-decoration-none" href="<?= Url::to(['/public/share', 'token' => $share->token]) ?>">
                ← <?= Html::encode($root->title ?: 'Корневой документ') ?>
            </a>
        <?php endif; ?>

        <article class="card border-0 shadow-sm editor-card mt-2">
            <div class="card-body p-4 p-lg-5">
                <h1 class="display-5 fw-semibold text-break mb-4"><?= Html::encode($document->title ?: 'Без названия') ?></h1>
                <div
                    id="outline-rich-editor"
                    class="outline-rich-editor"
                    data-document-id="<?= Html::encode($document->id) ?>"
                    data-editor-mode="read"
                    data-content-json="<?= Html::encode($contentJson) ?>"
                ></div>
                <div class="document-rendered-fallback" data-editor-fallback>
                    <?php if (trim((string)$document->content_text) === ''): ?>
                        <p class="text-body-secondary fst-italic">Документ не содержит текста.</p>
                    <?php else: ?>
                        <?= nl2br(Html::encode((string)$document->content_text)) ?>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <?php if ($children): ?>
            <section class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Дочерние документы</h2></div>
                <div class="list-group list-group-flush">
                    <?php foreach ($children as $child): ?>
                        <a class="list-group-item list-group-item-action px-4 py-3" href="<?= Url::to(['/public/share', 'token' => $share->token, 'documentId' => $child->id]) ?>">
                            <?= Html::encode($child->title ?: 'Без названия') ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="small text-body-secondary text-center mt-4">
            Опубликовано из <?= Html::encode(Yii::$app->name) ?>
        </div>
    </div>
</div>
