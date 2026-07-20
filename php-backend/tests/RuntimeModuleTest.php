<?php

declare(strict_types=1);
namespace app\tests;

use app\models\Revision;
use app\services\AttachmentStorage;
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
