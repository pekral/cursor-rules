<?php

declare(strict_types = 1);

test('CR run produces one consolidated linked-tracker comment per linked issue (issue #498)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $githubTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-github.md');
    $jiraTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-jira.md');

    expect($prSummary)->toContain('Embedded blocks (consolidation contract — issue #498)');
    expect($prSummary)->toContain('append them **verbatim** after `How to test`');
    expect($prSummary)->toContain('published once per linked tracker target');

    expect($github)->toContain('#### Linked-issue consolidated summary (mandatory — single comment per linked issue)');
    expect($github)->toContain('Consolidation contract (issue #498)');
    expect($github)->toContain('exactly one comment per linked issue');

    expect($jira)->toContain('#### JIRA (consolidated non-technical comment — fresh comment per CR run)');
    expect($jira)->toContain('Consolidation contract (issue #498)');
    expect($jira)->toContain('fresh JIRA comment');

    expect($githubTemplate)->toContain('{embedded_blocks}');
    expect($githubTemplate)->toContain('@skills/assignment-compliance-check/SKILL.md');
    expect($jiraTemplate)->toContain('{embedded_blocks}');
    expect($jiraTemplate)->toContain('@skills/assignment-compliance-check/SKILL.md');
});

test('pr-summary surfaces an assignment non-compliance verdict at the top of the tracker comment', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');
    $githubTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-github.md');
    $jiraTemplate = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-jira.md');

    expect($prSummary)->toContain('Assignment non-compliance verdict (top banner)');
    expect($prSummary)->toContain('{assignment_verdict}');

    foreach ([$githubTemplate, $jiraTemplate] as $template) {
        expect($template)->toContain('{assignment_verdict}');
        expect($template)->toContain('do not satisfy the assignment');
        expect($template)->toContain('omit this slot entirely');
    }
});

