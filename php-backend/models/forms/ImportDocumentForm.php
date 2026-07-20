<?php

declare(strict_types=1);
namespace app\models\forms;

use yii\base\Model;
use yii\web\UploadedFile;

final class ImportDocumentForm extends Model
{
    public ?UploadedFile $file = null;
    public string $title = '';
    public string $collectionId = '';
    public string $parentId = '';

    public function rules(): array
    {
        return [
            [['file'], 'required'],
            [['file'], 'file',
                'extensions' => ['md', 'markdown', 'txt', 'json'],
                'checkExtensionByMimeType' => false,
                'maxSize' => (int)env('IMPORT_MAX_BYTES', 10 * 1024 * 1024),
                'tooBig' => 'Файл слишком большой.',
                'wrongExtension' => 'Поддерживаются Markdown, TXT и JSON.',
            ],
            [['title'], 'string', 'max' => 1024],
            [['collectionId', 'parentId'], 'string', 'max' => 36],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'file' => 'Файл',
            'title' => 'Название документа',
            'collectionId' => 'Коллекция',
            'parentId' => 'Родительский документ',
        ];
    }
}
