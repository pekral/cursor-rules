<?php

declare(strict_types = 1);

test('laravel rules prefer filled()/blank() helpers over strict empty-string comparisons', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## String Emptiness Checks');
    expect($content)->toContain('`filled()`');
    expect($content)->toContain('`blank()`');
    expect($content)->toContain('`!== \'\'`');
    expect($content)->toContain('`=== \'\'`');
});

test('laravel rules extend Database and Eloquent with index and EXPLAIN guidance (issue #525)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('verify indexes for every high-cardinality');
    expect($content)->toContain('check `EXPLAIN` before shipping');
    expect($content)->toContain('left-most prefix');
    expect($content)->toContain('Do not add indexes blindly');
});

test('laravel rules forbid dispatching full Eloquent models to queued jobs (issue #525)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Do not dispatch full Eloquent models to queued jobs');
    expect($content)->toContain('Fetch fresh models inside `handle()`');
    expect($content)->toContain('serialize only the explicit fields needed by the job');
    expect($content)->toContain('Queue constructors must only accept lightweight scalar values');
});

test('laravel rules tighten Dependency Injection with hot-path and lazy resolution guidance (issue #525)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Do not call `app()`, `resolve()`, or `$container->make()` inside loops or hot paths');
    expect($content)->toContain('Bind stateless expensive services as singletons');
    expect($content)->toContain('Prefer lazy service resolution');
    expect($content)->toContain('Keep service constructors lightweight');
});

test('laravel rules require selective and lightweight middleware (issue #525)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Apply middleware selectively');
    expect($content)->toContain('Put cheap fast-failing middleware before expensive middleware');
    expect($content)->toContain('Do not perform database queries, service orchestration, or external API calls in middleware');
});

test('laravel rules add Stateless Runtime, Caching, and Long-Running Runtime Safety sections (issue #525)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## Stateless Runtime');
    expect($content)->toContain('Production application servers must be disposable');
    expect($content)->toContain('`onOneServer()` or another explicit distributed mutex');

    expect($content)->toContain('## Caching');
    expect($content)->toContain('Use Redis or another shared cache for sessions, queues, cross-server locks');
    expect($content)->toContain('Always set explicit TTLs for cached values');
    expect($content)->toContain('Do not cache user-specific or permission-sensitive data without including the relevant identity');

    expect($content)->toContain('## Long-Running Runtime Safety');
    expect($content)->toContain('safe for long-running PHP processes');
    expect($content)->toContain('Octane');
    expect($content)->toContain('worker recycling');
});

test('laravel rules document Laravel 13 Bus::bulk, scheduler metadata, and Schema::hasForeignKey (issue #551)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Use `Bus::bulk()` to dispatch many jobs onto the queue in a single call');
    expect($content)->toContain('Reserve `Bus::batch()` for cases that genuinely need progress tracking');

    expect($content)->toContain('## Scheduling');
    expect($content)->toContain('Attach structured metadata to scheduled commands with `withAttributes()`');
    expect($content)->toContain('monitoring, logging, and alerting');

    expect($content)->toContain('Use `Schema::hasForeignKey()` to verify a foreign key exists before creating or dropping it');
});

test('laravel rules require user-facing UI, console, and API strings to be translatable (issue #553)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## Localization and Translatable Strings');
    expect($content)->toContain('Every string a user can see must go through Laravel\'s translation layer');
    expect($content)->toContain('**UI**');
    expect($content)->toContain('**Console**');
    expect($content)->toContain('**API**');
    expect($content)->toContain('$this->info()');
    expect($content)->toContain('JSON `message` fields');
    expect($content)->toContain('add every new key to **all** shipped locales');
});

test('laravel rules forbid real HTTP and real system processes in tests (issue #553)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Never allow real external HTTP calls in tests.');
    expect($content)->toContain('Never let tests run real system processes outside the application.');
    expect($content)->toContain('Tests must never invoke an external binary or script directly on the system');
    expect($content)->toContain('`Process::fake()`');
    expect($content)->toContain('shell_exec()');
    expect($content)->toContain('proc_open()');
});

test('architecture rules enumerate the seven allowed business logic layers including Eloquent models', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('## Business Logic Layers');
    expect($content)->toContain('seven class types');
    expect($content)->toContain('**Actions**');
    expect($content)->toContain('**Model Services**');
    expect($content)->toContain('**Repositories**');
    expect($content)->toContain('**ModelManagers**');
    expect($content)->toContain('**Data Validators**');
    expect($content)->toContain('**Data Builders**');
    expect($content)->toContain('**Eloquent models**');
    expect($content)->toContain('simple, self-contained domain methods');
    expect($content)->toContain('@skills/class-refactoring/SKILL.md');
});

