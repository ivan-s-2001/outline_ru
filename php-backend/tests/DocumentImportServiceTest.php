<?php

declare(strict_types=1);
namespace app\tests;

use app\services\DocumentImportService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DocumentImportServiceTest extends TestCase
{
    public function testImportsExportedJsonWithoutLosingStructure(): void
    {
        $source = [
            'title' => 'Точный документ',
            'document' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'table',
                    'content' => [[
                        'type' => 'table_row',
                        'content' => [[
                            'type' => 'table_cell',
                            'content' => [['type' => 'paragraph']],
                        ]],
                    ]],
                ]],
            ],
            'plainText' => 'Ячейка',
        ];

        $result = (new DocumentImportService())->import(
            json_encode($source, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'json',
            'fallback'
        );

        self::assertSame('Точный документ', $result['title']);
        self::assertSame($source['document'], $result['contentJson']);
        self::assertSame('Ячейка', $result['contentText']);
    }

    public function testImportsMarkdownBlocksAndInlineMarks(): void
    {
        $markdown = <<<'MD'
# План тестирования

## Проверки

- Первый шаг
- **Второй** шаг

Подробнее в [инструкции](/docs/guide).
MD;
        $result = (new DocumentImportService())->import($markdown, 'md', 'plan');
        $nodes = $result['contentJson']['content'];

        self::assertSame('План тестирования', $result['title']);
        self::assertSame('heading', $nodes[0]['type']);
        self::assertSame(2, $nodes[0]['attrs']['level']);
        self::assertSame('bullet_list', $nodes[1]['type']);
        self::assertSame('bold', $nodes[1]['content'][1]['content'][0]['content'][0]['marks'][0]['type']);
        self::assertSame('link', $nodes[2]['content'][1]['marks'][0]['type']);
    }

    public function testEmptyCodeBlockHasNoEmptyTextNode(): void
    {
        $result = (new DocumentImportService())->import("```php\n```", 'md', 'code');
        $code = $result['contentJson']['content'][0];

        self::assertSame('code_block', $code['type']);
        self::assertSame([], $code['content']);
    }

    public function testPlainTextCreatesParagraphs(): void
    {
        $result = (new DocumentImportService())->import("Первый\n\nВторой", 'txt', 'notes');

        self::assertSame('notes', $result['title']);
        self::assertCount(2, $result['contentJson']['content']);
        self::assertSame("Первый\n\nВторой", $result['contentText']);
    }

    public function testRejectsUnknownExtension(): void
    {
        $this->expectException(RuntimeException::class);
        (new DocumentImportService())->import('data', 'docx', 'document');
    }
}
