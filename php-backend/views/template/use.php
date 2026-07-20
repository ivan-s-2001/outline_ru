<?php

declare(strict_types=1);
use app\models\Document;
use app\models\Template;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Template $template */
/** @var Document $document */
/** @var array<string,string> $collectionOptions */
/** @var array<string,string> $parentOptions */
$this->title = 'Создать из шаблона';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div>
                <a class="small text-decoration-none" href="<?= Url::to(['/template/view', 'id' => $template->id]) ?>">← К шаблону</a>
                <h1 class="h3 mt-2 mb-1">Создать документ</h1>
                <p class="text-body-secondary mb-0">Основа: <?= Html::encode($template->name) ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin([
                    'fieldConfig' => [
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-semibold'],
                        'errorOptions' => ['class' => 'invalid-feedback d-block'],
                    ],
                ]); ?>

                <?= $form->errorSummary($document, ['class' => 'alert alert-danger']) ?>

                <?= $form->field($document, 'title')->textInput([
                    'autofocus' => true,
                    'maxlength' => true,
                    'class' => 'form-control form-control-lg fw-semibold',
                ]) ?>

                <?= $form->field($document, 'collection_id')->dropDownList(
                    ['' => 'Без коллекции'] + $collectionOptions,
                    ['class' => 'form-select']
                ) ?>

                <?= $form->field($document, 'parent_document_id')->dropDownList(
                    ['' => 'Нет родительского документа'] + $parentOptions,
                    ['class' => 'form-select']
                ) ?>

                <?= Html::activeHiddenInput($document, 'content_json', [
                    'value' => json_encode($template->getContentData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]) ?>
                <?= Html::activeHiddenInput($document, 'content_text', [
                    'value' => (string)$template->content_text,
                ]) ?>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="<?= Url::to(['/template/view', 'id' => $template->id]) ?>">Отмена</a>
                    <?= Html::submitButton('Создать документ', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
