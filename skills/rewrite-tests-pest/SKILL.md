---
name: rewrite-tests-pest
description: Rewrites existing PHPUnit-style tests to PEST syntax following project testing rules. Use when the user wants to convert tests to PEST, rewrite tests to PEST syntax, or migrate PHPUnit tests to Pest. Ensures 100% coverage after rewrite.
---

# Rewrite Tests to PEST Syntax

Specialist for converting PHPUnit test classes to PEST syntax. Apply testing rules from `.cursor/rules/php/standards.mdc` (Testing section) and `.cursor/rules/php-testing.mdc`. Never modify production code. After rewrite, coverage must be 100% for changed code; add missing tests if needed.

**Role:** Rewrite tests only. Apply all project test rules.
**Constraint:** Tests only. Never modify production code.

---

## 1. Load Rules

- Read `.cursor/rules/php/standards.mdc` (focus: Testing, Bug-Fix Workflow, Naming, Structure).
- Read `.cursor/rules/php-testing.mdc` (analysis, mocking, asserts, coverage, running tests).

---

## 2. Prerequisites

- Run only if Pest is installed (`pestphp/pest` in `composer.json` or `vendor/bin/pest` exists).
- If Pest is not installed, do not rewrite; inform the user.

---

## 3. PHPUnit → PEST Mapping

| PHPUnit | PEST |
|--------|------|
| `final class FooTest extends TestCase` | Remove class; use one or more test files (e.g. `tests/FooTest.php` or feature-based files). |
| `public function testSomething(): void` | `test('something does something', function (): void { ... });` or `it('does something', function (): void { ... });` |
| `$this->assertSame($a, $b)` | `expect($a)->toBe($b)` (or `toEqual` for loose comparison) |
| `$this->assertTrue($x)` | `expect($x)->toBeTrue()` |
| `$this->assertFalse($x)` | `expect($x)->toBeFalse()` |
| `$this->assertInstanceOf(Class::class, $x)` | `expect($x)->toBeInstanceOf(Class::class)` |
| `$this->assertCount($n, $iterable)` | `expect($iterable)->toHaveCount($n)` |
| `$this->assertContains($needle, $haystack)` | `expect($haystack)->toContain($needle)` |
| `$this->assertEmpty($x)` | `expect($x)->toBeEmpty()` |
| `$this->expectException(Ex::class)` | `expect(fn () => ...)->toThrow(Ex::class)` or catch in test and assert |
| Data provider `@dataProvider foo` | `test('...', function (mixed $a, mixed $b): void { ... })->with([...])` or `dataset('name', [...])` |

- Test description: first argument of `test('...')` or `it('...')` — human-readable, describes behavior.
- Use local variables only; no test class properties.
- Shared setup: use `beforeEach()` in the same file or in `tests/Pest.php` if project uses it.
- Keep helpers in same file or in `tests/Pest.php` as plain functions if already used elsewhere.

---

## 4. Workflow

1. **Identify scope**  
   Target file(s): PHPUnit test classes (e.g. `*Test.php` extending `TestCase`). Optionally one PEST file per original class or one file per feature.

2. **Analyse**  
   - List all test methods and assertions.  
   - Note data providers, `setUp`/`tearDown`, and shared state.  
   - Check which code is covered (existing coverage report or run coverage for that file).

3. **Rewrite**  
   - Replace class with `test()` / `it()` calls.  
   - Convert `$this->assert*` to `expect()->...`.  
   - Convert data providers to `->with()` or `dataset()`.  
   - Move `setUp` logic into `beforeEach()` or into each test; avoid class state.  
   - Keep test data in English; keep names descriptive.

4. **Coverage**  
   - Run tests for changed files.  
   - Run coverage (e.g. Clover XML) for the code exercised by those tests.  
   - If coverage for changed production code is below 100%, add or adjust tests until 100%.

5. **Cleanup**  
   - Remove generated coverage artifacts when done.  
   - Run project fixers (e.g. composer scripts, PHP CS) if available.  
   - Delete obsolete PHPUnit-only boilerplate (e.g. empty class) if the file is now fully PEST.

---

## 5. Conventions (from project rules)

- Arrange–act–assert; error cases first, success last.
- Mock only external services (e.g. HTTP); use Mockery or framework fakes (e.g. `Http::fake()`).
- No tests for abstract/private/protected implementation details; test public behaviour.
- Final test classes: in PEST there is no class; keep test file and descriptions clear.
- Data providers via argument (e.g. `->with([...])`), not PHPDoc.
- Run tests only for changed files; check coverage only for changed code.

---

## 6. After Rewrite

- Run tests for the rewritten files.  
- Ensure 100% coverage for any changed production code; add tests if needed.  
- Remove coverage output files.  
- Run fixers if the project defines them.
