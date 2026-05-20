<?php

declare(strict_types = 1);

use Pekral\CursorRules\CoverageDiffCheck;
use Pekral\CursorRules\CoverageDiffCheckFailure;

it('parseUnifiedDiff returns the expected changed-line map', function (string $diff, array $expected): void {
    expect(CoverageDiffCheck::parseUnifiedDiff($diff))->toBe($expected);
})->with([
    'count defaults to 1 when omitted' => [
        "+++ b/src/Foo.php\n@@ -1 +5 @@\n",
        ['src/Foo.php' => [5 => true]],
    ],
    'empty input yields empty map' => ['', []],
    'hunk before any +++ marker is ignored' => [
        "@@ -1 +1 @@\n",
        [],
    ],
    'multiple files are tracked independently' => [
        "+++ b/src/A.php\n@@ -1 +1 @@\n+++ b/src/B.php\n@@ -1 +1 @@\n",
        ['src/A.php' => [1 => true], 'src/B.php' => [1 => true]],
    ],
    'multiple hunks aggregate per file' => [
        "+++ b/src/Foo.php\n@@ -1 +1,2 @@\n@@ -5 +10 @@\n",
        ['src/Foo.php' => [1 => true, 2 => true, 10 => true]],
    ],
    'non-PHP file is ignored' => [
        "+++ b/README.md\n@@ -1 +1,3 @@\n",
        [],
    ],
    'single hunk records every added line' => [
        "+++ b/src/Foo.php\n@@ -10,0 +11,3 @@\n",
        ['src/Foo.php' => [11 => true, 12 => true, 13 => true]],
    ],
    'zero-count hunk is skipped' => [
        "+++ b/src/Foo.php\n@@ -10,5 +10,0 @@\n",
        [],
    ],
]);

it('findGapsInClover returns no gaps when the changed-line map is empty', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover($cloverXml, [], '/repo'))->toBe([]);
});

it('findGapsInClover returns no gaps when every changed line is covered', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'stmt', 5], [11, 'stmt', 1]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Foo.php' => [10 => true, 11 => true]],
        '/repo',
    ))->toBe([]);
});

it('findGapsInClover reports lines that are changed and uncovered', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'stmt', 0], [11, 'stmt', 5]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Foo.php' => [10 => true, 11 => true]],
        '/repo',
    ))->toBe(['src/Foo.php' => [10]]);
});

it('findGapsInClover ignores non-stmt line nodes such as methods', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'method', 0], [11, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Foo.php' => [10 => true, 11 => true]],
        '/repo',
    ))->toBe(['src/Foo.php' => [11]]);
});

it('findGapsInClover ignores clover lines that the diff does not touch', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'stmt', 0], [99, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Foo.php' => [10 => true]],
        '/repo',
    ))->toBe(['src/Foo.php' => [10]]);
});

it('findGapsInClover skips files that the diff does not touch', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[10, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Bar.php' => [10 => true]],
        '/repo',
    ))->toBe([]);
});

it('findGapsInClover returns sorted and deduped line numbers per file', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/repo/src/Foo.php', [[5, 'stmt', 0], [3, 'stmt', 0], [5, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Foo.php' => [3 => true, 5 => true]],
        '/repo',
    ))->toBe(['src/Foo.php' => [3, 5]]);
});

it('findGapsInClover keeps the absolute file path when the project root does not match', function (): void {
    $cloverXml = coverageDiffCheckBuildClover([
        ['/elsewhere/src/Foo.php', [[10, 'stmt', 0]]],
    ]);

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['/elsewhere/src/Foo.php' => [10 => true]],
        '/repo',
    ))->toBe(['/elsewhere/src/Foo.php' => [10]]);
});