test('CR skills publish through the publish helper — GitHub always-new, JIRA always-new comment per CR run', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $githubScript = $packageDir . '/skills/code-review-github/scripts/upsert-comment.sh';
    $jiraScript = $packageDir . '/skills/code-review-jira/scripts/upsert-comment.sh';

    expect(is_file($githubScript))->toBeTrue();
    expect(is_executable($githubScript))->toBeTrue();
    expect(is_file($jiraScript))->toBeTrue();
    expect(is_executable($jiraScript))->toBeTrue();

    $githubScriptBody = (string) file_get_contents($githubScript);
    expect($githubScriptBody)->toContain('MARKER_KEY="${3:-cr-comment}"');
    expect($githubScriptBody)->toContain('<!-- ${MARKER_KEY}:actor=${ACTOR} -->');
    expect($githubScriptBody)->toContain('gh api user --jq .login');
    // Issue #519: a transient `gh api user` failure (rate limit, network blip,
    // token refresh) used to crash with a misleading "is gh authenticated?"
    // message because stderr and exit code were both swallowed. The script
    // now retries up to three times, captures the underlying stderr, and
    // surfaces the real error to the caller.
    expect($githubScriptBody)->toContain('ACTOR_STDERR="$(mktemp)"');
    expect($githubScriptBody)->toContain('trap \'rm -f "$ACTOR_STDERR"\' EXIT');
    expect($githubScriptBody)->toContain('for attempt in 1 2 3; do');
    expect($githubScriptBody)->toContain('gh api user --jq .login 2>"$ACTOR_STDERR"');
    expect($githubScriptBody)->toContain('failed to resolve current GitHub actor after 3 attempts');
    expect($githubScriptBody)->toContain('(run: gh auth status)');
    expect($githubScriptBody)->not->toContain('gh api user --jq .login 2>/dev/null');
    // Always-new comment on GitHub: the PATCH branch was removed by user
    // request — every CR run POSTs a fresh comment so the PR thread keeps a
    // chronological audit trail. The marker stays for per-actor traceability.
    expect($githubScriptBody)->not->toContain('-X PATCH');
    expect($githubScriptBody)->not->toContain('action=updated');
    expect($githubScriptBody)->not->toContain('repos/${NWO}/issues/comments/${EXISTING_ID}');
    expect($githubScriptBody)->toContain('action=created');
    expect($githubScriptBody)->toContain('repos/${NWO}/issues/${NUMBER}/comments');
    // Issue #519: `gh api -f body=@-` published a comment whose body was the
    // literal string `@-` because only the typed `-F/--field` flag expands
    // `@-` to stdin. The script now builds a JSON payload via jq and feeds
    // it through `--input -`, so neither `-f body=@-` nor `-F body=@-`
    // should appear.
    expect($githubScriptBody)->not->toContain('-f body=@-');
    expect($githubScriptBody)->not->toContain('-F body=@-');
    expect($githubScriptBody)->toContain('jq -n --arg body "$BODY" \'{body:$body}\'');
    expect($githubScriptBody)->toContain('--input -');

    $jiraScriptBody = (string) file_get_contents($jiraScript);
    // Issue #695: no hidden anchor marker is appended to the JIRA comment body.
    expect($jiraScriptBody)->not->toContain('{anchor:');
    expect($jiraScriptBody)->not->toContain('ACTOR_SLUG');
    // Issue #569: the helper was written against an acli build that no longer
    // matches the installed one. Actor/site come from `acli jira auth status`
    // (no `acli jira me --json`), and comments are posted via the current
    // `comment create` subcommand (not `add` / `edit` / `update`).
    // Per user request (always-new convention): the helper no longer looks up
    // or edits prior comments — every CR run posts a fresh JIRA comment.
    expect($jiraScriptBody)->toContain('acli jira auth status');
    expect($jiraScriptBody)->not->toContain('acli jira me --json');
    expect($jiraScriptBody)->not->toContain('acli jira workitem comment update');
    expect($jiraScriptBody)->toContain('acli jira workitem comment create');
    expect($jiraScriptBody)->not->toContain('acli jira workitem comment edit');
    expect($jiraScriptBody)->not->toContain('acli jira workitem comment add');
    expect($jiraScriptBody)->not->toContain('acli jira config get');
    expect($jiraScriptBody)->toContain('acli jira workitem comment list --key "$KEY" --json --paginate');
    // The list call now runs after create to resolve the new comment id for the
    // deep-link URL; the acli exit status is still captured separately so a
    // failed re-list degrades gracefully (returns the plain issue URL, exit 0).
    expect($jiraScriptBody)->toContain('raw="$(acli jira workitem comment list --key "$KEY" --json --paginate 2>/dev/null)" || return 1');
    expect($jiraScriptBody)->toContain('if ! COMMENTS_JSON="$(list_comments)"; then');
    // Issue #695: the new comment is found by most-recent created timestamp, not by marker.
    expect($jiraScriptBody)->toContain('find_latest_id');
    expect($jiraScriptBody)->toContain('sort_by(.created');

    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');

    foreach ([$github, $jira, $prSummary] as $skill) {
        expect($skill)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
        expect($skill)->toContain('<!-- cr-comment:actor=<gh-login> -->');
    }

    expect($jira)->toContain('skills/code-review-jira/scripts/upsert-comment.sh');
    // Issue #695: anchor references removed from JIRA skill documentation.
    expect($jira)->not->toContain('{anchor:cr-comment-actor-<slug>}');
    expect($prSummary)->toContain('skills/code-review-jira/scripts/upsert-comment.sh');
    // Issue #695: anchor references removed from pr-summary skill documentation.
    expect($prSummary)->not->toContain('{anchor:cr-comment-actor-<slug>}');

    foreach ([$github, $jira] as $skill) {
        expect(stripos($skill, 'always-new comment'))->not->toBeFalse();
        expect($skill)->toContain('POSTs a new comment');
        expect($skill)->not->toContain('edit the existing comment in place');
        expect($skill)->not->toContain('Replying to code review from');
    }

    $processCodeReview = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    expect($processCodeReview)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($processCodeReview)->toContain('cr-status');
    expect($processCodeReview)->toContain('<!-- cr-status:actor=<gh-login> -->');
    // Issue #695: anchor references removed from process-code-review skill documentation.
    expect($processCodeReview)->not->toContain('{anchor:cr-status-actor-<slug>}');
    expect($processCodeReview)->not->toContain('Replying to code review from');
    expect($processCodeReview)->not->toContain('Post resolved items and status updates as a new PR comment');

    foreach ([
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
        $packageDir . '/skills/code-review/templates/review-output.md',
    ] as $template) {
        $body = (string) file_get_contents($template);
        expect($body)->toContain('**Last updated:**');
        expect($body)->not->toContain('## Previous CR Status');
    }

    // Issue #695 follow-up: review-output.md must not mention the removed JIRA
    // anchor marker or claim that follow-up runs edit the comment in place.
    $reviewOutput = (string) file_get_contents($packageDir . '/skills/code-review/templates/review-output.md');
    expect($reviewOutput)->not->toContain('{anchor:');
    expect($reviewOutput)->not->toContain('edit that comment in place');
    expect($reviewOutput)->toContain('Always-new comment');
});

