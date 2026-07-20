<?php

declare(strict_types=1);
use app\assets\EditorAsset;
use app\models\Template;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

/** @var Template $model */
/** @var string $title */
$this->title = $title;
$cancelUrl = $model->isNewRecord
    ? ['/template/index']
    : ['/template/view', 'id' => $model->id];
$contentJson = Json::encode($model->getContentData());
$editorJs = Yii::getAlias('@webroot/editor/outline-editor.js');
$editorCss = Yii::getAlias('@webroot/editor/outline-editor.css');
if (is_file($editorJs) && is_file($editorCss)) {
    EditorAsset::register($this);
}
?>
<div class="document-form-shell">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($title) ?></h1>
            <p class="text-body-secondary mb-0">Шаблон сохраняет структуру всех поддерживаемых блоков редактора.</p>
        </div>
        <a class="btn btn-outline-secondary" href="<?= Url::to($cancelUrl) ?>">Отмена</a>
    </div>

    <?php $form = ActiveForm::begin([
        'id' => 'template-form',
        'options' => ['novalidate' => true, 'data-document-form' => '1'],
        'fieldConfig' => [
            'inputOptions' => ['class' => 'form-control'],
            'labelOptions' => ['class' => 'form-label fw-semibold'],
            'errorOptions' => ['class' => 'invalid-feedback d-block'],
        ],
    ]); ?>

    <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-4">
            <?= $form->field($model, 'name')->textInput([
                'autofocus' => true,
                'maxlength' => true,
                'class' => 'form-control form-control-lg fw-semibold',
                'placeholder' => 'Например, Отчёт о тестировании',
            ]) ?>
            <?= $form->field($model, 'description')->textarea([
                'rows' => 3,
                'maxlength' => 2000,
                'placeholder' => 'Когда и для чего использовать этот шаблон',
            ]) ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm editor-card mb-3">
        <div class="card-header bg-body border-0 px-4 pt-4 pb-0">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <h2 class="h6 mb-0">Содержимое шаблона</h2>
                <span class="badge text-bg-secondary" data-editor-status>Локальный режим</span>
            </div>
        </div>
        <div class="card-body p-4">
            <div
                id="outline-rich-editor"
                class="outline-rich-editor"
                data-editor-mode="edit"
                data-content-json="<?= Html::encode($contentJson) ?>"
            ></div>

            <div data-editor-fallback>
                <?= $form->field($model, 'content_text')->textarea([
                    'rows' => 18,
                    'class' => 'form-control document-text-fallback',
                    'placeholder' => 'Содержимое шаблона…',
                ])->label(false) ?>
            </div>

            <?= Html::activeHiddenInput($model, 'content_json', [
                'value' => $contentJson,
                'data-editor-json-input' => '1',
            ]) ?>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 sticky-save-bar">
        <a class="btn btn-outline-secondary" href="<?= Url::to($cancelUrl) ?>">Отмена</a>
        <?= Html::submitButton($model->isNewRecord ? 'Создать шаблон' : 'Сохранить изменения', [
            'class' => 'btn btn-primary',
        ]) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
