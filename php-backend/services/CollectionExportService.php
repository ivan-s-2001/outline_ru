<?php

declare(strict_types=1);

namespace app\services;

use app\models\Collection;
use app\models\Document;
use RuntimeException;
use ZipArchive;

final class CollectionExportService
{
    /** @param Document[] $documents
     *  @return array{path:string,name:string,mime:string}
     */
    public function create(Collection $collection, array $documents, string $format): array
    {
        $format = mb_strtolower($format);
        if (!in_array($format, ['md', 'markdown', 'html', 'json'], true)) {
            throw new RuntimeException('Формат пакетного экспорта не поддерживается.');
        }
        $extension = $format === 'markdown' ? 'md' : $format;
        $temporary = tempnam(sys_get_temp_dir(), 'outline-export-');
        if ($temporary === false) {
            throw new RuntimeException('Не удалось создать временный файл экспорта.');
        }
        $zipPath = $temporary . '.zip';
        @unlink($temporary);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Не удалось открыть ZIP-архив.');
        }

        try {
            $documentsById = [];
            foreach ($documents as $document) {
                $documentsById[(string)$document->id] = $document;
            }
            $used = [];
            $export = new DocumentExportService();
            $manifest = [];
            foreach ($documents as $document) {
                $directory = $this->directory($document, $documentsById);
                $base = $this->sanitize((string)$document->title ?: 'Без названия');
                $candidate = ($directory !== '' ? $directory . '/' : '') . $base . '.' . $extension;
                $candidate = $this->unique($candidate, (string)$document->id, $used);
                $content = match ($extension) {
                    'md' => $export->markdown($document),
                    'html' => $export->html($document),
                    'json' => $export->json($document),
                };
                if (!$zip->addFromString($candidate, $content)) {
                    throw new RuntimeException('Не удалось добавить документ в ZIP-архив.');
                }
                $manifest[] = [
                    'id' => $document->id,
                    'title' => $document->title,
                    'parentId' => $document->parent_document_id,
                    'path' => $candidate,
                    'revision' => (int)$document->revision_number,
                    'updatedAt' => $document->updated_at,
                ];
            }
            $zip->addFromString('_collection.json', json_encode([
                'id' => $collection->id,
                'name' => $collection->name,
                'format' => $extension,
                'exportedAt' => gmdate(DATE_ATOM),
                'documents' => $manifest,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } finally {
            $zip->close();
        }

        return [
            'path' => $zipPath,
            'name' => $this->sanitize((string)$collection->name ?: 'collection') . '-' . $extension . '.zip',
            'mime' => 'application/zip',
        ];
    }

    /** @param array<string,Document> $documents */
    private function directory(Document $document, array $documents): string
    {
        $segments = [];
        $current = $document;
        $seen = [];
        while ($current->parent_document_id && isset($documents[(string)$current->parent_document_id])) {
            $parentId = (string)$current->parent_document_id;
            if (isset($seen[$parentId])) {
                break;
            }
            $seen[$parentId] = true;
            $parent = $documents[$parentId];
            array_unshift($segments, $this->sanitize((string)$parent->title ?: 'Без названия'));
            $current = $parent;
            if (count($segments) >= 50) {
                break;
            }
        }
        return implode('/', $segments);
    }

    private function sanitize(string $value): string
    {
        $value = preg_replace('~[<>:"/\\|?*\x00-\x1F]+~u', '-', trim($value)) ?: 'document';
        $value = trim($value, ". \t\n\r\0\x0B");
        return mb_substr($value !== '' ? $value : 'document', 0, 100);
    }

    private function unique(string $candidate, string $id, array &$used): string
    {
        $key = mb_strtolower($candidate);
        if (!isset($used[$key])) {
            $used[$key] = true;
            return $candidate;
        }
        $dot = strrpos($candidate, '.');
        $suffix = '-' . substr(str_replace('-', '', $id), 0, 8);
        $candidate = $dot === false
            ? $candidate . $suffix
            : substr($candidate, 0, $dot) . $suffix . substr($candidate, $dot);
        $used[mb_strtolower($candidate)] = true;
        return $candidate;
    }
}