test('process-code-review enforces a convergence loop with quiet iterations and a single final publish', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $process = (string) file_get_contents($packageDir . '/skills/process-code-review/SKILL.md');
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($process)->toContain('### Review loop (mandatory — convergence gate)');
    expect($process)->toContain('`maxIterations = 5`');
    expect($process)->toContain('`criticalCount + moderateCount == 0`');
    expect($process)->toContain('do not publish; return findings as in-memory markdown for this loop iteration only');
    expect($process)->toContain('### Finalization (only after Review loop converged)');
    expect($process)->toContain('### PR update (only after Review loop converged)');
    expect($process)->toContain('### Completion (final, single publish)');

    expect($github)->toContain('Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`)');
    expect($github)->toContain('skip the entire Post Results step');
    expect($jira)->toContain('Quiet mode (loop iterations from `@skills/process-code-review/SKILL.md`)');
    expect($jira)->toContain('skip all publishing');
});

test('JIRA non-technical CR summary delegates to pr-summary Wiki Markup template', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $template = (string) file_get_contents($packageDir . '/skills/pr-summary/templates/pr-summary-jira.md');
    $rule = (string) file_get_contents($packageDir . '/rules/jira/general.mdc');
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    // JIRA non-technical comment carries only "How to test" — no Summary of changes, no Authors.
    expect($template)->toContain('h2. How to test');
    expect($template)->not->toContain('h2. Summary of changes');
    expect($template)->not->toContain('## Summary of changes');
    expect($template)->not->toContain('h2. Authors');
    expect($template)->not->toContain('```');

    expect($rule)->toContain('Wiki markup conversion cheatsheet');
    expect($rule)->toContain('`{code:php} ... {code}`');
    expect($rule)->toContain('`[label|https://example.com]`');
    expect($rule)->toContain('no leaked Markdown');

    expect($skill)->toContain('Delegate the JIRA comment to `@skills/pr-summary/SKILL.md`');
    expect($skill)->toContain('@skills/pr-summary/templates/pr-summary-jira.md');
    expect(is_file($packageDir . '/skills/code-review-jira/templates/jira-output.md'))->toBeFalse();

    // JIRA report = how to test only, plus conditional clarifying questions / assignment discrepancies / critical.
    $prSummary = (string) file_get_contents($packageDir . '/skills/pr-summary/SKILL.md');
    expect($prSummary)->toContain('output **only `How to test`**');
    expect($prSummary)->toContain('No leaked markup on JIRA');
    expect($skill)->toContain('Clarifying questions block (conditional)');
    expect($skill)->toContain('only `How to test`');
    expect($skill)->toContain('no leaked Markdown');
    expect($template)->toContain('h2. Clarifying questions');
});

test('GitHub PR comment templates use a compact AI-parseable header with severity icons', function (): void {
    $packageDir = dirname(__DIR__, 2);

    foreach (['code-review-github/templates/pr-comment-output.md', 'code-review-jira/templates/github-output.md'] as $path) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $path);

        expect($content)->toContain('# Code Review');
        expect($content)->toContain('**Status:** clean / needs-fix');
        expect($content)->toContain('**Counts:** Critical {n} · Moderate {n} · Minor {n} · Refactoring {n}');
        expect($content)->toContain('### 🔴 Critical 1.');
        expect($content)->toContain('### 🟠 Moderate 1.');
        expect($content)->toContain('### 🟡 Minor 1.');
        expect($content)->toContain('- **Location:**');
        expect($content)->toContain('- **Rule:**');
        expect($content)->toContain('- **Faulty Example:**');
        expect($content)->toContain('- **Suggested fix:**');
        expect($content)->toContain('```php');
    }
});

