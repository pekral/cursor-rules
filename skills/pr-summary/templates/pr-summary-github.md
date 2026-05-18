**Authors:** [@github-handle-1, @github-handle-2 — or `Name <email>` when no GitHub handle is known, comma-separated in commit order; never the agent / CR identity]
**Available behind:** [optional — present only when the change is reachable only behind a test parameter; name the toggle (e.g. `config('feature.new_pricing')`, ENV `BETA_PRICING=1`, query `?preview=1`, admin switch *New pricing preview*) and the value required to reach it; omit this line entirely when the change is reachable unconditionally]

## Summary of changes

**[Short headline naming the change — one line]**

[One paragraph (3–5 sentences) in plain language explaining the business reason, the affected area, and just enough technical context (integration, payload, table, endpoint, …) that a developer can locate the change without reading the diff. Phrase impersonally — "The change …", "This update …" — never first person.]

## How to test

1. [If *Available behind* is set, this step **must** enable the toggle / supply the parameter / switch the admin flag — naming the exact value]
2. [Next action a tester performs]
3. [Outcome the tester verifies]
