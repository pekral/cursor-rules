# Malicious Upload Payload Dataset (issue #680)

## Purpose

This directory contains **inert test fixtures** representing malicious file-upload payloads. **INERT** means they are data only — they must never be executed, rendered in a browser, opened in a spreadsheet application, or served by the repository itself. They are used when writing tests for file-upload security controls, to verify that application code correctly sanitizes, rejects, or safely serves user-uploaded content.

These files exist as data — they are never executed, rendered, or served by the repository itself. They are distributed as part of the `laravel-agent-skills` package so that project teams can reference them when writing security test cases.

## Warning

**Do not open, render, or execute these files in a browser or spreadsheet application without a controlled environment.** The payloads are designed to trigger vulnerabilities (XSS, formula injection, SVG script execution, polyglot content sniffing) in applications that do not properly sanitize uploaded files. They are safe to read as raw bytes or text.

## How to use in tests

Load a fixture file as raw bytes and pass it as the uploaded file body to your application's upload endpoint or sanitization function. Assert that the application rejects the file, strips the malicious content, or serves it with the correct headers.

Example (PHP / Pest):

```php
it('strips script tags from uploaded SVG content', function (): void {
    $fixture = file_get_contents(__DIR__ . '/../../skills/security-review/datasets/malicious-uploads/svg/script-onload.svg');
    // submit $fixture to your upload handler and assert the output is sanitized
    expect($sanitizedOutput)->not->toContain('<script');
    expect($sanitizedOutput)->not->toContain('onload=');
});
```

## Categories

| Directory | Threat | Rule reference |
|---|---|---|
| `stored-xss/` | Stored XSS from file content rendered as HTML | backend.md #680 — Stored XSS from file content |
| `svg/` | SVG with active content (`<script>`, `onload`, `<foreignObject>`) | backend.md #680 — SVG with active content served inline |
| `csv-formula-injection/` | CSV / Excel formula injection (`=`, `+`, `-`, `@`, tab/CR prefix) | backend.md #680 — CSV / Excel formula injection |
| `filename-metadata/` | HTML / JS in filenames and EXIF / PDF metadata | backend.md #680 — HTML / JavaScript in filenames and metadata |
| `polyglot/` | Polyglot files (valid image header + HTML/JS payload) | backend.md #680 — Polyglot files |
| `mime-double-extension/` | Double extension and MIME mismatch (tests both TYPE/TRANSPORT and CONTENT/RENDER layers) | security-review/SKILL.md File Handling + backend.md #680 |

## Scope note

The payloads in `mime-double-extension/` sit on the boundary between the TYPE / TRANSPORT layer (`security-review/SKILL.md` File Handling) and the CONTENT / RENDER layer (`rules/security/backend.md` *Malicious File Upload Content (issue #680)*). A single upload sink failing both surfaces should produce one finding per layer (accept decision and output decision), never two findings for the same line. See the gating rule in both skill files.
