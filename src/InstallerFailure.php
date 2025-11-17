<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use RuntimeException;

final class InstallerFailure extends RuntimeException
{

    public static function missingSource(string $developmentPath, string $vendorPath): self
    {
        return new self(sprintf('Source not found. Checked %s and %s.', $developmentPath, $vendorPath));
    }

    public static function directoryCreationFailed(string $directory): self
    {
        return new self(sprintf('Cannot create directory: %s', $directory));
    }

    public static function fileCopyFailed(string $source, string $destination): self
    {
        return new self(sprintf('Unable to copy %s to %s.', $source, $destination));
    }

    public static function removalFailed(string $path): self
    {
        return new self(sprintf('Cannot remove: %s', $path));
    }

}
