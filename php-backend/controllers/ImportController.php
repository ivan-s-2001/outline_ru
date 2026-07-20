<?php

declare(strict_types=1);
namespace app\controllers;

use app\components\AuthenticatedController;
use app\models\Collection;
use app\models\Document;
use app\models\forms\ImportDocumentForm;
use app\services\DocumentImportService;
use app\services\DocumentService;
use app\services\PermissionService;
use RuntimeException;
use Yii;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

final class ImportController extends AuthenticatedController
{
    public function actionDocument(): Response|string
    {
        if ($this->currentUser()->role === 'viewer') {
            throw new ForbiddenHttpException('Наблюдатель не может импортировать документы.');
        }

        $form = new ImportDocumentForm();
        if ($form->load(Yii::$app->request->post())) {
            $form->file = UploadedFile::getInstance($form, 'file');
            if ($form->validate()) {
                try {
                    $content = file_get_contents((string)$form->file?->tempName);
                    if ($content === false) {
                        throw new RuntimeException('Не удалось прочитать загруженный файл.');
                    }

                    $imported = (new DocumentImportService())->import(
                        $content,
                        (string)$form->file?->extension,
                        (string)$form->file?->baseName
                    );
                    $document = new Document([
                        'title' => trim($form->title) !== '' ? trim($form->title) : $imported['title'],
                        'collection_id' => trim($form->collectionId) !== '' ? trim($form->collectionId) : null,
                        'parent_document_id' => trim($form->parentId) !== '' ? trim($form->parentId) : null,
                        'content_json' => $imported['contentJson'],
                        'content_text' => $imported['contentText'],
                    ]);
                    $this->validatePlacement($document);
                    (new DocumentService())->save($document, $this->currentUser());

                    Yii::$app->session->setFlash('success', 'Документ импортирован.');
                    return $this->redirect(['/document/view', 'id' => $document->id]);
                } catch (RuntimeException|BadRequestHttpException $error) {
                    $form->addError('file', $error->getMessage());
                }
            }
        }

        return $this->render('document', [
            'model' => $form,
            ...$this->placementOptions(),
        ]);
    }

    private function placementOptions(): array
    {
        $permissions = new PermissionService();
        $collections = Collection::find()
            ->where(['workspace_id' => $this->workspaceId(), 'archived_at' => null])
            ->orderBy(['name' => SORT_ASC])
            ->all();
        $collections = array_values(array_filter(
            $collections,
            fn (Collection $collection): bool => $permissions->canUpdateCollection($this->currentUser(), $collection)
        ));

        $parents = Document::find()
            ->with('collection')
            ->where([
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
                'deleted_at' => null,
            ])
            ->orderBy(['title' => SORT_ASC])
            ->all();
        $parents = array_values(array_filter(
            $parents,
            fn (Document $document): bool => $permissions->canUpdateDocument($this->currentUser(), $document)
        ));

        return [
            'collectionOptions' => ArrayHelper::map($collections, 'id', 'name'),
            'parentOptions' => ArrayHelper::map($parents, 'id', 'title'),
        ];
    }

    private function validatePlacement(Document $document): void
    {
        $permissions = new PermissionService();
        if ($document->collection_id) {
            $collection = Collection::findOne([
                'id' => $document->collection_id,
                'workspace_id' => $this->workspaceId(),
                'archived_at' => null,
            ]);
            if (!$collection || !$permissions->canUpdateCollection($this->currentUser(), $collection)) {
                throw new BadRequestHttpException('Выбранная коллекция недоступна для записи.');
            }
        }

        if ($document->parent_document_id) {
            $parent = Document::find()
                ->with('collection')
                ->where([
                    'id' => $document->parent_document_id,
                    'workspace_id' => $this->workspaceId(),
                    'archived_at' => null,
                    'deleted_at' => null,
                ])
                ->one();
            if (!$parent || !$permissions->canUpdateDocument($this->currentUser(), $parent)) {
                throw new BadRequestHttpException('Родительский документ недоступен для записи.');
            }
            if ($document->collection_id && $parent->collection_id !== $document->collection_id) {
                throw new BadRequestHttpException('Родитель и импортированный документ должны находиться в одной коллекции.');
            }
            $document->collection_id ??= $parent->collection_id;
        }
    }
}