test('laravel rules permit simple self-contained logic on Eloquent models', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('Simple, self-contained domain logic may live as methods on the model.');
    expect($content)->toContain('$user->isActive()');
    expect($content)->toContain('Forbidden on models');
    expect($content)->toContain('$user->sendWelcomeEmail()');
    expect($content)->toContain('lazy-load relationships count as new database queries');
    expect($content)->not->toContain('Keep business logic out of models.');
    expect($content)->not->toContain('Keep business logic out of controllers, middleware, Blade views, and Eloquent models.');
});

test('architecture bullets remain under the Architecture heading and Business Logic Layers sits before Actions', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    $architectureHeading = strpos($content, "\n## Architecture\n");
    $multitenancyBullet = strpos($content, 'Multitenancy remains mandatory');
    $customHelpersBullet = strpos($content, '**Custom Helpers:**');
    $businessLogicHeading = strpos($content, "\n## Business Logic Layers\n");
    $actionsHeading = strpos($content, "\n## Actions\n");

    assert($architectureHeading !== false);
    assert($multitenancyBullet !== false);
    assert($customHelpersBullet !== false);
    assert($businessLogicHeading !== false);
    assert($actionsHeading !== false);

    expect($architectureHeading)->toBeLessThan($multitenancyBullet);
    expect($multitenancyBullet)->toBeLessThan($businessLogicHeading);
    expect($customHelpersBullet)->toBeLessThan($businessLogicHeading);
    expect($businessLogicHeading)->toBeLessThan($actionsHeading);
});

test('architecture rules carry the Shared Concerns (Traits) section scoped to globally reusable, domain-agnostic logic (issue #531)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('## Shared Concerns (Traits)');
    expect($content)->toContain('`app/Concerns/` is the **canonical home for all globally shared and reusable logic**');
    expect($content)->toContain('**Globally applicable**');
    expect($content)->toContain('**Domain-agnostic**');
    expect($content)->toContain('**Reusable as-is**');
    expect($content)->toContain('**Forbidden in `app/Concerns/`:**');
    expect($content)->toContain('Domain-specific logic');
    expect($content)->toContain('Single-use traits or helpers consumed by exactly one class');
    expect($content)->toContain('Orchestration, persistence, query, or HTTP/queue dispatching logic');
    expect($content)->toContain('The **Validation Rules (Traits)** section below is one specific instance of this broader rule');
    expect($content)->toContain('This is the canonical worked example of the **Shared Concerns (Traits)** rule above.');
});

test('architecture Shared Concerns (Traits) section sits immediately before Validation Rules (Traits) (issue #531)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    $sharedConcernsHeading = strpos($content, "\n## Shared Concerns (Traits)\n");
    $validationRulesHeading = strpos($content, "\n## Validation Rules (Traits)\n");
    $dataValidatorsHeading = strpos($content, "\n## Data Validators\n");

    expect($sharedConcernsHeading)->not->toBeFalse();
    expect($validationRulesHeading)->not->toBeFalse();
    expect($dataValidatorsHeading)->not->toBeFalse();
    assert($sharedConcernsHeading !== false);
    assert($validationRulesHeading !== false);
    assert($dataValidatorsHeading !== false);

    expect($sharedConcernsHeading)->toBeLessThan($validationRulesHeading);
    expect($validationRulesHeading)->toBeLessThan($dataValidatorsHeading);
});

test('architecture CR Severity Rules cover app/Concerns misuse in both directions (issue #531)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/architecture.mdc');

    expect($content)->toContain('domain-specific code placed under `app/Concerns/`');
    expect($content)->toContain('shared, reusable trait or helper logic placed outside `app/Concerns/`');
    expect($content)->toContain('single-use trait parked in `app/Concerns/`');
    expect($content)->toContain('per **Shared Concerns (Traits)**');
});

test('laravel rules carry the parallel Shared Concerns section and Layer Responsibilities bullet (issue #531)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/laravel/laravel.mdc');

    expect($content)->toContain('## Shared Concerns');
    expect($content)->toContain('Shared Concerns (`app/Concerns/`): globally shared and reusable logic');
    expect($content)->toContain('canonical home for all globally shared and reusable logic in the application');
    expect($content)->toContain('**globally applicable**');
    expect($content)->toContain('**domain-agnostic**');
    expect($content)->toContain('**reusable as-is**');
    expect($content)->toContain('Never put domain-specific logic in `app/Concerns/`');
    expect($content)->toContain('Validation rule traits (see the **Validation** section below) are one specific worked example');
});

test('laravel-security skill carries the secure-defaults reference and checklist', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/laravel-security/SKILL.md');

    expect($content)->toContain('Quick Security Checklist');
    expect($content)->toContain('Mass assignment');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('@skills/security-review/SKILL.md');
});
