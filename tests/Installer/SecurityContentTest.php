<?php

declare(strict_types = 1);

test('security/backend.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/backend.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('**No identity / account enumeration.**');
    expect($content)->toContain('Invalid credentials.');
    expect($content)->toContain('If the account exists, we sent the reset link.');
    expect($content)->toContain('**No authorization granularity leaks.**');
    expect($content)->toContain('**No internal implementation detail.**');
    expect($content)->toContain('**No verbatim echo of attacker input.**');
    expect($content)->toContain('**No password / token policy leak beyond the stated rule.**');
    expect($content)->toContain('**No timing or shape side channels.**');
    expect($content)->toContain('**Translations carry the same contract.**');
    expect($content)->toContain('**Specificity stays on the safe surfaces.**');
});

test('security/frontend.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/frontend.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**Mirror the backend wording.**');
    expect($content)->toContain('**Do not pre-flight existence on the client.**');
    expect($content)->toContain('**Never inject attacker input into the message DOM unescaped.**');
    expect($content)->toContain('**Strip stack traces and SDK errors before display.**');
    expect($content)->toContain('**Translation parity.**');
});

test('security/mobile.md carries the Safe Validation & Error Messages section (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/mobile.md');

    expect($content)->toContain('## Safe Validation & Error Messages (issue #540)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**No native crash dialogs surfaced to the user.**');
    expect($content)->toContain('**WebView error pages must stay generic.**');
    expect($content)->toContain('**Logs / debug overlays are not user-facing channels.**');
    expect($content)->toContain('**Translation parity.**');
});

test('code-review skill enforces Safe validation & error texts on every diff (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Safe validation & error texts (issue #540):**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('Identity / account enumeration on auth, password-reset, sign-up, change-email, or account-lookup flows');
    expect($content)->toContain('Authorization granularity leak');
    expect($content)->toContain('Internal implementation detail in the response body');
    expect($content)->toContain('Verbatim echo of attacker input');
    expect($content)->toContain('Password / token policy leak beyond the stated rule');
    expect($content)->toContain('Translation drift');
    expect($content)->toContain('Severity: **Critical** when the unsafe wording sits on an auth / password-reset / sign-up / authorization surface');
});

test('security-review skill audits safe validation & error texts across locales (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/security-review/SKILL.md');

    expect($content)->toContain('**safe validation & error texts (issue #540)**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('across every locale shipped by the project');
    expect($content)->toContain('directly exploitable for enumeration');
});

test('resolve-issue skill references Safe Validation & Error Messages rule (issue #540)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('Safe Validation & Error Messages');
    expect($content)->toContain('including every locale shipped by the project');
});

test('security/backend.md carries the Malicious Code & Supply-Chain Indicators section (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/backend.md');

    expect($content)->toContain('## Malicious Code & Supply-Chain Indicators (issue #549)');
    expect($content)->toContain('**Silent remote fetch ("tichý curl").**');
    expect($content)->toContain('**Disabled TLS validation ("ignorování TLS validace").**');
    expect($content)->toContain('**Suppressed error output ("potlačení chybového výstupu").**');
    expect($content)->toContain('**Hidden file + detached background process ("skrytý soubor v /tmp a spuštění procesu na pozadí").**');
    expect($content)->toContain('CURLOPT_SSL_VERIFYPEER => false');
});

test('security/frontend.md carries the Malicious Code & Supply-Chain Indicators section (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/frontend.md');

    expect($content)->toContain('## Malicious Code & Supply-Chain Indicators (issue #549)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('NODE_TLS_REJECT_UNAUTHORIZED=0');
    expect($content)->toContain('**Silent remote fetch piped to execution.**');
    expect($content)->toContain('**Swallowed errors hiding network calls.**');
});

test('security/mobile.md carries the Malicious Code & Supply-Chain Indicators section (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/mobile.md');

    expect($content)->toContain('## Malicious Code & Supply-Chain Indicators (issue #549)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**Disabled TLS / certificate validation.**');
    expect($content)->toContain('**Silent download + background execution.**');
    expect($content)->toContain('**Suppressed errors on security operations.**');
});

test('security-review skill audits malicious code & supply-chain indicators (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/security-review/SKILL.md');

    expect($content)->toContain('### Malicious Code & Supply-Chain Indicators (issue #549)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**Silent remote fetch**');
    expect($content)->toContain('**Disabled TLS validation**');
    expect($content)->toContain('**Suppressed error output**');
    expect($content)->toContain('**Hidden file + detached background process**');
});

test('code-review skill flags malicious code & supply-chain indicators on every diff (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Malicious code & supply-chain indicators (issue #549):**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('**Silent remote fetch**');
    expect($content)->toContain('**Disabled TLS validation**');
    expect($content)->toContain('**Suppressed error output**');
    expect($content)->toContain('**Hidden file + detached background process**');
});

test('resolve-issue skill references Malicious Code & Supply-Chain Indicators rule (issue #549)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/resolve-issue/SKILL.md');

    expect($content)->toContain('*Malicious Code & Supply-Chain Indicators* (issue #549)');
    expect($content)->toContain('NODE_TLS_REJECT_UNAUTHORIZED=0');
});

