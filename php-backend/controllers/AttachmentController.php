<?php

declare(strict_types=1);
namespace app\controllers;

use app\models\Attachment;
use app\models\Document;
use app\models\User;
use app\services\AttachmentStorage;
use app\services\PermissionService;
use app\services\PublicShareService;
use RuntimeException;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;
use yii\web\UploadedFile;

final class AttachmentController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'upload' => ['POST'],
                    'delete' => ['POST'],
                    'download' => ['GET'],
                ],
            ],
        ];
    }

    public function actionUpload(string $documentId): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $user = $this->requireUser();
        $document = $this->findDocument($documentId, $user);
        if (!(new PermissionService())->canUpdateDocument($user, $document)) {
            throw new ForbiddenHttpException('Недостаточно прав для загрузки вложения.');
        }

        $file = UploadedFile::getInstanceByName('file');
        if (!$file) {
            throw new BadRequestHttpException('Файл не передан.');
        }

        try {
            $attachment = (new AttachmentStorage())->store($document, $user, $file);
        } catch (RuntimeException $error) {
            throw new BadRequestHttpException($error->getMessage(), 0, $error);
        }

        return [
            'data' => [
                'id' => $attachment->id,
                'name' => $attachment->name,
                'contentType' => $attachment->content_type,
                'size' => (int)$attachment->size,
                'url' => Url::to(['/attachment/download', 'id' => $attachment->id]),
            ],
        ];
    }

    public function actionDownload(string $id): Response
    {
        $attachment = $this->findAttachment($id);
        $document = $attachment->document;
        if (!$document) {
            throw new NotFoundHttpException('Вложение недоступно.');
        }

        $allowed = false;
        $identity = Yii::$app->user->identity;
        if ($identity instanceof User && $identity->workspace_id === $attachment->workspace_id) {
            $allowed = (new PermissionService())->canReadDocument($identity, $document);
        }
        if (!$allowed) {
            $allowed = (new PublicShareService())->isPublished($document);
        }
        if (!$allowed) {
            throw new NotFoundHttpException('Вложение не найдено.');
        }

        $storage = new AttachmentStorage();
        $path = $storage->absolutePath((string)$attachment->storage_key);
        if (!is_file($path)) {
            throw new NotFoundHttpException('Файл отсутствует в хранилище.');
        }

        $inline = Yii::$app->request->get('download') !== '1' && $storage->isInlineSafe($attachment);
        return Yii::$app->response->sendFile($path, (string)$attachment->name, [
            'mimeType' => (string)$attachment->content_type,
            'inline' => $inline,
        ]);
    }

    public function actionDelete(string $id): Response
    {
        $user = $this->requireUser();
        $attachment = $this->findAttachment($id);
        $document = $attachment->document;
        if (
            $attachment->workspace_id !== $user->workspace_id ||
            !$document ||
            (
                $attachment->user_id !== $user->id &&
                !$user->isAdmin() &&
                !(new PermissionService())->canUpdateDocument($user, $document)
            )
        ) {
            throw new ForbiddenHttpException('Недостаточно прав для удаления вложения.');
        }

        try {
            (new AttachmentStorage())->delete($attachment);
            $attachment->delete();
            Yii::$app->session->setFlash('success', 'Вложение удалено.');
        } catch (RuntimeException $error) {
            Yii::$app->session->setFlash('error', $error->getMessage());
        }

        return $this->redirect(['/document/view', 'id' => $document->id]);
    }

    private function requireUser(): User
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User) {
            throw new UnauthorizedHttpException('Требуется вход.');
        }
        return $identity;
    }

    private function findDocument(string $id, User $user): Document
    {
        $document = Document::find()
            ->with('collection')
            ->where([
                'id' => $id,
                'workspace_id' => $user->workspace_id,
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        return $document;
    }

    private function findAttachment(string $id): Attachment
    {
        $attachment = Attachment::find()
            ->with('document.collection')
            ->where(['id' => $id])
            ->one();
        if (!$attachment) {
            throw new NotFoundHttpException('Вложение не найдено.');
        }
        return $attachment;
    }
}
