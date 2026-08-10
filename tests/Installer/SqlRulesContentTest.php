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

test('sql optimalize rule carries the MySQL schema design standard', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/sql/optimalize.mdc');

    // The standard is engine-scoped and greenfield-scoped so it cannot fight an established project convention.
    expect($content)->toContain('**Scope of the standard below.**');
    expect($content)->toContain('It governs **new tables and new columns**; never demand a rename or a retro-migration of an existing schema.');
    expect($content)->toContain('@skills/postgres-patterns/SKILL.md');
    expect($content)->toContain('### Strict mode is the prerequisite');
    expect($content)->toContain('STRICT_TRANS_TABLES,ONLY_FULL_GROUP_BY,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION');
    expect($content)->toContain('**Table names are singular**');
    expect($content)->toContain('**Boolean columns are adjectives with positive polarity**');
    expect($content)->toContain('`_at` for a moment in time (`DATETIME`)');
    expect($content)->toContain('**Never `TIMESTAMP`**');
    expect($content)->toContain('**`modified_at` and `updated_at` are two different columns.**');
    expect($content)->toContain('**`VARCHAR(255)` is cargo cult.**');
    expect($content)->toContain('**Money and every exact decimal is `DECIMAL`**');
    expect($content)->toContain('**The primary key propagates**');
    expect($content)->toContain('### Charset and collation');
    expect($content)->toContain('ERROR 1267');
    expect($content)->toContain('**Composition → `CASCADE`**');
    expect($content)->toContain('**Association → `RESTRICT`**');
    expect($content)->toContain('**Meaningful detachment → `SET NULL`**');
    expect($content)->toContain('### CHECK constraints');
    expect($content)->toContain('chk_order_shipped_needs_date');
});

test('sql optimalize rule carries the bulk and streaming processing standard for large datasets', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/sql/optimalize.mdc');

    expect($content)->toContain('## Bulk and streaming processing of large datasets');
    // A "large dataset" is defined by data-driven cardinality, not by a constant in the code.
    expect($content)->toContain('never by a fixed constant written in the code');
    expect($content)->toContain('Code that is correct for ten rows and unusable for a million is a defect');
    expect($content)->toContain('**Never materialize an unbounded result set.**');
    expect($content)->toContain('chunkById(1000, ...)');
    expect($content)->toContain('**`chunkById()` over `chunk()` whenever the loop writes.**');
    expect($content)->toContain('**Size the batch explicitly.**');
    expect($content)->toContain('500–1000 rows per statement');
    expect($content)->toContain('**One short transaction per batch**');
    expect($content)->toContain('resumable and idempotent');
    expect($content)->toContain('**Batch the side effects too.**');
    expect($content)->toContain('Bus::batch()');
    // The example dispatches one job per chunk directly — a batch of exactly one job
    // pays the job_batches bookkeeping for no fan-out, so it must not be the canonical snippet.
    expect($content)->toContain('ProcessOrders::dispatch($orders->modelKeys());');
    expect($content)->not->toContain('Bus::batch([new ProcessOrders');
    expect($content)->toContain('Cache::putMany()');
    expect($content)->toContain('**Aggregate, filter, and sort in SQL, not in PHP.**');
    expect($content)->toContain('**Imports and exports stream.**');
    expect($content)->toContain('**Move a large run off the request.**');
    // The severity / detection detail is owned by the CR rule, not duplicated here.
    expect($content)->toContain('@rules/code-review/general.mdc` *Batch-First Processing & Performance at Scale*');
});
