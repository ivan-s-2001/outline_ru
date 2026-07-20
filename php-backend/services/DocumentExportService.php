<?php

declare(strict_types=1);
namespace app\services;

use app\models\Document;

final class DocumentExportService
{
    public function markdown(Document $document): string
    {
        $body = trim($this->nodesToMarkdown($document->getContentData()['content'] ?? []));
        $title = trim((string)$document->title);
        return ($title !== '' ? '# ' . $title . "\n\n" : '') . $body . "\n";
    }

    public function html(Document $document): string
    {
        $title = htmlspecialchars((string)$document->title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $body = $this->nodesToHtml($document->getContentData()['content'] ?? []);
        return '<!doctype html><html lang="ru"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>' . $title . '</title>'
            . '<style>body{max-width:840px;margin:40px auto;padding:0 24px;font:16px/1.6 system-ui,sans-serif;color:#202124}'
            . 'img{max-width:100%;height:auto}table{border-collapse:collapse;width:100%}td,th{border:1px solid #ccc;padding:8px}'
            . 'pre{overflow:auto;padding:16px;background:#f5f5f5}blockquote{border-left:4px solid #ccc;margin-left:0;padding-left:16px;color:#555}</style>'
            . '</head><body><h1>' . $title . '</h1>' . $body . '</body></html>';
    }

    public function json(Document $document): string
    {
        return json_encode([
            'title' => (string)$document->title,
            'document' => $document->getContentData(),
            'plainText' => (string)$document->content_text,
            'revision' => (int)$document->revision_number,
            'updatedAt' => (string)$document->updated_at,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }

    public function safeFilename(Document $document, string $extension): string
    {
        $name = trim((string)$document->title) ?: 'document';
        $name = preg_replace('/[<>:"\\\/|?*\x00-\x1F]+/u', '-', $name) ?: 'document';
        $name = trim($name, '. ');
        $name = mb_substr($name !== '' ? $name : 'document', 0, 120);
        return $name . '.' . $extension;
    }

    private function nodesToMarkdown(array $nodes, int $depth = 0): string
    {
        $result = '';
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $type = (string)($node['type'] ?? '');
            $content = is_array($node['content'] ?? null) ? $node['content'] : [];
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

            $result .= match ($type) {
                'text' => $this->markedText((string)($node['text'] ?? ''), $node['marks'] ?? []),
                'paragraph' => $this->nodesToMarkdown($content, $depth) . "\n\n",
                'heading' => str_repeat('#', max(1, min(6, (int)($attrs['level'] ?? 1)))) . ' '
                    . trim($this->nodesToMarkdown($content, $depth)) . "\n\n",
                'blockquote' => $this->quoteMarkdown($this->nodesToMarkdown($content, $depth)) . "\n\n",
                'bullet_list' => $this->listMarkdown($content, false, $depth) . "\n",
                'ordered_list' => $this->listMarkdown($content, true, $depth) . "\n",
                'list_item', 'task_item' => $this->nodesToMarkdown($content, $depth),
                'code_block' => '```' . (string)($attrs['language'] ?? '') . "\n"
                    . rtrim($this->plainText($content)) . "\n```\n\n",
                'horizontal_rule' => "---\n\n",
                'hard_break' => "  \n",
                'image' => '![' . $this->escapeMarkdown((string)($attrs['alt'] ?? $attrs['title'] ?? '')) . ']('
                    . (string)($attrs['src'] ?? '') . ")\n\n",
                'attachment' => '[' . $this->escapeMarkdown((string)($attrs['name'] ?? 'Вложение')) . ']('
                    . (string)($attrs['href'] ?? $attrs['url'] ?? '') . ")\n\n",
                'table' => $this->tableMarkdown($content) . "\n\n",
                default => $this->nodesToMarkdown($content, $depth),
            };
        }
        return $result;
    }

    private function markedText(string $text, mixed $marks): string
    {
        $text = $this->escapeMarkdown($text);
        if (!is_array($marks)) {
            return $text;
        }
        foreach ($marks as $mark) {
            if (!is_array($mark)) {
                continue;
            }
            $type = (string)($mark['type'] ?? '');
            $attrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];
            $text = match ($type) {
                'bold', 'strong' => '**' . $text . '**',
                'italic', 'em' => '*' . $text . '*',
                'strike', 'strikethrough' => '~~' . $text . '~~',
                'code' => '`' . str_replace('`', '\\`', $text) . '`',
                'link' => '[' . $text . '](' . (string)($attrs['href'] ?? '') . ')',
                default => $text,
            };
        }
        return $text;
    }

    private function listMarkdown(array $items, bool $ordered, int $depth): string
    {
        $lines = [];
        foreach (array_values($items) as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $body = trim($this->nodesToMarkdown($item['content'] ?? [], $depth + 1));
            $body = preg_replace('/\n{2,}/', "\n", $body) ?? $body;
            $prefix = $ordered ? ($index + 1) . '. ' : '- ';
            $indent = str_repeat('  ', $depth);
            $parts = explode("\n", $body);
            $first = array_shift($parts) ?? '';
            $lines[] = $indent . $prefix . $first;
            foreach ($parts as $part) {
                $lines[] = $indent . '  ' . $part;
            }
        }
        return implode("\n", $lines) . "\n";
    }

    private function quoteMarkdown(string $value): string
    {
        $lines = preg_split('/\R/u', trim($value)) ?: [];
        return implode("\n", array_map(static fn (string $line): string => '> ' . $line, $lines));
    }

    private function tableMarkdown(array $rows): string
    {
        $matrix = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $cells = [];
            foreach (($row['content'] ?? []) as $cell) {
                $cells[] = str_replace('|', '\\|', trim($this->plainText($cell['content'] ?? [])));
            }
            if ($cells) {
                $matrix[] = $cells;
            }
        }
        if (!$matrix) {
            return '';
        }
        $width = max(array_map('count', $matrix));
        foreach ($matrix as &$row) {
            $row = array_pad($row, $width, '');
        }
        unset($row);
        $lines = ['| ' . implode(' | ', $matrix[0]) . ' |'];
        $lines[] = '| ' . implode(' | ', array_fill(0, $width, '---')) . ' |';
        foreach (array_slice($matrix, 1) as $row) {
            $lines[] = '| ' . implode(' | ', $row) . ' |';
        }
        return implode("\n", $lines);
    }

