<?php

declare(strict_types=1);
use app\models\Document;
use app\models\Share;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Document $document */
/** @var Share|null $share */
$this->title = 'Публичная ссылка — ' . ($document->title ?: 'Без названия');
$publicUrl = $share ? Url::to(['/public/share', 'token' => $share->token], true) : null;
?>
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="d-flex align-items-start justify-content-between gap-3 mb-4">
            <div>
                <a class="small text-decoration-none" href="<?= Url::to(['/document/view', 'id' => $document->id]) ?>">← К документу</a>
                <h1 class="h3 mt-2 mb-1">Публичная ссылка</h1>
                <p class="text-body-secondary mb-0"><?= Html::encode($document->title ?: 'Без названия') ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if ($publicUrl): ?>
                    <label class="form-label fw-semibold" for="share-url">Адрес публикации</label>
                    <div class="input-group mb-4">
                        <input id="share-url" class="form-control" type="text" readonly value="<?= Html::encode($publicUrl) ?>">
                        <button class="btn btn-outline-secondary" type="button" data-copy-target="#share-url">Копировать</button>
                        <a class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" href="<?= Html::encode($publicUrl) ?>">Открыть</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Ссылка ещё не создана. После сохранения документ станет доступен по случайному адресу.</div>
                <?php endif; ?>

                <?= Html::beginForm(['/share/save', 'documentId' => $document->id], 'post') ?>
                <div class="form-check form-switch mb-3">
                    <?= Html::hiddenInput('isPublished', '0') ?>
                    <?= Html::checkbox('isPublished', $share ? (bool)$share->is_published : true, [
                        'class' => 'form-check-input',
                        'id' => 'share-published',
                        'value' => '1',
                    ]) ?>
                    <label class="form-check-label fw-semibold" for="share-published">Публикация включена</label>
                    <div class="form-text">При выключении существующий адрес перестанет открываться.</div>
                </div>

                <div class="form-check form-switch mb-4">
                    <?= Html::hiddenInput('includeChildDocuments', '0') ?>
                    <?= Html::checkbox('includeChildDocuments', $share ? (bool)$share->include_child_documents : false, [
                        'class' => 'form-check-input',
                        'id' => 'share-children',
                        'value' => '1',
                    ]) ?>
                    <label class="form-check-label fw-semibold" for="share-children">Публиковать дочерние документы</label>
                    <div class="form-text">Доступ получают только потомки этого документа, а не вся коллекция.</div>
                </div>

                <?= Html::submitButton($share ? 'Сохранить настройки' : 'Создать публичную ссылку', ['class' => 'btn btn-primary']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>

        <?php if ($share): ?>
            <div class="border-top mt-4 pt-4">
                <?= Html::beginForm(['/share/revoke', 'id' => $share->id], 'post') ?>
                <?= Html::submitButton('Отозвать и удалить ссылку', [
                    'class' => 'btn btn-outline-danger',
                    'data-confirm' => 'Удалить публичную ссылку? Старый адрес перестанет работать.',
                ]) ?>
                <?= Html::endForm() ?>
            </div>
        <?php endif; ?>
    </div>
</div>
