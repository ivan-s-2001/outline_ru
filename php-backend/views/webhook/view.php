<?php

declare(strict_types=1);

use app\models\Webhook;
use app\models\WebhookDelivery;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Webhook $model */
/** @var ActiveDataProvider $provider */
$this->title = $model->name;
$secret = Yii::$app->session->getFlash('webhookSecret');
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div><a class="small text-decoration-none" href="<?= Url::to(['/webhook/index']) ?>">Webhooks</a><h1 class="h3 mt-1 mb-1"><?= Html::encode($model->name) ?></h1><div class="text-body-secondary text-break"><?= Html::encode((string)$model->url) ?></div></div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="<?= Url::to(['/webhook/create', 'id' => $model->id]) ?>">Изменить</a>
        <?= Html::beginForm(['/webhook/test', 'id' => $model->id], 'post') ?><?= Html::submitButton('Тест', ['class' => 'btn btn-primary']) ?><?= Html::endForm() ?>
    </div>
</div>
<?php if (is_string($secret) && $secret !== ''): ?>
    <div class="alert alert-warning"><div class="fw-semibold mb-2">Сохраните секрет. Повторно он не показывается.</div><code class="user-select-all text-break"><?= Html::encode($secret) ?></code></div>
<?php endif; ?>
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-7"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><dl class="row mb-0"><dt class="col-sm-4">События</dt><dd class="col-sm-8"><?= Html::encode(implode(', ', $model->getEventsValue())) ?></dd><dt class="col-sm-4">Статус</dt><dd class="col-sm-8"><?= $model->is_active ? 'Активен' : 'Отключён' ?></dd><dt class="col-sm-4">Подпись</dt><dd class="col-sm-8"><code>X-Outline-Signature: sha256=…</code></dd></dl></div></div></div>
    <div class="col-12 col-lg-5"><div class="card border-0 shadow-sm h-100"><div class="card-body p-4"><h2 class="h6">Секрет</h2><p class="small text-body-secondary">Ротация немедленно прекращает проверку подписей старым секретом.</p><div class="d-flex flex-wrap gap-2"><?= Html::beginForm(['/webhook/rotate', 'id' => $model->id], 'post') ?><?= Html::submitButton('Сменить секрет', ['class' => 'btn btn-outline-warning', 'data-confirm' => 'Сменить секрет webhook?']) ?><?= Html::endForm() ?><?= Html::beginForm(['/webhook/delete', 'id' => $model->id], 'post') ?><?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger', 'data-confirm' => 'Удалить webhook и историю доставок?']) ?><?= Html::endForm() ?></div></div></div></div>
</div>
<div class="card border-0 shadow-sm overflow-hidden"><div class="card-header bg-body border-0 pt-4 px-4"><h2 class="h5 mb-0">Доставки</h2></div>
<?= GridView::widget([
    'dataProvider' => $provider,
    'tableOptions' => ['class' => 'table table-hover align-middle mb-0'],
    'layout' => "{items}\n<div class=\"card-footer bg-body border-0\">{pager}</div>",
    'emptyText' => 'Доставок пока нет.',
    'columns' => [
        'event_name',
        'status',
        'attempts',
        'last_status_code',
        ['attribute' => 'last_error', 'value' => static fn (WebhookDelivery $d): string => mb_substr((string)$d->last_error, 0, 180)],
        'created_at',
        'delivered_at',
    ],
]) ?>
</div>
