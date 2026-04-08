# Correlation Criteria

## Purpose

Confirm that a DB row corresponds to the same request shown in the Telescope UI. Never assume a match without explicit criteria.

## Match fields

Use as many of these fields as available to establish correlation:

| Field | Source (UI) | Source (DB) | Match type |
|---|---|---|---|
| UUID | Entry detail page | `telescope_entries.uuid` | Exact |
| Timestamp | Request timestamp | `telescope_entries.created_at` | Proximity (within seconds) |
| Method + URI | Request summary | `content` JSON: `method`, `uri` | Exact |
| Status code | Response status | `content` JSON: `response_status` | Exact |
| Tags | Tag list | `telescope_entries_tags.tag` | Set match |
| Family hash | Related entries grouping | `telescope_entries.family_hash` | Exact |
| Child entries | Related tabs (queries, jobs, etc.) | Entries with same `batch_id` or `family_hash` | Count + type match |

## Confidence levels

| Level | Criteria |
|---|---|
| **High** | UUID matches exactly, or 3+ fields match with no contradictions |
| **Medium** | 2 fields match, no contradictions, but UUID was not directly verified |
| **Low** | Only timestamp proximity and type match; other fields unavailable |

## Handling ambiguity

- If correlation is ambiguous, **explicitly state what is missing** in the report.
- Do not claim DB correlation without listing the match criteria used.
- If multiple DB rows could match, list all candidates and explain why one is preferred.
