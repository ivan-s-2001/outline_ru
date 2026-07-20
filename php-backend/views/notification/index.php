<?php

declare(strict_types=1);

use app\models\Notification;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ListView;

/** @var ArrayDataProvider $provider */
/** @var int $unreadCount */
$this->title = 'Уведомления';

$describe = static function (Notification $notification): string {
    $actor = $notification->actor?->getFullName() ?: 'Пользователь';
    return match ((string)$notification->type) {
        'document_mention' => $actor . ' упомянул(а) вас в документе',
        'comment_reply' => $actor . ' ответил(а) на ваш комментарий',
        'document_comment' => $actor . ' добавил(а) комментарий к вашему документу',
        default => $actor . ' отправил(а) уведомление',
    };
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Уведомления</h1>
        <p class="text-body-secondary mb-0">
            <?= $unreadCount > 0
                ? Html::encode('Непрочитанных: ' . $unreadCount)
                : 'Новых уведомлений нет.' ?>
        </p>
    </div>
    <?php if ($unreadCount > 0): ?>
        <?= Html::beginForm(['/notification/read-all'], 'post') ?>
        <?= Html::submitButton('Отметить все прочитанными', ['class' => 'btn btn-outline-primary']) ?>
        <?= Html::endForm() ?>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm">
    <?= ListView::widget([
        'dataProvider' => $provider,
        'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
        'emptyText' => '<div class="p-5 text-center text-body-secondary">Уведомлений пока нет.</div>',
        'itemView' => static function (Notification $notification) use ($describe): string {
            $documentTitle = $notification->document?->title ?: 'Документ';
            $time = (string)$notification->created_at;
            $readClass = $notification->isUnread() ? 'bg-primary-subtle' : 'bg-body';

            $open = Html::beginForm(['/notification/read', 'id' => $notification->id], 'post', [
                'class' => 'd-inline',
            ])
                . Html::submitButton('Открыть', ['class' => 'btn btn-primary btn-sm'])
                . Html::endForm();
            $archive = Html::beginForm(['/notification/archive', 'id' => $notification->id], 'post', [
                'class' => 'd-inline',
            ])
                . Html::submitButton('Скрыть', ['class' => 'btn btn-outline-secondary btn-sm'])
                . Html::endForm();

            return Html::tag('div',
                Html::tag('div',
                    Html::tag('div', Html::encode($describe($notification)), ['class' => 'fw-semibold'])
                    . Html::tag('div', Html::encode($documentTitle), ['class' => 'text-body-secondary mt-1'])
                    . Html::tag('div', Html::encode($time), ['class' => 'small text-body-secondary mt-1']),
                    ['class' => 'flex-grow-1 min-w-0']
                )
                . Html::tag('div', $open . $archive, ['class' => 'd-flex flex-wrap gap-2']),
                ['class' => 'd-flex flex-wrap align-items-center gap-3 px-4 py-3 border-bottom ' . $readClass]
            );
        },
    ]) ?>
</div>
