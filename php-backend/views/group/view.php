<?php

declare(strict_types=1);

use app\models\Group;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Group $model */
/** @var User[] $members */
/** @var User[] $availableUsers */
$this->title = $model->name;
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1"><?= Html::encode($model->name) ?></h1>
        <p class="text-body-secondary mb-0"><?= Html::encode($model->description ?: 'Без описания') ?></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/group/update', 'id' => $model->id]) ?>">Изменить</a>
        <a class="btn btn-outline-secondary" href="<?= Url::to(['/group/index']) ?>">К списку</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body border-0 px-4 pt-4 pb-0">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Участники</h2>
                    <span class="badge text-bg-secondary"><?= count($members) ?></span>
                </div>
            </div>
            <div class="card-body p-0 pt-3">
                <?php if (!$members): ?>
                    <div class="text-center text-body-secondary py-5 px-3">В группе пока нет пользователей.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($members as $user): ?>
                            <div class="list-group-item d-flex align-items-center justify-content-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <div class="fw-semibold text-truncate"><?= Html::encode($user->getFullName()) ?></div>
                                    <div class="small text-body-secondary text-truncate">@<?= Html::encode($user->login) ?> · <?= Html::encode($user->email) ?></div>
                                </div>
                                <?= Html::beginForm(['/group/remove-member', 'id' => $model->id, 'userId' => $user->id], 'post', ['data-confirm' => 'Удалить пользователя из группы?']) ?>
                                <?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger btn-sm']) ?>
                                <?= Html::endForm() ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body p-4">
                <h2 class="h5">Добавить участника</h2>
                <?php if (!$availableUsers): ?>
                    <p class="text-body-secondary mb-0">Все активные пользователи уже входят в группу.</p>
                <?php else: ?>
                    <?= Html::beginForm(['/group/add-member', 'id' => $model->id], 'post') ?>
                    <label class="form-label fw-semibold" for="group-user-id">Пользователь</label>
                    <select class="form-select mb-3" id="group-user-id" name="userId" required>
                        <option value="">Выберите пользователя</option>
                        <?php foreach ($availableUsers as $user): ?>
                            <option value="<?= Html::encode($user->id) ?>"><?= Html::encode($user->getFullName() . ' (@' . $user->login . ')') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= Html::submitButton('Добавить в группу', ['class' => 'btn btn-primary w-100']) ?>
                    <?= Html::endForm() ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-danger-subtle">
            <div class="card-body p-4">
                <h2 class="h6 text-danger">Удаление группы</h2>
                <p class="small text-body-secondary">Индивидуальные пользователи не удаляются. Будут удалены членства и права, назначенные этой группе.</p>
                <?= Html::beginForm(['/group/delete', 'id' => $model->id], 'post', ['data-confirm' => 'Удалить группу и все назначенные ей права?']) ?>
                <?= Html::submitButton('Удалить группу', ['class' => 'btn btn-outline-danger w-100']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </aside>
</div>
