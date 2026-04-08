# Quality Checks

## Verbatim Section Integrity

- The original assignment text in the "Původní zadání" section MUST be byte-identical to the source
- Do not fix typos, grammar, or formatting in the verbatim section
- If the original text contains markdown, preserve it exactly

## PR Comment Coverage

- Every unresolved PR review comment must map to at least one item in "Požadavky pro implementaci"
- Already-resolved or duplicate requests must be excluded
- If a comment is ambiguous, include it with a note rather than silently dropping it

## Acceptance Criteria Validation

- Each acceptance criterion must be testable — it should be possible to verify pass/fail
- Avoid vague criteria like "works correctly" or "is fast enough"
- Each criterion should map to at least one implementation requirement

## Attachment Verification

- If the PR or assignment references attachments (images, documents, files), verify they were downloaded and analyzed
- Include attachment impact in the technical context section
- If an attachment cannot be retrieved, note this explicitly in the output
