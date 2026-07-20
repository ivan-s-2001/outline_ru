<?php

declare(strict_types=1);
use app\models\ApiKey;
use app\models\forms\ApiKeyForm;
use yii\bootstrap5\ActiveForm;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
/** @var ApiKeyForm $model */
/** @var string|false|null $secret */
$this->title = 'API-ключи';
$keys = $provider->getModels();
?>
<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="mb-4">
            <h1 class="h3 mb-1">API-ключи</h1>
            <p class="text-body-secondary mb-0">Личные Bearer-токены используют те же права рабочего пространства и документов, что и ваш аккаунт.</p>
        </div>

        <?php if (is_string($secret) && $secret !== ''): ?>
            <div class="alert alert-warning shadow-sm">
                <h2 class="h6">Скопируйте секрет сейчас</h2>
                <p class="small mb-2">После закрытия страницы полный ключ больше не будет показан.</p>
                <div class="input-group">
                    <input id="api-key-secret" type="text" class="form-control font-monospace" readonly value="<?= Html::encode($secret) ?>">
                    <button type="button" class="btn btn-outline-dark" data-copy-target="#api-key-secret">Копировать</button>
                </div>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body border-0 pt-4 px-4">
                <h2 class="h5 mb-0">Созданные ключи</h2>
            </div>
            <?php if (!$keys): ?>
                <div class="card-body p-5 text-center text-body-secondary">API-ключей пока нет.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                        <tr>
                            <th class="ps-4">Ключ</th>
                            <th>Доступ</th>
                            <th>Использован</th>
                            <th>Срок</th>
                            <th class="text-end pe-4">Действие</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($keys as $key): ?>
                            <?php /** @var ApiKey $key */ ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-break"><?= Html::encode((string)$key->name) ?></div>
                                    <code><?= Html::encode((string)$key->token_prefix) ?>…</code>
                                    <div class="small text-body-secondary"><?= Html::encode((string)$key->created_at) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $key->permission === 'write' ? 'text-bg-warning' : 'text-bg-secondary' ?>">
                                        <?= $key->permission === 'write' ? 'Чтение и запись' : 'Только чтение' ?>
                                    </span>
                                </td>
                                <td class="small text-body-secondary"><?= Html::encode($key->last_used_at ? (string)$key->last_used_at : 'Никогда') ?></td>
                                <td class="small">
                                    <?php if ($key->revoked_at !== null): ?>
                                        <span class="text-danger">Отозван</span>
                                    <?php elseif (!$key->isActive()): ?>
                                        <span class="text-danger">Истёк</span>
                                    <?php else: ?>
                                        <?= Html::encode($key->expires_at ? (string)$key->expires_at : 'Без срока') ?>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end pe-4">
                                    <?php if ($key->revoked_at === null && $key->isActive()): ?>
                                        <?= Html::beginForm(['/api-key/revoke', 'id' => $key->id], 'post') ?>
                                        <?= Html::submitButton('Отозвать', [
                                            'class' => 'btn btn-outline-danger btn-sm',
                                            'data-confirm' => 'Отозвать API-ключ? Это действие нельзя отменить.',
                                        ]) ?>
                                        <?= Html::endForm() ?>
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
    </div>

    <div class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm sticky-xl-top" style="top: 5rem">
            <div class="card-body p-4">
                <h2 class="h5">Новый ключ</h2>
                <?php $form = ActiveForm::begin([
                    'action' => ['/api-key/create'],
                    'fieldConfig' => [
                        'inputOptions' => ['class' => 'form-control'],
                        'labelOptions' => ['class' => 'form-label fw-semibold'],
                        'errorOptions' => ['class' => 'invalid-feedback d-block'],
                    ],
                ]); ?>

                <?= $form->field($model, 'name')->textInput([
                    'maxlength' => true,
                    'placeholder' => 'Например, скрипт отчётов',
                ]) ?>
                <?= $form->field($model, 'permission')->dropDownList([
                    'read' => 'Только чтение',
                    'write' => 'Чтение и запись',
                ], ['class' => 'form-select'])->hint('Запись дополнительно ограничивается вашими ACL-правами.') ?>
                <?= $form->field($model, 'expiresInDays')->dropDownList([
                    30 => '30 дней',
                    90 => '90 дней',
                    365 => '1 год',
                    0 => 'Без срока',
                ], ['class' => 'form-select']) ?>

                <?= Html::submitButton('Создать ключ', ['class' => 'btn btn-primary w-100']) ?>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
