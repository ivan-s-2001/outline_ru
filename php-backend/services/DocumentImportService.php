<?php

declare(strict_types=1);
namespace app\services;

use JsonException;
use RuntimeException;

final class DocumentImportService
{
    public function import(string $content, string $extension, string $fallbackTitle): array
    {
        if (strlen($content) > (int)env('IMPORT_MAX_BYTES', 10 * 1024 * 1024)) {
            throw new RuntimeException('Файл импорта превышает допустимый размер.');
        }

        $extension = mb_strtolower(ltrim(trim($extension), '.'));
        return match ($extension) {
            'json' => $this->fromJson($content, $fallbackTitle),
            'md', 'markdown' => $this->fromMarkdown($content, $fallbackTitle),
            'txt', 'text' => $this->fromText($content, $fallbackTitle),
            default => throw new RuntimeException('Поддерживаются файлы Markdown, TXT и JSON.'),
        };
    }

    private function fromJson(string $content, string $fallbackTitle): array
    {
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('JSON-файл повреждён: ' . $error->getMessage(), 0, $error);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('JSON должен содержать объект документа.');
        }

        $document = isset($decoded['document']) && is_array($decoded['document'])
            ? $decoded['document']
            : $decoded;
        if (($document['type'] ?? null) !== 'doc' || !is_array($document['content'] ?? [])) {
            throw new RuntimeException('JSON не содержит валидный ProseMirror-документ.');
        }

        $title = trim((string)($decoded['title'] ?? $fallbackTitle));
        $plainText = isset($decoded['plainText']) && is_string($decoded['plainText'])
            ? $decoded['plainText']
            : $this->plainText($document['content']);

