# Finding Deduplication Policy

## Purpose

Avoid reporting findings that have already been reported in prior review cycles on the same PR.

## Procedure

1. **Collect prior reviews** — Before writing findings, gather all previous review comments and reports from the PR timeline and related issue discussion.
2. **Build a dedup list** — Index prior findings by problem signature: `file/scope + root cause + risk`.
3. **Compare new findings** — For each new finding, check whether a matching signature exists in the dedup list.
4. **Skip duplicates** — If a finding was already reported and its severity/impact has not changed, do not report it again.
5. **Re-report if changed** — If the severity or impact of a previously reported finding has changed (e.g., the fix attempt introduced a new variant), report it with updated context.

## Problem signature format

A problem signature consists of:
- **File/scope** — The file path and function/class/block where the issue occurs
- **Root cause** — The underlying reason for the issue (e.g., "missing null check", "unbounded query")
- **Risk** — The consequence if unfixed (e.g., "NullPointerException in production", "memory exhaustion")

Two findings match if all three components are semantically equivalent, even if the exact wording differs.
