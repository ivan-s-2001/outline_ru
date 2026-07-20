<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Document;
use app\services\DocumentExportService;
use app\services\PermissionService;
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

        $export = new DocumentExportService();
        [$content, $extension, $mime] = match (mb_strtolower($format)) {
            'markdown', 'md' => [$export->markdown($document), 'md', 'text/markdown; charset=UTF-8'],
            'html' => [$export->html($document), 'html', 'text/html; charset=UTF-8'],
            'json' => [$export->json($document), 'json', 'application/json; charset=UTF-8'],
            default => throw new NotFoundHttpException('Формат экспорта не поддерживается.'),
        };

        return Yii::$app->response->sendContentAsFile(
            $content,
            $export->safeFilename($document, $extension),
            ['mimeType' => $mime, 'inline' => false]
        );
    }
}