test('code-review skill enforces strict rule compliance and architecture conformance', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Strict rule compliance (mandatory walk-through)**');
    expect($content)->toContain('scan the diff for any pattern that matches a numbered or bulleted rule');
    expect($content)->toContain('raise one finding per matched violation');
    expect($content)->toContain('**Architecture conformance (Laravel)**');
    expect($content)->toContain('section-by-section deep-dive for `@rules/laravel/architecture.mdc`');
    expect($content)->toContain('seven allowed homes including the Eloquent-model carve-out');
    expect($content)->toContain('Default severity for rule violations:');
    expect($content)->toContain('apply the **Strict rule compliance** stratification');
    expect($content)->not->toContain('Do not review formatting, linting, or trivial issues');
});

test('code review skills delegate the non-technical issue-tracker summary to pr-summary', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $github = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $jira = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $canonical = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md');

    expect($github)->toContain('#### Linked-issue consolidated summary (mandatory — single comment per linked issue)');
    expect($github)->toContain('every linked issue');
    expect($github)->toContain('closingIssues[]');
    expect($github)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($github)->toContain('plus a non-technical summary to every linked issue');
    expect($github)->toContain('issue-tracker summary status');
    expect($github)->toContain('cross-repo issue, lacking write access');
    expect($github)->toContain('@skills/pr-summary/SKILL.md');
    expect($github)->toContain('@skills/pr-summary/templates/pr-summary-github.md');

    expect($jira)->toContain('#### Linked GitHub issues (consolidated mirror — always-new comment per CR run)');
    expect($jira)->toContain('skills/code-review-github/scripts/upsert-comment.sh');
    expect($jira)->toContain('no linked GitHub issue — mirror skipped');
    expect($jira)->toContain('cross-repo issue, lacking write access');
    expect($jira)->toContain('@skills/pr-summary/SKILL.md');
    expect($jira)->toContain('@skills/pr-summary/templates/pr-summary-jira.md');

    expect($canonical)->toContain('must** delegate the **single consolidated comment on every linked issue**');
    expect($canonical)->toContain('every linked issue');
    expect($canonical)->toContain('@skills/pr-summary/SKILL.md');
});

test('every code review skill invokes assignment-compliance-check', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $wrappers = glob($packageDir . '/skills/code-review*/SKILL.md');
    assert($wrappers !== false);
    expect($wrappers)->not->toBeEmpty();

    foreach ($wrappers as $skillFile) {
        expect((string) file_get_contents($skillFile))->toContain('@skills/assignment-compliance-check/SKILL.md');
    }
});

test('every code review skill runs analyze-problem for assignment conformance', function (): void {
    $packageDir = dirname(__DIR__, 2);

    foreach (['code-review', 'code-review-github', 'code-review-jira', 'code-review-bugsnag'] as $skill) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $skill . '/SKILL.md');
        expect($content)->toContain('@skills/analyze-problem/SKILL.md');
        expect($content)->toContain('assignment conformance');
    }
});

test('code-review skill After Completion section keeps test-like-human on demand', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->not->toMatch('/##\s*After Completion[^#]*Always run @skills\/test-like-human\/SKILL\.md/s');
    expect($content)->toMatch('/##\s*After Completion[^#]*Do \*\*not\*\* auto-invoke `@skills\/test-like-human\/SKILL\.md`/s');
});

test('code-review-jira skill After Completion section keeps test-like-human on demand', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');

    expect($content)->not->toMatch('/##\s*After Completion[^#]*Always run @skills\/test-like-human\/SKILL\.md/s');
    expect($content)->toMatch('/##\s*After Completion[^#]*Do \*\*not\*\* auto-invoke `@skills\/test-like-human\/SKILL\.md`/s');
});

test('CR and resolution skills never auto-invoke test-like-human', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $forbiddenSubstrings = [
        'Always run @skills/test-like-human/SKILL.md, regardless of code review findings',
        'Run @skills/test-like-human/SKILL.md if changes are testable',
        '- Run `@skills/test-like-human/SKILL.md`',
        '2. Run `@skills/test-like-human/SKILL.md`',
    ];
    $skills = ['code-review', 'code-review-github', 'code-review-jira', 'process-code-review', 'resolve-issue'];

    foreach ($skills as $skill) {
        $content = (string) file_get_contents($packageDir . '/skills/' . $skill . '/SKILL.md');

        foreach ($forbiddenSubstrings as $needle) {
            expect($content)->not->toContain($needle);
        }
    }
});

