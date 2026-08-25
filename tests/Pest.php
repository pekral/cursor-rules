<?php

declare(strict_types = 1);

function installerEnsureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    mkdir($directory, 0777, recursive: true);
}

function installerCreateProjectRoot(): string
{
    $root = sys_get_temp_dir() . '/cursor-rules-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($root);
    file_put_contents($root . '/composer.json', '{}');

    return $root;
}

function installerWriteFile(string $path, string $content): void
{
    $directory = dirname($path);
    installerEnsureDirectory($directory);
    file_put_contents($path, $content);
}

function installerRemoveDirectory(string $directory): void
{
    if (is_file($directory)) {
        unlink($directory);

        return;
    }

    if (!is_dir($directory)) {
        return;
    }

    /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator */
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo) {
            continue;
        }

        if ($fileInfo->isDir()) {
            rmdir($fileInfo->getPathname());

            continue;
        }

        unlink($fileInfo->getPathname());
    }

    rmdir($directory);
}

function installerSymlinkUnsupported(): bool
{
    return !function_exists('symlink') || stripos(PHP_OS, 'WIN') === 0;
}

function installerCountFiles(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY,
    );
    $count = 0;

    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $count++;
        }
    }

    return $count;
}

/**
 * Builds a Clover XML document from a list of [file path, list of [line, type, count]] tuples.
 *
 * @param array<array{0: string, 1: array<array{0: int, 1: string, 2: int}>}> $files
 */
function coverageDiffCheckBuildClover(array $files): string
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<coverage><project>";

    foreach ($files as [$path, $lines]) {
        $xml .= sprintf('<file name="%s">', htmlspecialchars($path, ENT_XML1 | ENT_QUOTES));

        foreach ($lines as [$num, $type, $count]) {
            $xml .= sprintf('<line num="%d" type="%s" count="%d"/>', $num, $type, $count);
        }

        $xml .= '</file>';
    }

    return $xml . '</project></coverage>';
}

function installerRestoreEnvAndCleanup(string|false $homeBefore, string $originalCwd, string $root): void
{
    if ($homeBefore !== false && $homeBefore !== '') {
        putenv('HOME=' . $homeBefore);
        putenv('USERPROFILE=' . $homeBefore);
    } else {
        putenv('HOME');
        putenv('USERPROFILE');
    }

    if ($originalCwd !== '') {
        chdir($originalCwd);
    }

    installerRemoveDirectory($root);
}