it('findGapsInClover walks files nested under <package> in addition to direct <project> children', function (): void {
    $cloverXml = <<<'XML'
        <?xml version="1.0" encoding="UTF-8"?>
        <coverage>
          <project>
            <file name="/repo/src/Top.php">
              <line num="10" type="stmt" count="0"/>
            </file>
            <package name="Acme">
              <file name="/repo/src/Nested.php">
                <line num="20" type="stmt" count="0"/>
              </file>
            </package>
          </project>
        </coverage>
        XML;

    expect(CoverageDiffCheck::findGapsInClover(
        $cloverXml,
        ['src/Top.php' => [10 => true], 'src/Nested.php' => [20 => true]],
        '/repo',
    ))->toBe(['src/Top.php' => [10], 'src/Nested.php' => [20]]);
});

it('findGapsInClover throws when the XML is malformed', function (): void {
    CoverageDiffCheck::findGapsInClover('<not-xml', ['src/Foo.php' => [1 => true]], '/repo');
})->throws(CoverageDiffCheckFailure::class, 'Invalid Clover XML');

it('findGapsInClover throws when the XML has no <project> root', function (): void {
    CoverageDiffCheck::findGapsInClover(
        '<?xml version="1.0"?><coverage><other/></coverage>',
        ['src/Foo.php' => [1 => true]],
        '/repo',
    );
})->throws(CoverageDiffCheckFailure::class, 'Invalid Clover XML');

it('discoverChangedLines returns an array shape for the current repository state', function (): void {
    $result = CoverageDiffCheck::discoverChangedLines('HEAD');

    expect($result)->toBeArray();
});

it('discoverChangedLines aggregates the parsed diff into a changed-line map', function (): void {
    $result = CoverageDiffCheck::discoverChangedLines('HEAD~5');

    foreach ($result as $file => $lines) {
        expect($file)->toBeString();
        expect($lines)->toBeArray();

        foreach ($lines as $lineNumber => $marker) {
            expect($lineNumber)->toBeInt();
            expect($marker)->toBeTrue();
        }
    }
});

it('discoverChangedLines restricts the result to the caller-supplied file allow-list', function (): void {
    $tempDir = sys_get_temp_dir() . '/coverage-diff-allowlist-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($tempDir);
    $originalCwd = getcwd();
    chdir($tempDir);

    try {
        shell_exec('git init --quiet -b main 2>&1');
        shell_exec('git -c user.email=t@t.local -c user.name=Tester commit --allow-empty --quiet -m initial 2>&1');

        file_put_contents($tempDir . '/Kept.php', "<?php\necho 'kept';\n");
        file_put_contents($tempDir . '/Filtered.php', "<?php\necho 'filtered';\n");

        $result = CoverageDiffCheck::discoverChangedLines('HEAD', ['Kept.php']);

        expect($result)->toHaveKey('Kept.php');
        expect($result)->not->toHaveKey('Filtered.php');
    } finally {
        if (is_string($originalCwd)) {
            chdir($originalCwd);
        }

        installerRemoveDirectory($tempDir);
    }
});

it('discoverChangedLines picks up untracked PHP files via git ls-files', function (): void {
    $tempDir = sys_get_temp_dir() . '/coverage-diff-untracked-' . bin2hex(random_bytes(4));
    installerEnsureDirectory($tempDir);
    $originalCwd = getcwd();
    chdir($tempDir);

    try {
        shell_exec('git init --quiet -b main 2>&1');
        shell_exec('git -c user.email=t@t.local -c user.name=Tester commit --allow-empty --quiet -m initial 2>&1');

        file_put_contents($tempDir . '/Untracked.php', "<?php\necho 'a';\necho 'b';\n");

        $result = CoverageDiffCheck::discoverChangedLines('HEAD');

        expect($result)->toHaveKey('Untracked.php');
        expect($result['Untracked.php'])->toBe([1 => true, 2 => true, 3 => true]);
    } finally {
        if (is_string($originalCwd)) {
            chdir($originalCwd);
        }

        installerRemoveDirectory($tempDir);
    }
});