test('every code review skill references class-refactoring skill', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $needle = '@skills/class-refactoring/SKILL.md';
    $reviewSkills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($reviewSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain($needle);
    }
});

test('code review skills constrain refactoring lens to PR diff', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $reviewSkills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($reviewSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('Refactoring & Tech Debt (DRY)');
        expect($content)->toContain('untouched code');
    }
});

test('code review templates include refactoring tech debt section', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $templates = [
        $packageDir . '/skills/code-review/templates/review-output.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($templates as $template) {
        $content = (string) file_get_contents($template);
        expect($content)->toContain('## Refactoring (DRY / tech debt)');
        expect($content)->toContain('{n} Refactoring');
    }
});

test('code review output omits empty sections instead of rendering placeholders', function (): void {
    $packageDir = dirname(__DIR__, 2);

    $templates = [
        $packageDir . '/skills/code-review/templates/review-output.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($templates as $template) {
        $content = (string) file_get_contents($template);
        expect($content)->toContain('Section visibility — render only sections that have content.');
        expect($content)->toContain('Render only when at least one Critical, Moderate, or Minor finding exists.');
        expect($content)->toContain('Render only when at least one in-scope refactoring item exists.');
        expect($content)->toContain('Render only when at least one out-of-scope structural improvement is justified by a rule.');
    }

    $skills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->toContain('**Omit empty sections entirely.**');
        // Counts line is the canonical "clean state" signal after the issue #528 follow-up — the Coverage line is no longer always rendered.
        expect($content)->toContain('the Counts line is the clean signal');
    }

    $githubSkill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    expect($githubSkill)->not->toContain('post: "No findings identified"');
});

test('github code review skills do not describe inline review comment workflow', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $githubFacingSkills = [
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-github/templates/pr-comment-output.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/code-review-jira/templates/github-output.md',
    ];

    foreach ($githubFacingSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);
        expect($content)->not->toContain('/pulls/{pr}/reviews');
        expect($content)->not->toContain('comments[]');
        expect($content)->not->toContain('event=COMMENT');
        expect($content)->not->toContain('event=REQUEST_CHANGES');
        expect($content)->not->toContain('inline review comment');
    }
});

test('code-testing rules add Test Organization clause for namespace mirroring and description match (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('## Test Organization');
    expect($content)->toContain('mirrors the namespace of the production class');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('{ClassName}{Scenario}Test.php');
    expect($content)->toContain('tests/Feature/<flow>');
    expect($content)->toContain('tests/Contract/<vendor>');
    expect($content)->toContain('tests/Integration/<area>');
    expect($content)->toContain('matches what the body actually asserts');
    expect($content)->toContain('test(\'test1\')');
    expect($content)->toContain('it(\'it works\')');
    expect($content)->toContain('test(\'happy path\')');

    expect($content)->toContain('tests/InstallerPathTest.php');
    expect($content)->not->toContain('`tests/InstallerPath.php`');
});

test('code-testing rules register the Test Organization Review Hook pointing at the code-review skill (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('## Test Organization Review Hook');
    expect($content)->toContain('@skills/code-review/SKILL.md');
});

test('code-review rule references Test Organization gate (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/code-review/general.mdc');

    expect($content)->toContain('## Test Organization');
    expect($content)->toContain('mirrors the namespace of the production class');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('matches what the body asserts');
    expect($content)->toContain('@rules/code-testing/general.mdc');
    expect($content)->toContain('@skills/code-review/SKILL.md');
});

test('code-review skill enforces Test Organization gate on every diff (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Test organization (issue #528)**');
    expect($content)->toContain('Placement mirrors the SUT namespace');
    expect($content)->toContain('File name matches the SUT');
    expect($content)->toContain('`it()` / `test()` description matches the asserted scenario');
    expect($content)->toContain('Severity: **Moderate** by default');
    expect($content)->toContain('Escalate to **Critical**');
    expect($content)->toContain('@rules/code-testing/general.mdc');

    // Suggested Fix templates must be concrete so process-code-review can extract them.
    expect($content)->toContain('**Placement / file name fix**');
    expect($content)->toContain('**Description fix**');
    expect($content)->toContain('@skills/process-code-review/SKILL.md');
    expect($content)->toContain('degrade to checking that the file sits under an intent-named directory');
});

