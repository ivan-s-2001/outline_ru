<?php

declare(strict_types=1);
namespace app\services;

final class DownloadFilename
{
    public static function fromTitle(string $title, string $extension): string
    {
        $name = trim($title) ?: 'document';
        $name = preg_replace('~[<>:"\\/|?*\x00-\x1F]+~u', '-', $name) ?: 'document';
        $name = trim($name, '. ');
        $name = mb_substr($name !== '' ? $name : 'document', 0, 120);

        $extension = mb_strtolower(trim($extension));
        $extension = preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'txt';
        return $name . '.' . $extension;
    }
}