test('security/backend.md carries the Malicious File Upload Content section (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/backend.md');

    expect($content)->toContain('## Malicious File Upload Content (issue #680)');
    expect($content)->toContain('**Stored XSS from file content.**');
    expect($content)->toContain('**SVG with active content served inline.**');
    expect($content)->toContain('**CSV / Excel formula injection.**');
    expect($content)->toContain('**HTML / JavaScript in filenames and metadata.**');
    expect($content)->toContain('**Polyglot files.**');
    expect($content)->toContain('**Missing `Content-Disposition` / `nosniff` on upload serving endpoints.**');
    expect($content)->toContain('raise one finding per violation, never both');
    expect($content)->toContain('CONTENT / RENDER');
    expect($content)->toContain('TYPE / TRANSPORT');
});

test('security/frontend.md carries the Malicious File Upload Content section (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/frontend.md');

    expect($content)->toContain('## Malicious File Upload Content (issue #680)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('raise one finding per violation, never both');
    expect($content)->toContain('**Never use `innerHTML` for filenames or file content.**');
    expect($content)->toContain('**SVG uploads must not be rendered inline.**');
    expect($content)->toContain('**Do not trust the client-supplied MIME type.**');
    expect($content)->toContain('**Previewing file content in the browser.**');
});

test('security/mobile.md carries the Malicious File Upload Content section (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/rules/security/mobile.md');

    expect($content)->toContain('## Malicious File Upload Content (issue #680)');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('raise one finding per violation, never both');
    expect($content)->toContain('**WebView must not render user-uploaded HTML or SVG without sanitization.**');
    expect($content)->toContain('**Shared / opened files must be validated.**');
    expect($content)->toContain('**Do not render filenames or metadata into HTML contexts.**');
});

test('code-review skill flags malicious file upload content on every diff (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/code-review/SKILL.md') . "\n" . (string) file_get_contents(
        $packageDir . '/rules/code-review/general.mdc',
    );

    expect($content)->toContain('**Malicious file upload content (issue #680):**');
    expect($content)->toContain('@rules/security/backend.md');
    expect($content)->toContain('raise one finding per violation, never both');
    expect($content)->toContain('**Stored XSS from file content**');
    expect($content)->toContain('**SVG with active content served inline**');
    expect($content)->toContain('**CSV / Excel formula injection**');
    expect($content)->toContain('**HTML / JavaScript in filenames or metadata**');
    expect($content)->toContain('**Polyglot files served from application origin**');
    expect($content)->toContain('**Missing `Content-Disposition` / `nosniff` on upload-serving endpoint**');
});

test('security-review skill distinguishes TYPE/TRANSPORT from CONTENT/RENDER for file uploads (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/security-review/SKILL.md');

    expect($content)->toContain('TYPE / TRANSPORT');
    expect($content)->toContain('CONTENT / RENDER');
    expect($content)->toContain('raise one finding per violation, never both');
    expect($content)->toContain('Malicious File Upload Content (issue #680)');
});

test('malicious-uploads dataset exists with README and all six payload categories (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $datasetDir = $packageDir . '/skills/security-review/datasets/malicious-uploads';

    expect(is_dir($datasetDir))->toBeTrue();
    expect(file_exists($datasetDir . '/README.md'))->toBeTrue();
    expect(is_dir($datasetDir . '/stored-xss'))->toBeTrue();
    expect(is_dir($datasetDir . '/svg'))->toBeTrue();
    expect(is_dir($datasetDir . '/csv-formula-injection'))->toBeTrue();
    expect(is_dir($datasetDir . '/filename-metadata'))->toBeTrue();
    expect(is_dir($datasetDir . '/polyglot'))->toBeTrue();
    expect(is_dir($datasetDir . '/mime-double-extension'))->toBeTrue();

    $readme = (string) file_get_contents($datasetDir . '/README.md');
    expect($readme)->toContain('INERT');
    expect($readme)->toContain('inert test fixtures');
    expect($readme)->toContain('never executed');
});

test('no dataset file in malicious-uploads/ contains a PHP open tag (issue #680)', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $datasetDir = $packageDir . '/skills/security-review/datasets/malicious-uploads';

    $paths = array_values(array_filter(
        (array) glob($datasetDir . '/**/*', GLOB_NOSORT),
        static fn (mixed $p): bool => is_string($p) && is_file($p),
    ));

    foreach ($paths as $path) {
        $content = (string) file_get_contents($path);
        expect($content)->not->toContain(
            '<?php',
            sprintf('Dataset fixture %s must not contain a PHP open tag — keep fixtures inert plain text.', basename($path)),
        );
    }
});

test('security-bounty-hunter keeps tooling optional and stays distinct from the review skills', function (): void {
    $packageDir = dirname(__DIR__, 2);
    $content = (string) file_get_contents($packageDir . '/skills/security-bounty-hunter/SKILL.md');

    expect($content)->toContain('hunts unknown exploitable bugs');
    expect($content)->toContain('@skills/security-review/SKILL.md');
    expect($content)->toContain('@skills/security-threat-analysis/SKILL.md');
    // Static tooling is triage input only, never a hard dependency the package would have to bundle.
    expect($content)->toContain('optional');
});
