<?php

declare(strict_types = 1);

test('reports/general.mdc rule ships in the package and declares the canonical language statement', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $rulePath = $packageDir . '/rules/reports/general.mdc';

    expect(is_file($rulePath))->toBeTrue();

    $content = (string) file_get_contents($rulePath);

    expect($content)->toContain('Tracker-Published Reports — Language');
    expect($content)->toContain('same language as the source assignment');
    expect($content)->toContain('Czech');
    expect($content)->toContain('Code identifiers stay verbatim');
    expect($content)->toContain('@rules/git/general.mdc');
});

test('every tracker-publishing skill references @rules/reports/general.mdc', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $trackerPublishingSkills = [
        $packageDir . '/skills/pr-summary/SKILL.md',
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
        $packageDir . '/skills/assignment-compliance-check/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
        $packageDir . '/skills/test-like-human/SKILL.md',
        $packageDir . '/skills/tester-cookbook/SKILL.md',
        $packageDir . '/skills/prepare-issue-context/SKILL.md',
    ];

    foreach ($trackerPublishingSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        $hasReference = str_contains($content, '@rules/reports/general.mdc');

        expect($hasReference)->toBeTrue($skillFile . ' must reference the shared tracker-report language rule (@rules/reports/general.mdc)');
    }
});

test('no tracker-publishing skill still carries the obsolete "must be in English" constraint', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $forbiddenPatterns = [
        '/^[-*]\s*All output must be in English\s*$/m',
        '/^[-*]\s*All output posted to GitHub must be in English\s*$/m',
        '/^[-*]\s*GitHub output must be in English\s*$/m',
        '/^[-*]\s*All CR output must be written in English\s*$/m',
        '/^[-*]\s*Output must be in English\s*$/m',
    ];
    $skills = [
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
    ];

    foreach ($skills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        foreach ($forbiddenPatterns as $pattern) {
            expect((bool) preg_match($pattern, $content))->toBeFalse(
                $skillFile . ' still carries an obsolete English-only constraint matching ' . $pattern,
            );
        }
    }
});

test('readme rules overview lists the reports/general.mdc rule', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $readme = (string) file_get_contents($packageDir . '/README.md');

    expect($readme)->toContain('`reports/general.mdc`');
    expect($readme)->toContain('Language rule for reports published to issue trackers');
});

test('reports/general.mdc declares the GitHub-PR technical-CR English exception', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/reports/general.mdc');

    expect($content)->toContain('Exception — technical CR findings on the GitHub PR');
    expect($content)->toContain('canonical English');
    expect($content)->toContain('@skills/code-review-github/SKILL.md');
    expect($content)->toContain('@skills/process-code-review/SKILL.md');
    expect($content)->toContain('exception does **not** extend to');
    expect($content)->toContain('pr-summary');
});

test('reports/general.mdc bans bilingual parentheses and mid-comment language mixing', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/reports/general.mdc');

    expect($content)->toContain('never mix that language with another natural language');
    expect($content)->toContain('No bilingual parentheses');
    expect($content)->toContain('Kritické (Critical)');
    expect((bool) preg_match('/use the Czech equivalents \(e\.g\. \*Kritické\*, \*Závažné\*, \*Drobné\*\)/', $content))->toBeFalse();
});

test('CR wrapper skills carry the GitHub-PR English exception in their constraints', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $crWrapperSkills = [
        $packageDir . '/skills/code-review-github/SKILL.md',
        $packageDir . '/skills/code-review-jira/SKILL.md',
        $packageDir . '/skills/code-review/SKILL.md',
        $packageDir . '/skills/process-code-review/SKILL.md',
        $packageDir . '/skills/security-review/SKILL.md',
        $packageDir . '/skills/security-threat-analysis/SKILL.md',
        $packageDir . '/skills/resolve-issue/SKILL.md',
    ];

    foreach ($crWrapperSkills as $skillFile) {
        $content = (string) file_get_contents($skillFile);

        $namesException = str_contains($content, 'Exception — technical CR findings on the GitHub PR');
        $mentionsCanonicalEnglish = str_contains($content, 'canonical English');

        expect($namesException && $mentionsCanonicalEnglish)->toBeTrue(
            $skillFile . ' must cite the GitHub-PR technical-CR English exception from @rules/reports/general.mdc',
        );
    }
});