test('create-test skill instructs creators to follow Test Organization conventions (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/create-test/SKILL.md');

    expect($content)->toContain('Place new test files per `@rules/code-testing/general.mdc` *Test Organization*');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('Name every `it()` / `test()` block to match the scenario the body asserts');
    expect($content)->toContain('test(\'test1\')');
});

test('create-missing-tests-in-pr skill instructs creators to follow Test Organization conventions (issue #528)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/create-missing-tests-in-pr/SKILL.md');

    expect($content)->toContain('Place new test files per `@rules/code-testing/general.mdc` *Test Organization*');
    expect($content)->toContain('{ClassName}Test.php');
    expect($content)->toContain('Name every `it()` / `test()` block to match the scenario the body asserts');
});

test('code-testing rule short-circuits coverage reporting when changed files are at 100% (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/code-testing/general.mdc');

    expect($content)->toContain('Coverage reporting is short by default');
    expect($content)->toContain('uncovered changed lines');
    expect($content)->toContain('coverage tooling unavailable');
    expect($content)->toContain(
        'omit the `## Coverage` section entirely, omit the `Coverage:` header line, and omit the `coverage …` slot from the final summary line',
    );
    expect($content)->toContain('The coverage check itself still runs unconditionally');
    expect($content)->not->toContain('Always report the coverage result (tool used, command, % covered for changed lines).');
});

test('core-standards Testing bullet short-circuits coverage reporting when 100% (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/php/core-standards.mdc');

    expect($content)->toContain('Report the coverage result short by default');
    expect($content)->toContain('omit the `## Coverage` section, the `Coverage:` header line, and the `coverage …` slot from the summary line');
    expect($content)->toContain('The check itself still runs unconditionally');
    expect($content)->not->toContain('Always report the coverage result; never push or finalize a change without it.');
});

test('code-review skill short-circuits coverage section in Output Rules + Coverage gate (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    // Coverage gate text mandates short-by-default reporting.
    expect($content)->toContain('**Coverage reporting is short by default.**');
    expect($content)->toContain(
        'omit the `## Coverage` section entirely, omit the `Coverage:` header line, and omit the `coverage …` slot from the final summary line',
    );

    // Output Rules opening clause no longer claims `## Coverage` is always rendered.
    expect($content)->toContain(
        'Only the header block (Status / Counts / Last updated / tracker-status line) and the final `Summary` line are always rendered.',
    );
    expect($content)->toContain('all conditional');
    // The old "always render Coverage" sentence must be gone — verify by checking a distinctive fragment that only existed in the legacy sentence.
    expect($content)->not->toContain('Counts / Coverage / Last updated / tracker-status line');
});

test('code-review-github skill + template short-circuit coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-github/templates/pr-comment-output.md');

    expect($skill)->toContain('Only the header block (Status / Counts / Last updated / Issue tracker summary)');
    expect($skill)->toContain('the final `Summary` line are always rendered in the PR comment.');
    expect($skill)->toContain('all conditional');
    expect($skill)->toContain('includes a `## Coverage` section before the summary line **only** when the coverage gate has something to report');
    expect($skill)->not->toContain('Counts / Coverage / Issue tracker summary');

    expect($template)->toContain('are conditional');
    expect($template)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($template)->toContain('omitted on a clean 100% pass');
});

test('code-review-jira skill + template short-circuit coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-jira/templates/github-output.md');

    expect($skill)->toContain('Only the header block (Status / Counts / Last updated / Linked-tracker mirror)');
    expect($skill)->toContain('the final `Summary` line are always rendered in the GitHub PR comment.');
    expect($skill)->toContain('all conditional');
    expect($skill)->toContain('includes a `## Coverage` section before the summary line **only** when the coverage gate has something to report');

    expect($template)->toContain('are conditional');
    expect($template)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($template)->toContain('omitted on a clean 100% pass');
});

