<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

/**
 * Appends the humanizer directive to installed SKILL.md files.
 */
final class InstallerHumanizer
{

    private const string SECTION_HEADING = '## Output Humanization';

    private const string DIRECTIVE = '- Use [blader/humanizer](https://github.com/blader/humanizer)'
        . ' for all skill outputs to keep the text natural and human-friendly.';

    public static function appendIfNeeded(string $destination): void
    {
        if (!self::isSkillMarkdown($destination) || is_link($destination)) {
            return;
        }

        $contents = file_get_contents($destination);

        if ($contents === false || str_contains($contents, self::DIRECTIVE)) {
            return;
        }

        $normalized = rtrim($contents);

        if ($normalized !== '') {
            $normalized .= PHP_EOL . PHP_EOL;
        }

        $updated = $normalized
            . self::SECTION_HEADING . PHP_EOL
            . self::DIRECTIVE
            . PHP_EOL;

        file_put_contents($destination, $updated);
    }

    private static function isSkillMarkdown(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_ends_with($normalized, '/SKILL.md');
    }

}
