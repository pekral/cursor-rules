<?php

declare(strict_types = 1);

namespace Pekral\CursorRules;

use SimpleXMLElement;

final class CoverageDiffCheck
{

    /**
     * Parses a unified diff and returns the set of line numbers introduced or modified per PHP file.
     *
     * @return array<string, array<int, true>>
     */
    public static function parseUnifiedDiff(string $diff): array
    {
        $changed = [];
        $currentFile = null;

        foreach (explode("\n", $diff) as $line) {
            $matchedFile = self::matchFileMarker($line);

            if ($matchedFile !== null) {
                $currentFile = $matchedFile;

                continue;
            }

            if ($currentFile === null) {
                continue;
            }

            foreach (self::matchHunkAddedLines($line) as $lineNumber) {
                $changed[$currentFile][$lineNumber] = true;
            }
        }

        return $changed;
    }

    /**
     * Compares a Clover coverage report against a changed-line map and returns the uncovered changed lines per file.
     *
     * @param array<string, array<int, true>> $changedLines
     * @return array<string, array<int>>
     */
    public static function findGapsInClover(string $cloverXml, array $changedLines, string $projectRoot): array
    {
        if ($changedLines === []) {
            return [];
        }

        $project = self::loadCloverProject($cloverXml);
        $rootWithSlash = rtrim($projectRoot, '/') . '/';
        $gaps = [];

        foreach (self::cloverFiles($project) as $fileNode) {
            $relativePath = self::relativiseToRoot((string) $fileNode['name'], $rootWithSlash);

            if (!isset($changedLines[$relativePath])) {
                continue;
            }

            $uncovered = self::uncoveredChangedLines($fileNode, $changedLines[$relativePath]);

            if ($uncovered !== []) {
                $gaps[$relativePath] = $uncovered;
            }
        }

        return $gaps;
    }

    /**
     * Runs `git diff` against the given base ref and the working tree, returning the aggregated changed-line map.
     *
     * When $restrictToFiles is non-empty, only diffs for the listed files are aggregated. The allow-list lets
     * callers (typically an AI agent that already knows which files changed) scope the run without losing the
     * precise line-level coverage filtering.
     *
     * @param array<int, string> $restrictToFiles
     * @return array<string, array<int, true>>
     */
    public static function discoverChangedLines(string $baseRef, array $restrictToFiles = []): array
    {
        $allowList = self::normaliseAllowList($restrictToFiles);
        $changed = [];

        foreach (self::diffCommands($baseRef, $allowList) as $command) {
            $changed = self::mergeChangedLines($changed, self::parseUnifiedDiff((string) shell_exec($command)));
        }

        foreach (self::untrackedPhpFiles($allowList) as $file) {
            $command = sprintf('git diff --no-index --unified=0 /dev/null %s 2>/dev/null', escapeshellarg($file));
            $changed = self::mergeChangedLines($changed, self::parseUnifiedDiff((string) shell_exec($command)));
        }

        return $allowList === [] ? $changed : self::filterByAllowList($changed, $allowList);
    }

    /**
     * @param array<int, string> $allowList
     * @return array<int, string>
     */
    private static function diffCommands(string $baseRef, array $allowList): array
    {
        $pathspec = self::buildPathspec($allowList);

        return [
            sprintf('git diff --unified=0 --diff-filter=ACMRTUXB %s...HEAD -- %s2>/dev/null', escapeshellarg($baseRef), $pathspec),
            sprintf('git diff --unified=0 --diff-filter=ACMRTUXB -- %s2>/dev/null', $pathspec),
            sprintf('git diff --unified=0 --cached --diff-filter=ACMRTUXB -- %s2>/dev/null', $pathspec),
        ];
    }

    /**
     * @param array<int, string> $allowList
     * @return iterable<string>
     */
    private static function untrackedPhpFiles(array $allowList): iterable
    {
        $command = sprintf('git ls-files --others --exclude-standard -- %s2>/dev/null', self::buildPathspec($allowList));
        $output = (string) shell_exec($command);

        foreach (explode("\n", $output) as $line) {
            $trimmed = trim($line);

            if ($trimmed !== '' && str_ends_with($trimmed, '.php')) {
                yield $trimmed;
            }
        }
    }

