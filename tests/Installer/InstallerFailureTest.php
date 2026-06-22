<?php

declare(strict_types = 1);

use Pekral\CursorRules\InstallerFailure;

test('InstallerFailure missingSource creates exception with correct message', function (): void {
    $exception = InstallerFailure::missingSource('/dev/path', '/vendor/path');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Source not found. Checked /dev/path and /vendor/path.');
});

test('InstallerFailure directoryCreationFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::directoryCreationFailed('/some/directory');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Cannot create directory: /some/directory');
});

test('InstallerFailure fileCopyFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::fileCopyFailed('/source/file', '/dest/file');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Unable to copy /source/file to /dest/file.');
});

test('InstallerFailure removalFailed creates exception with correct message', function (): void {
    $exception = InstallerFailure::removalFailed('/some/path');

    expect($exception)->toBeInstanceOf(InstallerFailure::class);
    expect($exception->getMessage())->toBe('Cannot remove: /some/path');
});
