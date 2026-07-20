<?php

declare(strict_types=1);

use app\models\CollectionPermission;
use app\models\DocumentPermission;
use app\models\Group;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var string $resourceType */
/** @var string $resourceId */
/** @var string $resourceName */
/** @var string $defaultPermission */
/** @var array<int,CollectionPermission|DocumentPermission> $permissions */
/** @var User[] $users */
/** @var Group[] $groups */
/** @var array $backRoute */
$isCollection = $resourceType === 'collection';
$this->title = 'Управление доступом';
$permissionLabels = [
    'none' => 'Нет доступа',
    'read' => 'Только чтение',
    'read_write' => 'Чтение и изменение',
];
$grantRoute = $isCollection
    ? ['/access/grant-collection', 'id' => $resourceId]
    : ['/access/grant-document', 'id' => $resourceId];
?>
<div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-4">
    <div>
        <div class="small text-body-secondary mb-1"><?= $isCollection ? 'Коллекция' : 'Документ' ?></div>
        <h1 class="h3 mb-1">Доступ: <?= Html::encode($resourceName) ?></h1>
        <p class="text-body-secondary mb-0">
            Доступ по умолчанию: <span class="fw-semibold"><?= Html::encode($permissionLabels[$defaultPermission] ?? $defaultPermission) ?></span>
        </p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= Url::to($backRoute) ?>">Назад</a>
</div>

<div class="row g-4">
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-body border-0 px-4 pt-4 pb-0">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    <h2 class="h5 mb-0">Индивидуальные разрешения</h2>
                    <span class="badge text-bg-secondary"><?= count($permissions) ?></span>
                </div>
            </div>
            <div class="card-body p-0 pt-3">
                <?php if (!$permissions): ?>
                    <div class="text-center text-body-secondary py-5 px-3">
                        Индивидуальные разрешения не назначены.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th class="ps-4">Пользователь или группа</th>
                                <th>Уровень</th>
                                <th class="text-end pe-4">Действия</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($permissions as $permission): ?>
                                <?php
                                $subjectName = $permission->user
                                    ? $permission->user->getFullName() . ' (@' . $permission->user->login . ')'
                                    : ($permission->group?->name ?? 'Удалённый субъект');
                                $subjectType = $permission->user ? 'Пользователь' : 'Группа';
                                $revokeRoute = $isCollection
                                    ? ['/access/revoke-collection', 'id' => $resourceId, 'permissionId' => $permission->id]
                                    : ['/access/revoke-document', 'id' => $resourceId, 'permissionId' => $permission->id];
                                ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-semibold"><?= Html::encode($subjectName) ?></div>
                                        <div class="small text-body-secondary"><?= Html::encode($subjectType) ?></div>
                                    </td>
                                    <td><?= Html::encode($permissionLabels[$permission->permission] ?? $permission->permission) ?></td>
                                    <td class="text-end pe-4">
                                        <?= Html::beginForm($revokeRoute, 'post', ['class' => 'd-inline', 'data-confirm' => 'Удалить это разрешение?']) ?>
                                        <?= Html::submitButton('Отозвать', ['class' => 'btn btn-outline-danger btn-sm']) ?>
                                        <?= Html::endForm() ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside class="col-12 col-xl-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="h5">Назначить доступ</h2>
                <p class="small text-body-secondary">Повторное назначение обновляет существующий уровень.</p>

                <?= Html::beginForm($grantRoute, 'post') ?>
                <label class="form-label fw-semibold" for="access-subject-type">Тип</label>
                <select class="form-select mb-3" id="access-subject-type" name="subjectType" required>
                    <option value="user">Пользователь</option>
                    <option value="group">Группа</option>
                </select>

                <label class="form-label fw-semibold" for="access-subject-id">Пользователь или группа</label>
                <select class="form-select mb-3" id="access-subject-id" name="subjectId" required>
                    <option value="">Выберите</option>
                    <optgroup label="Пользователи">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= Html::encode($user->id) ?>" data-subject-type="user">
                                <?= Html::encode($user->getFullName() . ' (@' . $user->login . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Группы">
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= Html::encode($group->id) ?>" data-subject-type="group">
                                <?= Html::encode($group->name) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>

                <label class="form-label fw-semibold" for="access-permission">Уровень доступа</label>
                <select class="form-select mb-3" id="access-permission" name="permission" required>
                    <option value="read">Только чтение</option>
                    <option value="read_write">Чтение и изменение</option>
                    <option value="none">Явный запрет</option>
                </select>

                <?= Html::submitButton('Сохранить разрешение', ['class' => 'btn btn-primary w-100']) ?>
                <?= Html::endForm() ?>
            </div>
        </div>
    </aside>
</div>
