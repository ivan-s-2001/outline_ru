<?php

declare(strict_types=1);
use app\models\Event;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
/** @var string $queryText */
/** @var string $userId */
/** @var array<string,string> $userOptions */
$this->title = 'Журнал аудита';
$events = $provider->getModels();
$labels = [
    'api_key.created' => 'Создан API-ключ',
    'api_key.revoked' => 'Отозван API-ключ',
    'document.created' => 'Создан документ',
    'document.updated' => 'Изменён документ',
    'document.archived' => 'Архивирован документ',
    'attachment.created' => 'Загружено вложение',
    'attachment.deleted' => 'Удалено вложение',
    'share.created' => 'Создана публичная ссылка',
    'share.revoked' => 'Отозвана публичная ссылка',
];
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Журнал аудита</h1>
        <p class="text-body-secondary mb-0">События безопасности и изменения объектов рабочего пространства.</p>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
        <?= Html::beginForm(['/audit/index'], 'get', ['class' => 'row g-3 align-items-end']) ?>
        <div class="col-12 col-lg-6">
            <label class="form-label fw-semibold" for="audit-query">Событие, модель, IP или данные</label>
            <input id="audit-query" class="form-control" type="search" name="q" value="<?= Html::encode($queryText) ?>" placeholder="api_key, UUID, 127.0.0.1">
        </div>
        <div class="col-12 col-lg-4">
            <label class="form-label fw-semibold" for="audit-user">Пользователь</label>
            <?= Html::dropDownList('userId', $userId, ['' => 'Все пользователи'] + $userOptions, ['id' => 'audit-user', 'class' => 'form-select']) ?>
        </div>
        <div class="col-12 col-lg-2 d-grid">
            <?= Html::submitButton('Фильтровать', ['class' => 'btn btn-primary']) ?>
        </div>
        <?= Html::endForm() ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <?php if (!$events): ?>
        <div class="card-body p-5 text-center text-body-secondary">События не найдены.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                <tr>
                    <th class="ps-4">Время</th>
                    <th>Событие</th>
                    <th>Пользователь</th>
                    <th>Объект</th>
                    <th>IP</th>
                    <th class="pe-4">Данные</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <?php /** @var Event $event */ ?>
                    <?php $data = $event->getDataArray(); ?>
                    <tr>
                        <td class="ps-4 text-nowrap small"><?= Html::encode((string)$event->created_at) ?></td>
                        <td>
                            <div class="fw-semibold"><?= Html::encode($labels[$event->name] ?? (string)$event->name) ?></div>
                            <code class="small"><?= Html::encode((string)$event->name) ?></code>
                        </td>
                        <td><?= Html::encode($event->user?->getFullName() ?: 'Система') ?></td>
                        <td><code class="small"><?= Html::encode($event->model_id ?: '—') ?></code></td>
                        <td class="small"><?= Html::encode($event->ip ?: '—') ?></td>
                        <td class="pe-4">
                            <?php if ($data): ?>
                                <details>
                                    <summary class="small text-primary">Показать</summary>
                                    <pre class="small bg-body-tertiary border rounded p-2 mt-2 mb-0"><code><?= Html::encode(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code></pre>
                                </details>
                            <?php else: ?>
                                <span class="text-body-secondary">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<div class="mt-3"><?= LinkPager::widget(['pagination' => $provider->pagination]) ?></div>
