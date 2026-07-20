<?php

declare(strict_types=1);
namespace app\tests;

use app\models\Document;
use app\models\Revision;
use app\models\Template;
use app\services\AttachmentStorage;
use app\services\DocumentExportService;
use PHPUnit\Framework\TestCase;
use Yii;

final class RuntimeModuleTest extends TestCase
{
    public function testRoutesContainDocumentRuntimeEndpoints(): void
    {
        $routes = require dirname(__DIR__) . '/config/routes.php';

        self::assertSame('search/index', $routes['GET search']);
        self::assertSame(
            'attachment/upload',
            $routes['POST documents/<documentId:[0-9a-fA-F-]{36}>/attachments']
        );
        self::assertSame(
            'revision/index',
            $routes['GET documents/<documentId:[0-9a-fA-F-]{36}>/history']
        );
        self::assertSame('public/share', $routes['GET s/<token:[0-9a-f]{64}>']);
        self::assertSame('template/index', $routes['GET templates']);
        self::assertSame(
            'template/use',
            $routes['GET,POST templates/<id:[0-9a-fA-F-]{36}>/use']
        );
        self::assertSame(
            'export/document',
            $routes['GET documents/<id:[0-9a-fA-F-]{36}>/export/<format:(?:md|markdown|html|json)>']
        );
    }

    public function testRevisionDecodesStoredEditorJson(): void
    {
        $revision = new Revision();
        $revision->content_json = '{"type":"doc","content":[{"type":"paragraph"}]}';

        self::assertSame(
            ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            $revision->getContentData()
        );
    }

    public function testRevisionRejectsInvalidEditorJson(): void
    {
        $revision = new Revision();
        $revision->content_json = '{invalid';

        self::assertSame(['type' => 'doc', 'content' => []], $revision->getContentData());
    }

    public function testTemplateDecodesStoredEditorJson(): void
    {
        $template = new Template();
        $template->content_json = '{"type":"doc","content":[{"type":"heading","attrs":{"level":2}}]}';

        self::assertSame(
            ['type' => 'doc', 'content' => [['type' => 'heading', 'attrs' => ['level' => 2]]]],
            $template->getContentData()
        );
    }

    public function testInvalidTemplateJsonReturnsEditableEmptyDocument(): void
    {
        $template = new Template();
        $template->content_json = '{invalid';

        self::assertSame(
            ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            $template->getContentData()
        );
    }

    public function testMarkdownExportPreservesHeadingsAndMarks(): void
    {
        $document = new Document([
            'title' => 'План релиза',
            'content_json' => [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'heading',
                        'attrs' => ['level' => 2],
                        'content' => [['type' => 'text', 'text' => 'Проверки']],
                    ],
                    [
                        'type' => 'paragraph',
                        'content' => [[
                            'type' => 'text',
                            'text' => 'Готово',
                            'marks' => [['type' => 'bold']],
                        ]],
                    ],
                ],
            ],
        ]);

        $markdown = (new DocumentExportService())->markdown($document);
        self::assertStringContainsString('# План релиза', $markdown);
        self::assertStringContainsString('## Проверки', $markdown);
        self::assertStringContainsString('**Готово**', $markdown);
    }

    public function testHtmlExportEscapesTextAndUnsafeLinks(): void
    {
        $document = new Document([
            'title' => '<script>alert(1)</script>',
            'content_json' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [[
                        'type' => 'text',
                        'text' => '<b>опасно</b>',
                        'marks' => [[
                            'type' => 'link',
                            'attrs' => ['href' => 'javascript:alert(1)'],
                        ]],
                    ]],
                ]],
            ],
        ]);

        $html = (new DocumentExportService())->html($document);
        self::assertStringNotContainsString('<script>', $html);
        self::assertStringNotContainsString('javascript:', $html);
        self::assertStringContainsString('&lt;b&gt;опасно&lt;/b&gt;', $html);
    }

    public function testExportFilenameIsWindowsSafe(): void
    {
        $document = new Document(['title' => 'Отчёт: тест / 20?']);
        self::assertSame(
            'Отчёт- тест - 20-.md',
            (new DocumentExportService())->safeFilename($document, 'md')
        );
    }

    public function testAttachmentStorageFallsBackToRuntimeDirectory(): void
    {
        $previous = getenv('ATTACHMENT_STORAGE_PATH');
        putenv('ATTACHMENT_STORAGE_PATH=');

        try {
            $path = (new AttachmentStorage())->absolutePath('ab/example-id');
            $expected = Yii::getAlias('@runtime/storage/attachments')
                . DIRECTORY_SEPARATOR . 'ab'
                . DIRECTORY_SEPARATOR . 'example-id';
            self::assertSame($expected, $path);
        } finally {
            if ($previous === false) {
                putenv('ATTACHMENT_STORAGE_PATH');
            } else {
                putenv('ATTACHMENT_STORAGE_PATH=' . $previous);
            }
        }
    }

    public function testAttachmentStorageRemovesTraversalSegments(): void
    {
        $previous = getenv('ATTACHMENT_STORAGE_PATH');
        putenv('ATTACHMENT_STORAGE_PATH=' . sys_get_temp_dir() . '/outline-files');

        try {
            $path = (new AttachmentStorage())->absolutePath('../ab/../file');
            self::assertStringNotContainsString('..', $path);
            self::assertStringStartsWith(sys_get_temp_dir(), $path);
        } finally {
            if ($previous === false) {
                putenv('ATTACHMENT_STORAGE_PATH');
            } else {
                putenv('ATTACHMENT_STORAGE_PATH=' . $previous);
            }
        }
    }
}
