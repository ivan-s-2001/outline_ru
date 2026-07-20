<?php

declare(strict_types=1);
namespace app\services;

use app\models\Attachment;
use app\models\BaseRecord;
use app\models\Document;
use app\models\User;
use RuntimeException;
use Yii;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

final class AttachmentStorage
{
    public function store(Document $document, User $user, UploadedFile $file): Attachment
    {
        $maximum = (int)env('ATTACHMENT_MAX_BYTES', 50 * 1024 * 1024);
        if ($file->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Файл не был загружен полностью.');
        }
        if ($file->size <= 0 || $file->size > $maximum) {
            throw new RuntimeException(sprintf('Размер файла должен быть от 1 байта до %d МБ.', (int)floor($maximum / 1024 / 1024)));
        }

        $id = BaseRecord::uuid();
        $storageKey = substr(str_replace('-', '', $id), 0, 2) . DIRECTORY_SEPARATOR . $id;
        $path = $this->absolutePath($storageKey);
        FileHelper::createDirectory(dirname($path), 0770, true);
        if (!$file->saveAs($path, false)) {
            throw new RuntimeException('Не удалось сохранить загруженный файл.');
        }

        $name = $this->safeName($file->name);
        $mime = FileHelper::getMimeType($path) ?: 'application/octet-stream';
        $attachment = new Attachment([
            'id' => $id,
            'workspace_id' => $user->workspace_id,
            'document_id' => $document->id,
            'user_id' => $user->id,
            'name' => $name,
            'content_type' => mb_substr($mime, 0, 255),
            'size' => filesize($path) ?: $file->size,
            'storage_key' => str_replace(DIRECTORY_SEPARATOR, '/', $storageKey),
        ]);

        if (!$attachment->save()) {
            @unlink($path);
            throw new RuntimeException($this->firstError($attachment->getFirstErrors()));
        }

        return $attachment;
    }

    public function absolutePath(string $storageKey): string
    {
        $normalized = str_replace(['\\', '..'], ['/', ''], $storageKey);
        $configured = trim((string)env('ATTACHMENT_STORAGE_PATH', ''));
        $base = $configured !== ''
            ? $configured
            : Yii::getAlias('@runtime/storage/attachments');
        return rtrim($base, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($normalized, '/'));
    }

    public function delete(Attachment $attachment): void
    {
        $path = $this->absolutePath((string)$attachment->storage_key);
        if (is_file($path) && !@unlink($path)) {
            throw new RuntimeException('Не удалось удалить файл из хранилища.');
        }
    }

    public function isInlineSafe(Attachment $attachment): bool
    {
        return in_array((string)$attachment->content_type, [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ], true);
    }

    private function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', $name) ?: 'file';
        $name = trim($name, ". \t\n\r\0\x0B");
        return mb_substr($name !== '' ? $name : 'file', 0, 1024);
    }

    private function firstError(array $errors): string
    {
        foreach ($errors as $error) {
            return (string)$error;
        }
        return 'Не удалось сохранить вложение.';
    }
}
