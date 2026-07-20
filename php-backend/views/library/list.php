<?php

declare(strict_types=1);

use app\models\Document;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;

/** @var string $title */
/** @var string $mode */
/** @var string $emptyText */
/** @var ArrayDataProvider $provider */
$this->title = $title;
?>
<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
    <div><h1 class="h3 mb-1"><?= Html::encode($title) ?></h1><p class="text-body-secondary mb-0"><?= Html::encode($emptyText) ?></p></div>
</div>
<div class="card border-0 shadow-sm overflow-hidden">
    <?= ListView::widget([
        'dataProvider' => $provider,
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => '<div class="p-5 text-center text-body-secondary">' . Html::encode($emptyText) . '</div>',
        'itemView' => static function (Document $document) use ($mode): string {
            $title = Html::encode($document->title ?: 'Без названия');
            $collection = $document->collection?->name
                ? Html::tag('div', Html::encode($document->collection->name), ['class' => 'small text-body-secondary'])
                : '';
            $actions = '';
            if ($mode === 'trash' || $mode === 'archive') {
                $actions .= Html::beginForm(['/library/restore', 'id' => $document->id], 'post', ['class' => 'd-inline'])
                    . Html::submitButton('Восстановить', ['class' => 'btn btn-outline-primary btn-sm'])
                    . Html::endForm();
            }
            if ($mode === 'trash') {
                $actions .= Html::beginForm(['/library/delete-forever', 'id' => $document->id], 'post', ['class' => 'd-inline'])
                    . Html::submitButton('Удалить навсегда', ['class' => 'btn btn-outline-danger btn-sm', 'data-confirm' => 'Удалить документ и вложения без возможности восстановления?'])
                    . Html::endForm();
            }
            if ($mode === 'favorites') {
                $actions .= Html::beginForm(['/library/toggle-favorite', 'id' => $document->id], 'post', ['class' => 'd-inline'])
                    . Html::submitButton('Убрать', ['class' => 'btn btn-outline-secondary btn-sm'])
                    . Html::endForm();
            }
            $open = ($document->deleted_at === null && $document->archived_at === null)
                ? Html::a('Открыть', ['/document/view', 'id' => $document->id], ['class' => 'btn btn-primary btn-sm'])
                : '';

            return Html::tag('div',
                Html::tag('div', Html::tag('div', $title, ['class' => 'fw-semibold']) . $collection . Html::tag('div', Html::encode((string)$document->updated_at), ['class' => 'small text-body-secondary mt-1']), ['class' => 'flex-grow-1 min-w-0'])
                . Html::tag('div', $open . $actions, ['class' => 'd-flex flex-wrap gap-2']),
                ['class' => 'd-flex flex-wrap align-items-center gap-3 px-4 py-3 border-bottom']
            );
        },
    ]) ?>
</div>
