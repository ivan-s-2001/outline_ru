<?php

declare(strict_types=1);
use app\models\Document;
use app\models\Revision;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Document $document */
/** @var Revision[] $revisions */
/** @var bool $canUpdate */
$this->title = 'История — ' . ($document->title ?: 'Без названия');
?>
<div class="document-view-shell">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
        <div>
            <a class="small text-decoration-none" href="<?= Url::to(['/document/view', 'id' => $document->id]) ?>">← К документу</a>
            <h1 class="h3 mt-2 mb-1">История версий</h1>
            <p class="text-body-secondary mb-0"><?= Html::encode($document->title ?: 'Без названия') ?></p>
        </div>
        <span class="badge text-bg-secondary"><?= count($revisions) ?> версий</span>
    </div>

    <div class="card border-0 shadow-sm">
        <?php if (!$revisions): ?>
            <div class="card-body p-5 text-center text-body-secondary">История документа пуста.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th class="ps-4">Версия</th>
                        <th>Название</th>
                        <th>Автор изменения</th>
                        <th>Дата</th>
                        <th class="text-end pe-4">Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($revisions as $revision): ?>
                        <tr>
                            <td class="ps-4 fw-semibold"><?= Html::encode((string)$revision->revision_number) ?></td>
                            <td class="text-break"><?= Html::encode($revision->title ?: 'Без названия') ?></td>
                            <td><?= Html::encode($revision->user?->getFullName() ?: 'Система') ?></td>
                            <td class="text-body-secondary"><?= Html::encode((string)$revision->created_at) ?></td>
                            <td class="pe-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a class="btn btn-outline-secondary btn-sm" href="<?= Url::to(['/revision/view', 'id' => $revision->id]) ?>">Открыть</a>
                                    <?php if ($canUpdate && (int)$revision->revision_number !== (int)$document->revision_number): ?>
                                        <?= Html::beginForm(['/revision/restore', 'id' => $revision->id], 'post') ?>
                                        <?= Html::submitButton('Восстановить', [
                                            'class' => 'btn btn-outline-primary btn-sm',
                                            'data-confirm' => 'Восстановить эту версию как новую ревизию?',
                                        ]) ?>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
