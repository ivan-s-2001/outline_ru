<?php

declare(strict_types=1);

namespace app\commands;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use yii\console\Controller;
use yii\console\ExitCode;

final class PortController extends Controller
{
    private const SOURCE_DIRECTORIES = ['app', 'server', 'shared', 'plugins'];
    private const SOURCE_EXTENSIONS = ['ts', 'tsx', 'js', 'jsx', 'json', 'md', 'css'];

    public function actionInventory(): int
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $items = [];
        $totalLines = 0;

        foreach (self::SOURCE_DIRECTORIES as $directory) {
            $sourceRoot = $repositoryRoot . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($sourceRoot)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceRoot, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $extension = strtolower($file->getExtension());
                if (!in_array($extension, self::SOURCE_EXTENSIONS, true)) {
                    continue;
                }

                $absolutePath = $file->getPathname();
                $relativePath = str_replace('\\', '/', substr($absolutePath, strlen($repositoryRoot) + 1));
                $contents = file_get_contents($absolutePath);
                if ($contents === false) {
                    throw new RuntimeException('Не удалось прочитать ' . $relativePath);
                }

                $lineCount = $contents === '' ? 0 : substr_count($contents, "\n") + 1;
                $totalLines += $lineCount;
                $items[] = [
                    'source' => $relativePath,
                    'sha256' => hash('sha256', $contents),
                    'lines' => $lineCount,
                    'module' => $this->detectModule($relativePath),
                    'target' => null,
                    'test' => null,
                    'status' => 'pending',
                ];
            }
        }

        usort($items, static fn (array $left, array $right): int => strcmp($left['source'], $right['source']));

        $payload = [
            'generatedAt' => gmdate(DATE_ATOM),
            'repository' => 'outline/outline',
            'sourceFiles' => count($items),
            'sourceLines' => $totalLines,
            'allowedStatuses' => ['pending', 'analyzed', 'ported', 'tested', 'accepted', 'not-applicable'],
            'items' => $items,
        ];

        $runtime = dirname(__DIR__) . '/runtime';
        if (!is_dir($runtime) && !mkdir($runtime, 0775, true) && !is_dir($runtime)) {
            throw new RuntimeException('Не удалось создать runtime');
        }

        $output = $runtime . '/port-inventory.json';
        file_put_contents(
            $output,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );

        $this->stdout(sprintf(
            "Учтено файлов: %d\nУчтено строк: %d\nФайл: %s\n",
            count($items),
            $totalLines,
            $output
        ));

        return ExitCode::OK;
    }

    private function detectModule(string $path): string
    {
        $rules = [
            'editor' => ['editor/', 'prosemirror', 'collaboration/', 'hocuspocus'],
            'authentication' => ['Login/', 'auth/', 'authentication', 'passport', 'jwt'],
            'documents' => ['Document', 'documents/', 'Revision', 'revisions/'],
            'collections' => ['Collection', 'collections/'],
            'comments' => ['Comment', 'comments/'],
            'attachments' => ['Attachment', 'attachments/', 'fileStorage'],
            'users-groups' => ['User', 'Group', 'users/', 'groups/'],
            'sharing' => ['Share', 'shares/', 'permission', 'policies/'],
            'search' => ['search', 'Search'],
            'notifications' => ['Notification', 'notifications/', 'emails/'],
            'imports-exports' => ['Import', 'Export', 'imports/', 'exports/'],
            'integrations' => ['Integration', 'integrations/', 'plugins/'],
            'api' => ['routes/api/', 'presenters/', 'api/'],
            'background-jobs' => ['queues/', 'tasks/', 'processors/'],
            'frontend-shell' => ['app/components/', 'app/scenes/', 'app/routes'],
        ];

        foreach ($rules as $module => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($path, $needle)) {
                    return $module;
                }
            }
        }

        return 'platform';
    }
}