    /**
     * @param array<int, string> $restrictToFiles
     * @return array<int, string>
     */
    private static function normaliseAllowList(array $restrictToFiles): array
    {
        $allowList = [];

        foreach ($restrictToFiles as $file) {
            $trimmed = trim($file);

            if ($trimmed !== '') {
                $allowList[$trimmed] = $trimmed;
            }
        }

        return array_values($allowList);
    }

    /**
     * @param array<int, string> $allowList
     */
    private static function buildPathspec(array $allowList): string
    {
        if ($allowList === []) {
            return '';
        }

        $quoted = array_map(static fn (string $path): string => escapeshellarg($path), $allowList);

        return implode(' ', $quoted) . ' ';
    }

    /**
     * @param array<string, array<int, true>> $changed
     * @param array<int, string> $allowList
     * @return array<string, array<int, true>>
     */
    private static function filterByAllowList(array $changed, array $allowList): array
    {
        $allowedKeys = array_flip($allowList);
        $filtered = [];

        foreach ($changed as $file => $lines) {
            if (isset($allowedKeys[$file])) {
                $filtered[$file] = $lines;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, array<int, true>> $target
     * @param array<string, array<int, true>> $source
     * @return array<string, array<int, true>>
     */
    private static function mergeChangedLines(array $target, array $source): array
    {
        foreach ($source as $file => $lines) {
            foreach (array_keys($lines) as $lineNumber) {
                $target[$file][$lineNumber] = true;
            }
        }

        return $target;
    }

    private static function matchFileMarker(string $line): ?string
    {
        return preg_match('#^\+\+\+ b/(.+\.php)$#', $line, $match) === 1 ? $match[1] : null;
    }

    /**
     * @return array<int>
     */
    private static function matchHunkAddedLines(string $line): array
    {
        if (preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $match) !== 1) {
            return [];
        }

        $start = (int) $match[1];
        $count = isset($match[2]) ? (int) $match[2] : 1;

        return $count === 0 ? [] : range($start, $start + $count - 1);
    }

    private static function loadCloverProject(string $cloverXml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($cloverXml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$xml instanceof SimpleXMLElement || !isset($xml->project)) {
            throw CoverageDiffCheckFailure::invalidCloverXml('missing <project> root');
        }

        return $xml->project;
    }

    private static function relativiseToRoot(string $absolutePath, string $rootWithSlash): string
    {
        return str_starts_with($absolutePath, $rootWithSlash)
            ? substr($absolutePath, strlen($rootWithSlash))
            : $absolutePath;
    }

    /**
     * @param array<int, true> $changedLinesForFile
     * @return array<int>
     */
    private static function uncoveredChangedLines(SimpleXMLElement $fileNode, array $changedLinesForFile): array
    {
        $uncovered = [];

        foreach ($fileNode->line as $lineNode) {
            if ((string) $lineNode['type'] !== 'stmt') {
                continue;
            }

            $lineNumber = (int) $lineNode['num'];

            if (!isset($changedLinesForFile[$lineNumber]) || (int) $lineNode['count'] !== 0) {
                continue;
            }

            $uncovered[$lineNumber] = $lineNumber;
        }

        $uncovered = array_values($uncovered);
        sort($uncovered);

        return $uncovered;
    }

    /**
     * Yields every <file> node from a Clover <project> tree, regardless of whether it sits directly under
     * <project> or nested inside <package>.
     *
     * @return iterable<\SimpleXMLElement>
     */
    private static function cloverFiles(SimpleXMLElement $project): iterable
    {
        foreach ($project->file as $fileNode) {
            yield $fileNode;
        }

        foreach ($project->package as $packageNode) {
            foreach ($packageNode->file as $fileNode) {
                yield $fileNode;
            }
        }
    }

}
