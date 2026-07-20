<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$directories = ['assets', 'commands', 'components', 'config', 'controllers', 'migrations', 'models', 'services', 'tests', 'views', 'web'];
$failed = false;
$count = 0;

foreach ($directories as $directory) {
    $path = $root . DIRECTORY_SEPARATOR . $directory;
    if (!is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }

        $count++;
        $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname());
        exec($command, $output, $exitCode);
        echo implode(PHP_EOL, $output) . PHP_EOL;
        $output = [];
        if ($exitCode !== 0) {
            $failed = true;
        }
    }
}

echo sprintf("Проверено PHP-файлов: %d\n", $count);
exit($failed ? 1 : 0);