test('CR base review-output template short-circuits coverage section (issue #528 follow-up)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/templates/review-output.md');

    expect($content)->toContain('are conditional');
    expect($content)->toContain('Render this section **only** when the coverage gate produced something to report');
    expect($content)->toContain('omitted on a clean 100% pass');
});

test('code-review skill mandates a standalone Laravel architecture walk on every CR run (issue #530)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Architecture conformance (Laravel)** — mandatory standalone walk-through (issue #530)');
    expect($content)->toContain('independent of Strict rule compliance');
    expect($content)->toContain('section-by-section deep-dive for `@rules/laravel/architecture.mdc`');
    expect($content)->toContain('Walk every section of that file against the current diff **regardless of which files the diff touches**');
    expect($content)->toContain('helpers, routes, configs, migrations, seeders, tests, or even a docs-only commit');
    expect($content)->toContain('seven allowed homes including the Eloquent-model carve-out');
    expect($content)->toContain('Actions / Model Services / Repositories / ModelManagers / Data Validators / Data Builders / Eloquent models');
    expect($content)->toContain('arch-app-services examples (when installed)');
    expect($content)->toContain('https://github.com/pekral/arch-app-services/blob/master/README.md');
    expect($content)->toContain('When the package is **not** installed, ignore this README cross-check');
    expect($content)->toContain('published CR comment carries a `## Architecture` section **only when the walk produces at least one finding**');
    expect($content)->toContain('omit the `## Architecture` heading entirely — never render a "walked, 0 findings" status line');
    expect($content)->toContain(
        'On **non-Laravel projects** (no `laravel/framework` in `composer.json` `require`), skip the walk entirely and omit the `## Architecture` section',
    );
});

test('code-review Output Rules carry the Architecture section conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('`## Architecture` section (issue #530)');
    expect($content)->toContain('the `## Architecture` heading is rendered **only when the walk produces at least one finding**');
    expect($content)->toContain('omit the heading entirely — never render a `walked, 0 findings` status line');
    expect($content)->toContain('the `## Architecture` section is omitted entirely');
});

test('code-review canonical template renders the Laravel Architecture section conditionally (issue #530)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $template = (string) file_get_contents($packageDir . '/skills/code-review/templates/review-output.md');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->toContain('omit the entire `## Architecture` heading and body');
    expect($template)->toContain('Architecture conformance (Laravel) — mandatory standalone walk-through');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('code-review-github Output Rules and template carry the Architecture conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-github/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-github/templates/pr-comment-output.md');

    expect($skill)->toContain('`## Architecture` section (issue #530)');
    expect($skill)->toContain('only when the walk produces at least one finding');
    expect($skill)->toContain('never render a `walked, 0 findings` status line');
    expect($skill)->toContain('On non-Laravel projects, omit the `## Architecture` section entirely');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('code-review-jira Output Rules and GitHub template carry the Architecture conditional rendering rule (issue #530)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $skill = (string) file_get_contents($packageDir . '/skills/code-review-jira/SKILL.md');
    $template = (string) file_get_contents($packageDir . '/skills/code-review-jira/templates/github-output.md');

    expect($skill)->toContain('`## Architecture` section (issue #530)');
    expect($skill)->toContain('only when the walk produces at least one finding');
    expect($skill)->toContain('never render a `walked, 0 findings` status line');
    expect($skill)->toContain('The JIRA non-technical comment (produced by `pr-summary`) never includes this section');

    expect($template)->toContain('## Architecture');
    expect($template)->toContain('**Laravel-only, conditional on findings (issue #530)');
    expect($template)->toContain('only when the walk produces at least one finding');
    expect($template)->not->toContain('Status: walked, 0 findings');

    $architectureHeading = strpos($template, "\n## Architecture\n");
    $coverageHeading = strpos($template, "\n## Coverage\n");

    expect($architectureHeading)->not->toBeFalse();
    expect($coverageHeading)->not->toBeFalse();
    assert($architectureHeading !== false);
    assert($coverageHeading !== false);
    expect($architectureHeading)->toBeLessThan($coverageHeading);
});

test('code-review skill adds Shared Concerns (Traits) to the mandatory architecture walk (issue #531)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Shared Concerns (Traits)** (globally shared, domain-agnostic, reusable-as-is logic only');
    expect($content)->toContain('flag domain-specific code parked under `app/Concerns/`');
    expect($content)->toContain('reusable trait logic scattered outside `app/Concerns/`');
});

