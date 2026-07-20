<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\services\CollectionExportService;
use app\services\DocumentExportService;
use app\services\DownloadFilename;
use app\services\PdfExportService;
use app\services\PermissionService;
use RuntimeException;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class ExportController extends AuthenticatedController
{
    public function actionDocument(string $id, string $format = 'md'): Response
    {
        $document = Document::find()
            ->with('collection')
            ->where([
                'id' => $id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->one();
        if (!$document) {
            throw new NotFoundHttpException('Документ не найден.');
        }
        if (!(new PermissionService())->canReadDocument($this->currentUser(), $document)) {
            throw new ForbiddenHttpException('Нет доступа к экспорту документа.');
        }

        $format = mb_strtolower($format);
        $export = new DocumentExportService();
        [$content, $extension, $mime] = match ($format) {
            'markdown', 'md' => [$export->markdown($document), 'md', 'text/markdown; charset=UTF-8'],
            'html' => [$export->html($document), 'html', 'text/html; charset=UTF-8'],
            'json' => [$export->json($document), 'json', 'application/json; charset=UTF-8'],
            'pdf' => [(new PdfExportService())->render($document), 'pdf', 'application/pdf'],
            default => throw new NotFoundHttpException('Формат экспорта не поддерживается.'),
        };

        return Yii::$app->response->sendContentAsFile(
            $content,
            DownloadFilename::forDocument($document, $extension),
            ['mimeType' => $mime, 'inline' => false]
        );
    }

    public function actionCollection(string $id, string $format = 'md'): Response
    {
        $collection = Collection::findOne([
            'id' => $id,
            'workspace_id' => $this->workspaceId(),
            'archived_at' => null,
        ]);
        if (!$collection) {
            throw new NotFoundHttpException('Коллекция не найдена.');
        }
        $permissions = new PermissionService();
        if (!$permissions->canReadCollection($this->currentUser(), $collection)) {
            throw new ForbiddenHttpException('Нет доступа к экспорту коллекции.');
        }

        $documents = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'collection_id' => $collection->id,
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();
        $documents = array_values(array_filter(
            $documents,
            fn (Document $document): bool => $permissions->canReadDocument($this->currentUser(), $document)
        ));

        try {
            $archive = (new CollectionExportService())->create($collection, $documents, $format);
        } catch (RuntimeException $error) {
            throw new ForbiddenHttpException($error->getMessage(), 0, $error);
        }
        Yii::$app->response->on(Response::EVENT_AFTER_SEND, static function () use ($archive): void {
            if (is_file($archive['path'])) {
                @unlink($archive['path']);
            }
        });
        return Yii::$app->response->sendFile(
            $archive['path'],
            $archive['name'],
            ['mimeType' => $archive['mime'], 'inline' => false]
        );
    }
}
