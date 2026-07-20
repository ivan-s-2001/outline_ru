<?php

declare(strict_types=1);
use app\models\forms\ImportDocumentForm;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var ImportDocumentForm $model */
/** @var array<string,string> $collectionOptions */
/** @var array<string,string> $parentOptions */
$this->title = 'Импорт документа';
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">Импорт документа</h1>
                <p class="text-body-secondary mb-0">Markdown сохраняет основные блоки и форматирование. JSON восстанавливает точную структуру ProseMirror.</p>
            </div>
            <a class="btn btn-outline-secondary" href="<?= Url::to(['/site/dashboard']) ?>">Отмена</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php $form = ActiveForm::begin([
                    'options' => ['enctype' => 'multipart/form-data'],
                    'fieldConfig' => [
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-semibold'],
                        'errorOptions' => ['class' => 'invalid-feedback d-block'],
                    ],
                ]); ?>

                <?= $form->errorSummary($model, ['class' => 'alert alert-danger']) ?>

                <?= $form->field($model, 'file')->fileInput([
                    'accept' => '.md,.markdown,.txt,.json,text/markdown,text/plain,application/json',
                    'class' => 'form-control',
                ])->hint('До 10 МБ. Поддерживаются .md, .markdown, .txt и JSON-экспорт приложения.') ?>

                <?= $form->field($model, 'title')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Необязательно: название из файла или имени файла',
                ]) ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <?= $form->field($model, 'collectionId')->dropDownList(
                            ['' => 'Без коллекции'] + $collectionOptions,
                            ['class' => 'form-select']
                        ) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'parentId')->dropDownList(
                            ['' => 'Нет родительского документа'] + $parentOptions,
                            ['class' => 'form-select']
                        ) ?>
                    </div>
                </div>

                <div class="alert alert-light border mt-3 mb-0">
                    <strong>JSON:</strong> точный round-trip блоков, таблиц, изображений и marks.<br>
                    <strong>Markdown:</strong> заголовки, абзацы, списки, цитаты, код, ссылки, жирный и курсив.<br>
                    <strong>TXT:</strong> абзацы по пустым строкам.
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a class="btn btn-outline-secondary" href="<?= Url::to(['/site/dashboard']) ?>">Отмена</a>
                    <?= Html::submitButton('Импортировать', ['class' => 'btn btn-primary']) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
