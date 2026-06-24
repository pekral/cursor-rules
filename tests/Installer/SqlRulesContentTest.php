<?php

declare(strict_types = 1);

test('sql optimalize rule carries the New storage reuse analysis section (issue #708)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/sql/optimalize.mdc');

    expect($content)->toContain('## New storage reuse analysis');
    expect($content)->toContain('Can this data be stored in an existing storage without a drastic impact on performance?');
    expect($content)->toContain('Schema::create(...)');
    expect($content)->toContain('@skills/code-review/SKILL.md');
    expect($content)->toContain('Do not flag migrations that only add a column or index to an existing table');
});
