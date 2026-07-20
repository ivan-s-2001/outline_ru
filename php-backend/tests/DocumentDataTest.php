<?php

declare(strict_types=1);

namespace app\tests;

use app\models\BaseRecord;
use app\models\Document;
use PHPUnit\Framework\TestCase;

final class DocumentDataTest extends TestCase
{
    public function testUuidUsesVersionFourFormat(): void
    {
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            BaseRecord::uuid()
        );
    }

    public function testDecodesStoredEditorJson(): void
    {
        $document = new Document();
        $document->content_json = '{"type":"doc","content":[{"type":"paragraph"}]}';

        self::assertSame(
            ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            $document->getContentData()
        );
    }

    public function testInvalidEditorJsonReturnsEmptyDocument(): void
    {
        $document = new Document();
        $document->content_json = '{invalid';

        self::assertSame(['type' => 'doc', 'content' => []], $document->getContentData());
    }
}
