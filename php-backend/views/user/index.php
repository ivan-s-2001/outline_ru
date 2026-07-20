<?php

declare(strict_types=1);

use app\models\User;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\LinkPager;

/** @var ActiveDataProvider $provider */
$this->title = 'Пользователи';
$users = $provider->getModels();
$roleLabels = [
    'owner' => 'Владелец',
    'admin' => 'Администратор',
    'member' => 'Участник',
    'viewer' => 'Наблюдатель',
];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Пользователи</h1>
        <p class="text-body-secondary mb-0">Локальные учётные записи с логином и паролем.</p>
    </div>
    <a class="btn btn-primary" href="<?= Url::to(['/user/create']) ?>">Новый пользователь</a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr>
                <th class="ps-4">Сотрудник</th>
                <th>Логин</th>
                <th>Роль</th>
                <th>Статус</th>
                <th class="text-end pe-4">Действия</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <?php /** @var User $user */ ?>
                <tr>
                    <td class="ps-4">
                        <div class="fw-semibold"><?= Html::encode($user->getFullName()) ?></div>
                        <div class="small text-body-secondary"><?= Html::encode($user->email) ?></div>
                    </td>
                    <td>@<?= Html::encode($user->login) ?></td>
                    <td><?= Html::encode($roleLabels[$user->role] ?? $user->role) ?></td>
                    <td>
                        <span class="badge <?= $user->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                            <?= $user->status === 'active' ? 'Активен' : 'Заблокирован' ?>
                        </span>
                    </td>
                    <td class="text-end pe-4">
                        <a class="btn btn-outline-secondary btn-sm" href="<?= Url::to(['/user/update', 'id' => $user->id]) ?>">Открыть</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="5" class="text-center text-body-secondary py-5">Пользователи не найдены.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    <?= LinkPager::widget(['pagination' => $provider->pagination]) ?>
</div>