        return [
            'title' => $title !== '' ? $title : 'Импортированный документ',
            'contentJson' => $document,
            'contentText' => trim($plainText),
        ];
    }

    private function fromText(string $content, string $fallbackTitle): array
    {
        $content = $this->normalize($content);
        $blocks = preg_split('/\n{2,}/u', trim($content)) ?: [];
        $nodes = [];
        foreach ($blocks as $block) {
            $text = trim($block);
            if ($text !== '') {
                $nodes[] = $this->paragraph($text);
            }
        }

        return [
            'title' => trim($fallbackTitle) ?: 'Импортированный документ',
            'contentJson' => ['type' => 'doc', 'content' => $nodes ?: [$this->paragraph('')]],
            'contentText' => trim($content),
        ];
    }

    private function fromMarkdown(string $content, string $fallbackTitle): array
    {
        $content = $this->normalize($content);
        $lines = explode("\n", $content);
        $nodes = [];
        $title = trim($fallbackTitle);
        $index = 0;

        while ($index < count($lines)) {
            $line = rtrim($lines[$index]);
            if (trim($line) === '') {
                $index++;
                continue;
            }

            if (preg_match('/^```\s*([A-Za-z0-9_+-]*)\s*$/', trim($line), $match)) {
                $language = $match[1] ?? '';
                $index++;
                $code = [];
                while ($index < count($lines) && !preg_match('/^```\s*$/', trim($lines[$index]))) {
                    $code[] = $lines[$index++];
                }
                if ($index < count($lines)) {
                    $index++;
                }
                $nodes[] = [
                    'type' => 'code_block',
                    'attrs' => ['language' => $language !== '' ? $language : null],
                    'content' => [['type' => 'text', 'text' => implode("\n", $code)]],
                ];
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/u', $line, $match)) {
                $level = strlen($match[1]);
                $text = trim($match[2]);
                if ($level === 1 && $title === trim($fallbackTitle)) {
                    $title = $text;
                } else {
                    $nodes[] = [
                        'type' => 'heading',
                        'attrs' => ['level' => $level],
                        'content' => $this->inline($text),
                    ];
                }
                $index++;
                continue;
            }

            if (preg_match('/^\s*(?:---+|___+|\*\*\*+)\s*$/', $line)) {
                $nodes[] = ['type' => 'horizontal_rule'];
                $index++;
                continue;
            }

            if (preg_match('/^\s*>\s?(.*)$/u', $line)) {
                $quoted = [];
                while ($index < count($lines) && preg_match('/^\s*>\s?(.*)$/u', $lines[$index], $match)) {
                    $quoted[] = $match[1];
                    $index++;
                }
                $nodes[] = [
                    'type' => 'blockquote',
                    'content' => [$this->paragraph(implode("\n", $quoted))],
                ];
                continue;
            }

            if (preg_match('/^\s*[-+*]\s+(.+)$/u', $line)) {
                $items = [];
                while ($index < count($lines) && preg_match('/^\s*[-+*]\s+(.+)$/u', $lines[$index], $match)) {
                    $items[] = [
                        'type' => 'list_item',
                        'content' => [$this->paragraph($match[1])],
                    ];
                    $index++;
                }
                $nodes[] = ['type' => 'bullet_list', 'content' => $items];
                continue;
            }

            if (preg_match('/^\s*\d+[.)]\s+(.+)$/u', $line)) {
                $items = [];
                while ($index < count($lines) && preg_match('/^\s*\d+[.)]\s+(.+)$/u', $lines[$index], $match)) {
                    $items[] = [
                        'type' => 'list_item',
                        'content' => [$this->paragraph($match[1])],
                    ];
                    $index++;
                }
                $nodes[] = ['type' => 'ordered_list', 'attrs' => ['order' => 1], 'content' => $items];
                continue;
            }

            $paragraph = [$line];
            $index++;
            while ($index < count($lines) && trim($lines[$index]) !== '' && !$this->startsBlock($lines[$index])) {
                $paragraph[] = rtrim($lines[$index++]);
            }
            $nodes[] = $this->paragraph(implode("\n", $paragraph));
        }

        $document = ['type' => 'doc', 'content' => $nodes ?: [$this->paragraph('')]];
        return [
            'title' => $title !== '' ? $title : 'Импортированный документ',
            'contentJson' => $document,
            'contentText' => $this->plainText($document['content']),
        ];
    }

    private function paragraph(string $text): array
    {
        return ['type' => 'paragraph', 'content' => $this->inline($text)];
    }

    private function inline(string $text): array
    {
        if ($text === '') {
            return [];
        }
        $parts = preg_split(
            '/(\[[^\]]+\]\([^)]+\)|\*\*[^*]+\*\*|`[^`]+`|\*[^*]+\*)/u',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        ) ?: [$text];
        $nodes = [];
        foreach ($parts as $part) {
            $marks = [];
            $value = $part;
            if (preg_match('/^\[([^\]]+)\]\(([^)]+)\)$/u', $part, $match)) {
                $value = $match[1];
                $marks[] = ['type' => 'link', 'attrs' => ['href' => trim($match[2])]];
            } elseif (preg_match('/^\*\*(.+)\*\*$/us', $part, $match)) {
                $value = $match[1];
                $marks[] = ['type' => 'bold'];
            } elseif (preg_match('/^`(.+)`$/us', $part, $match)) {
                $value = $match[1];
                $marks[] = ['type' => 'code'];
            } elseif (preg_match('/^\*(.+)\*$/us', $part, $match)) {
                $value = $match[1];
                $marks[] = ['type' => 'italic'];
            }
            $node = ['type' => 'text', 'text' => $value];
            if ($marks) {
                $node['marks'] = $marks;
            }
            $nodes[] = $node;
        }
        return $nodes;
    }

    private function startsBlock(string $line): bool
    {
        $line = rtrim($line);
        return (bool)preg_match(
            '/^(?:```|#{1,6}\s+|\s*>|\s*[-+*]\s+|\s*\d+[.)]\s+|\s*(?:---+|___+|\*\*\*+)\s*$)/u',
            $line
        );
    }

    private function plainText(array $nodes): string
    {
        $parts = [];
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['type'] ?? '') === 'text') {
                $parts[] = (string)($node['text'] ?? '');
                continue;
            }
            $child = is_array($node['content'] ?? null) ? $this->plainText($node['content']) : '';
            if ($child !== '') {
                $parts[] = $child;
            }
        }
        return trim(implode("\n", $parts));
    }

    private function normalize(string $content): string
    {
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        return str_replace(["\r\n", "\r"], "\n", $content);
    }
}