    private function nodesToHtml(array $nodes): string
    {
        $result = '';
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            $type = (string)($node['type'] ?? '');
            $content = is_array($node['content'] ?? null) ? $node['content'] : [];
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];
            $inner = $this->nodesToHtml($content);

            $result .= match ($type) {
                'text' => $this->markedHtml((string)($node['text'] ?? ''), $node['marks'] ?? []),
                'paragraph' => '<p>' . $inner . '</p>',
                'heading' => '<h' . max(1, min(6, (int)($attrs['level'] ?? 1))) . '>' . $inner . '</h'
                    . max(1, min(6, (int)($attrs['level'] ?? 1))) . '>',
                'blockquote' => '<blockquote>' . $inner . '</blockquote>',
                'bullet_list' => '<ul>' . $inner . '</ul>',
                'ordered_list' => '<ol>' . $inner . '</ol>',
                'list_item', 'task_item' => '<li>' . $inner . '</li>',
                'code_block' => '<pre><code>' . htmlspecialchars($this->plainText($content), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>',
                'horizontal_rule' => '<hr>',
                'hard_break' => '<br>',
                'image' => '<img src="' . $this->safeUrl((string)($attrs['src'] ?? '')) . '" alt="'
                    . htmlspecialchars((string)($attrs['alt'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">',
                'attachment' => '<p><a href="' . $this->safeUrl((string)($attrs['href'] ?? $attrs['url'] ?? '')) . '">'
                    . htmlspecialchars((string)($attrs['name'] ?? 'Вложение'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></p>',
                'table' => '<table><tbody>' . $inner . '</tbody></table>',
                'table_row' => '<tr>' . $inner . '</tr>',
                'table_cell', 'table_header' => '<td>' . $inner . '</td>',
                default => $inner,
            };
        }
        return $result;
    }

    private function markedHtml(string $text, mixed $marks): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        if (!is_array($marks)) {
            return $text;
        }
        foreach ($marks as $mark) {
            if (!is_array($mark)) {
                continue;
            }
            $type = (string)($mark['type'] ?? '');
            $attrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];
            $text = match ($type) {
                'bold', 'strong' => '<strong>' . $text . '</strong>',
                'italic', 'em' => '<em>' . $text . '</em>',
                'underline' => '<u>' . $text . '</u>',
                'strike', 'strikethrough' => '<s>' . $text . '</s>',
                'code' => '<code>' . $text . '</code>',
                'link' => '<a href="' . $this->safeUrl((string)($attrs['href'] ?? '')) . '">' . $text . '</a>',
                default => $text,
            };
        }
        return $text;
    }

    private function plainText(array $nodes): string
    {
        $result = '';
        foreach ($nodes as $node) {
            if (!is_array($node)) {
                continue;
            }
            if (($node['type'] ?? '') === 'text') {
                $result .= (string)($node['text'] ?? '');
            } elseif (($node['type'] ?? '') === 'hard_break') {
                $result .= "\n";
            } else {
                $result .= $this->plainText(is_array($node['content'] ?? null) ? $node['content'] : []);
            }
        }
        return $result;
    }

    private function escapeMarkdown(string $value): string
    {
        return preg_replace('/([\\`*_{}\[\]()#+\-.!|>])/u', '\\$1', $value) ?? $value;
    }

    private function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/^(?:javascript|data):/i', $url)) {
            return '';
        }
        return htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
