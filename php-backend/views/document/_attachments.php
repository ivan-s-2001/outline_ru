<?php

declare(strict_types=1);
use app\models\Attachment;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Attachment[] $attachments */
/** @var bool $canUpdate */
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
?>
<?php if ($attachments): ?>
    <section class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-body border-0 pt-4 px-4 d-flex align-items-center justify-content-between gap-3">
            <h2 class="h5 mb-0">Вложения</h2>
            <span class="badge text-bg-secondary"><?= count($attachments) ?></span>
        </div>
        <div class="list-group list-group-flush">
            <?php foreach ($attachments as $attachment): ?>
                <div class="list-group-item d-flex flex-wrap align-items-center justify-content-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <a class="fw-semibold text-decoration-none text-break" href="<?= Url::to(['/attachment/download', 'id' => $attachment->id]) ?>" target="_blank" rel="noopener noreferrer"><?= Html::encode((string)$attachment->name) ?></a>
                        <div class="small text-body-secondary"><?= Html::encode((string)$attachment->content_type) ?> · <?= Html::encode($formatSize((int)$attachment->size)) ?></div>
                    </div>
                    <div class="d-flex gap-2 ms-auto">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= Url::to(['/attachment/download', 'id' => $attachment->id, 'download' => 1]) ?>">Скачать</a>
                        <?php if ($canUpdate): ?>
                            <?= Html::beginForm(['/attachment/delete', 'id' => $attachment->id], 'post') ?>
                            <?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger btn-sm', 'data-confirm' => 'Удалить вложение из хранилища?']) ?>
                            <?= Html::endForm() ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
