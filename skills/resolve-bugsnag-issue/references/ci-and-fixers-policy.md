# CI and Fixers Policy

## Automatic Fixers

- If there are any automatic fixers in the project that are called through another layer (such as Phing or composer scripts), run them and ensure automatic error correction.
- Find and load local configs for tools if they exist.
- Never try to format PHP code outside of these fixers yourself.

## CI Checkers

- If there are any CI (or local) checkers, run them.
- **Never run all tests for the entire codebase** — only run tests for the current changes.
- Fix any errors, run the fixers again, and keep fixing until all errors are fixed.

## Iteration

The fixer/checker cycle is:
1. Run fixers.
2. Run checkers for changed files only.
3. If errors remain, fix them.
4. Repeat from step 1 until clean.
