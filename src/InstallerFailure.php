<?php

declare(strict_types = 1);

namespace AgenticVibes\AgentSkills;

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

    public static function settingsJsonInvalid(string $path, string $reason): self
    {
        return new self(sprintf('Cannot parse Claude settings file %s: %s.', $path, $reason));
    }

    public static function settingsJsonWriteFailed(string $path, string $reason): self
    {
        return new self(sprintf('Cannot write Claude settings file %s: %s.', $path, $reason));
    }

    public static function settingsSubagentWritesInvalid(string $path, string $reason): self
    {
        return new self(sprintf('Invalid subagent-write permissions for %s: %s.', $path, $reason));
    }

}