test('code-review skill verifies every Critical finding via analyze-problem before publishing (issue #537)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('### Critical Findings Verification (issue #537)');
    expect($content)->toContain('Walk every **Critical** finding aggregated within this skill\'s run through `@skills/analyze-problem/SKILL.md`');
    expect($content)->toContain('invoke `@skills/analyze-problem/SKILL.md` **inline in this skill\'s context** (do not dispatch as a subagent)');
    expect($content)->toContain(
        '**Confirmed** — Verified Facts and Probable Root Cause back the finding → keep the Critical finding verbatim in the report',
    );
    expect($content)->toContain('**Refuted** — Verified Facts contradict the finding');
    expect($content)->toContain('**Never silently downgrade** a Critical to Moderate or Minor on the basis of this verification');
    expect($content)->toContain('**Moderate and Minor findings are not subject to this verification**');
});

test('code review enforces translatable UI, console, and API strings (issue #553)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('Translation completeness (mandatory when the project ships translations)');
    expect($content)->toContain('@rules/laravel/laravel.mdc` **Localization and Translatable Strings**');
    expect($content)->toContain('**Console** (human-readable Artisan command output');
    expect($content)->toContain('**API** (JSON `message` fields');
});

test('code review enforces test isolation against real HTTP and system processes (issue #553)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('Test isolation — no real HTTP, no real system processes');
    expect($content)->toContain('**Real outbound HTTP**');
    expect($content)->toContain('**Real system process / external binary or script**');
    expect($content)->toContain('A test must never invoke an external binary or script directly on the system');
    expect($content)->toContain('Http::fake()');
    expect($content)->toContain('Process::fake()');
});

test('code-review wires the API rule and api-review skill into every CR run (issue #552)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('- Apply @rules/api/general.mdc');
    expect($content)->toContain('@skills/api-review/SKILL.md');
    expect($content)->toContain('`@rules/php/core-standards.mdc`, `@rules/api/general.mdc`, `@rules/code-review/general.mdc`');
});

test('code-review skill flags request->DTO transformation called directly in the controller body (issue #698)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Request → DTO transformation belongs in the FormRequest, not the controller**');
    expect($content)->toContain('`$request->toDto()`');
    expect($content)->toContain('Severity: **Moderate**');
    expect($content)->toContain('`@rules/laravel/architecture.mdc` Controllers and Other Entry Points');
});

test(
    'code-review skill enforces acceptance-criteria use-case coverage and test business logic in Assignment Conformance Gate (issue #708)',
    function (): void {
        $packageDir = dirname(__DIR__, 2);
        $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
            $packageDir . '/rules/code-review/general.mdc',
        );
    
        // Acceptance-criteria use-case coverage bullet in the Validation section
        expect($content)->toContain('**Acceptance-criteria use-case coverage (mandatory):**');
        expect($content)->toContain('at least one automated test exists whose description and assertions directly target that criterion or scenario');
        expect($content)->toContain('Any acceptance criterion without a dedicated use-case test is a **Critical** finding');
    
        // Testing logic verified in Requirements → changes (completeness) direction
        expect($content)->toContain('including the **testing logic**');
        expect($content)->toContain('tests added or modified by the diff must themselves assert the correct, assignment-required behavior');
        expect($content)->toContain('Any unmet requirement (in production code or in test logic) is already a **Critical** finding raised there');
    },
);

test('code-review skill flags enum-mode match() in Data Validator bullet and New storage reuse analysis (issue #708)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    // enum-mode match() added to the inline validation guards bullet
    expect($content)->toContain('enum-mode `match()` belong in a Data Validator');
    expect($content)->toContain('ContactChangeDataValidator::evaluate(ContactChangeCondition $condition, ChangeModel $change): bool');
    expect($content)->toContain('Applies only when `pekral/arch-app-services` is installed');

    // New storage reuse analysis bullet
    expect($content)->toContain('**New storage reuse analysis**');
    expect($content)->toContain('Schema::create(...)');
    expect($content)->toContain('Can this data be stored in an existing storage without a drastic impact on performance?');
    expect($content)->toContain('Severity: **Moderate** (see `@rules/sql/optimalize.mdc` *New storage reuse analysis*)');
});
