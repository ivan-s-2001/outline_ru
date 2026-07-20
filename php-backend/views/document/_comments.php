<?php

declare(strict_types=1);

use app\models\Comment;
use app\models\Document;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var Document $document */
/** @var Comment[] $comments */
/** @var Comment $commentForm */
/** @var bool $canUpdate */
/** @var User $currentUser */
$currentUser = Yii::$app->user->identity;
?>
<section class="card border-0 shadow-sm mt-4" id="comments">
    <div class="card-header bg-body border-0 px-4 pt-4 pb-0">
        <div class="d-flex align-items-center justify-content-between gap-3">
            <h2 class="h5 mb-0">Комментарии</h2>
            <span class="badge text-bg-secondary"><?= count($comments) ?></span>
        </div>
    </div>
    <div class="card-body p-4">
        <?= Html::beginForm(['/comment/create', 'documentId' => $document->id], 'post', [
            'class' => 'mb-4',
            'data-comment-form' => 'new',
        ]) ?>
        <label class="form-label fw-semibold" for="new-comment-text">Новое обсуждение</label>
        <?= Html::textarea('Comment[text]', '', [
            'id' => 'new-comment-text',
            'class' => 'form-control',
            'rows' => 3,
            'maxlength' => 20000,
            'required' => true,
            'placeholder' => 'Напишите комментарий…',
        ]) ?>
        <div class="d-flex justify-content-end mt-2">
            <?= Html::submitButton('Добавить комментарий', ['class' => 'btn btn-primary']) ?>
        </div>
        <?= Html::endForm() ?>

        <?php if (!$comments): ?>
            <div class="text-center border rounded-3 py-5 px-3 text-body-secondary">
                Комментариев пока нет.
            </div>
        <?php else: ?>
            <div class="vstack gap-3">
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $author = $comment->user;
                    $isOwner = $comment->user_id === $currentUser->id;
                    $canManage = $isOwner || $currentUser->isAdmin();
                    $canResolve = $canManage || $canUpdate;
                    $collapseId = 'edit-comment-' . str_replace('-', '', (string)$comment->id);
                    ?>
                    <article class="comment-thread border rounded-3 p-3 p-md-4 <?= $comment->isResolved() ? 'comment-resolved' : '' ?>">
                        <header class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                            <div>
                                <div class="fw-semibold"><?= Html::encode($author?->getFullName() ?: 'Удалённый пользователь') ?></div>
                                <div class="small text-body-secondary"><?= Html::encode((string)$comment->created_at) ?></div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($comment->isResolved()): ?>
                                    <span class="badge text-bg-success">Завершено</span>
                                <?php endif; ?>
                                <?php if ($canManage): ?>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#<?= Html::encode($collapseId) ?>" aria-expanded="false" aria-controls="<?= Html::encode($collapseId) ?>">
                                        Изменить
                                    </button>
                                <?php endif; ?>
                            </div>
                        </header>

                        <div class="comment-text mb-3"><?= nl2br(Html::encode((string)$comment->text)) ?></div>

                        <?php if ($canManage): ?>
                            <div class="collapse mb-3" id="<?= Html::encode($collapseId) ?>">
                                <div class="card card-body bg-body-tertiary border-0">
                                    <?= Html::beginForm(['/comment/update', 'id' => $comment->id], 'post') ?>
                                    <?= Html::textarea('Comment[text]', (string)$comment->text, [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'maxlength' => 20000,
                                        'required' => true,
                                    ]) ?>
                                    <div class="d-flex justify-content-end mt-2">
                                        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary btn-sm']) ?>
                                    </div>
                                    <?= Html::endForm() ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($comment->replies): ?>
                            <div class="vstack gap-2 border-start ps-3 mb-3">
                                <?php foreach ($comment->replies as $reply): ?>
                                    <?php
                                    $replyOwner = $reply->user_id === $currentUser->id;
                                    $replyManage = $replyOwner || $currentUser->isAdmin();
                                    $replyCollapseId = 'edit-reply-' . str_replace('-', '', (string)$reply->id);
                                    ?>
                                    <div class="comment-reply bg-body-tertiary rounded-3 p-3">
                                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                                            <div>
                                                <span class="fw-semibold"><?= Html::encode($reply->user?->getFullName() ?: 'Удалённый пользователь') ?></span>
                                                <span class="small text-body-secondary ms-1"><?= Html::encode((string)$reply->created_at) ?></span>
                                            </div>
                                            <?php if ($replyManage): ?>
                                                <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#<?= Html::encode($replyCollapseId) ?>">
                                                    Изменить
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="comment-text"><?= nl2br(Html::encode((string)$reply->text)) ?></div>
                                        <?php if ($replyManage): ?>
                                            <div class="collapse mt-2" id="<?= Html::encode($replyCollapseId) ?>">
                                                <?= Html::beginForm(['/comment/update', 'id' => $reply->id], 'post') ?>
                                                <?= Html::textarea('Comment[text]', (string)$reply->text, [
                                                    'class' => 'form-control form-control-sm',
                                                    'rows' => 2,
                                                    'maxlength' => 20000,
                                                    'required' => true,
                                                ]) ?>
                                                <div class="d-flex justify-content-end gap-2 mt-2">
                                                    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary btn-sm']) ?>
                                                    <?= Html::submitButton('Удалить', [
                                                        'class' => 'btn btn-outline-danger btn-sm',
                                                        'formaction' => Url::to(['/comment/delete', 'id' => $reply->id]),
                                                        'data-confirm' => 'Удалить ответ?',
                                                    ]) ?>
                                                </div>
                                                <?= Html::endForm() ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!$comment->isResolved()): ?>
                            <?= Html::beginForm(['/comment/create', 'documentId' => $document->id, 'parentId' => $comment->id], 'post', ['class' => 'mb-3']) ?>
                            <div class="input-group">
                                <?= Html::textInput('Comment[text]', '', [
                                    'class' => 'form-control',
                                    'maxlength' => 20000,
                                    'required' => true,
                                    'placeholder' => 'Ответить…',
                                    'aria-label' => 'Ответить на комментарий',
                                ]) ?>
                                <?= Html::submitButton('Ответить', ['class' => 'btn btn-outline-primary']) ?>
                            </div>
                            <?= Html::endForm() ?>
                        <?php endif; ?>

                        <footer class="d-flex flex-wrap gap-2">
                            <?php if ($canResolve): ?>
                                <?= Html::beginForm(['/comment/resolve', 'id' => $comment->id], 'post') ?>
                                <?= Html::submitButton($comment->isResolved() ? 'Возобновить' : 'Завершить обсуждение', ['class' => 'btn btn-outline-success btn-sm']) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                            <?php if ($canManage): ?>
                                <?= Html::beginForm(['/comment/delete', 'id' => $comment->id], 'post', ['data-confirm' => 'Удалить обсуждение вместе с ответами?']) ?>
                                <?= Html::submitButton('Удалить', ['class' => 'btn btn-outline-danger btn-sm']) ?>
                                <?= Html::endForm() ?>
                            <?php endif; ?>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
