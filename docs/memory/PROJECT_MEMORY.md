# Project memory — cursor-rules

### auto-mode-external-write-blocked — Post-convergence publication to linked issue silently blocked in auto-mode environments

- Trigger: an agent (e.g. `argos`, `apollon`) attempts to publish a comment to a GitHub issue or JIRA ticket (the source tracker) in an environment where auto-mode write classification is active — typically when `pr-summary` or `upsert-comment.sh` targets a linked issue rather than the PR itself.
- Rule:    In auto-mode environments the external-write classifier may block the publication silently — no error is thrown, the comment simply never appears. This affects `pr-summary` linked-issue mirrors and any post-convergence feedback step that targets the originating tracker item. Always verify that the publication step actually posted (check the tracker URL, not just the agent handoff). If blocked: publish manually, or re-run with explicit linked-issue write permission enabled. Document the outcome in the agent handoff (`Blocked: external-write blocked by auto-mode classifier`) so the next human knows a manual step is required.
- Example: `argos` post-convergence `pr-summary` mirror on issue #629 was blocked — the handoff noted `status: failed to post on issue #629: external-write blocked by auto-mode classifier`; the comment was posted manually to the PR instead. The netechnical summary on #629 required a separate manual action.
- Source:  https://github.com/pekral/cursor-rules/pull/636   Added: 2026-06-20

### agent-file-vs-registration — Adding agents/<name>.md does not make the agent dispatchable

- Trigger: a daidalos run tries to dispatch a newly documented agent (e.g. `apollon`) via the Task tool, or any orchestrator step assumes a new `agents/<name>.md` entry is immediately executable as a subagent.
- Rule:    An `agents/<name>.md` file is documentation only. For an agent to be dispatchable in Claude tooling the agent type must also be installed/registered (the installer syncs copies into `.claude/`). Until that happens, the agent cannot be spawned and the orchestrator must fall back to available registered agents or treat the step as blocked. Document the dependency explicitly in the agent's own file and in the issue that introduces the agent.
- Example: `agents/apollon.md` was added in #628; daidalos correctly noted "agent type `apollon` is not registered in this environment" and continued with `metis / talos / argos`. The push-level gate becomes effective only after `apollon` is installed.
- Source:  https://github.com/pekral/cursor-rules/pull/633   Added: 2026-06-20

### parallel-agent-publication-contract — Parallel-dispatched agents must route findings through the shared brief, not publish directly

- Trigger: a new CR / security / review agent is introduced that daidalos dispatches in parallel with an existing agent (e.g. `athena` alongside `argos`); the new agent's output step uses raw `gh pr comment` / `gh issue comment` to publish its findings.
- Rule:    Any agent dispatched in parallel must hand off findings via the shared task brief so the consolidating agent (e.g. `argos`) can merge and publish them as a single report. Direct publication is permitted only in standalone mode (no parallel dispatch), and even then must go through the canonical `upsert-comment.sh` wrapper — never raw `gh pr comment` or `gh issue comment`. Writing raw comment commands in a parallel-dispatch context breaks the consolidation contract and produces duplicate / uncoordinated comment threads.
- Example: `agents/athena.md` step 5 originally used `gh pr comment` to publish directly; argos flagged this as Moderate in PR #638 (commit `82abc16`); fixed to hand off via shared brief when dispatched with argos, and use `upsert-comment.sh` in standalone mode.
- Source:  https://github.com/pekral/cursor-rules/pull/638   Added: 2026-06-20

### agent-new-mode-status-result-parity — Adding a new run-mode to an agent requires extending both Status and Result in the handoff section

- Trigger: a new run-mode or output branch is added to an agent definition (e.g. a "Decomposition done" path that returns before a PR is opened); the author updates `Result:` with the new value but leaves the `Status:` line unchanged.
- Rule:    Every new run-mode that produces a distinct output must appear in **both** the `Status:` line **and** the `Result:` list in the agent's *Output — handoff* section, and must be consistent with the status values defined in every cross-file peer (e.g. `daidalos.md` ↔ `metis.md`). Update all affected files atomically in the same commit; a missing `Status` value for a new mode is an incomplete contract that the CR loop will flag as Moderate.
- Example: `agents/daidalos.md` *Output — handoff* `Status` line omitted `Decomposition done` while `agents/metis.md` defined it and issue #639 step 4 required it; argos caught this as Moderate in iteration 1 of PR #640 (fix commit `392203d`).
- Source:  https://github.com/pekral/cursor-rules/pull/640   Added: 2026-06-20

### cr-rule-severity-collision — Adding a new detection rule for an antipattern already covered by an existing (gated) rule with a different severity creates a non-deterministic severity conflict

- Trigger: a PR adds a new detection bullet (e.g. Moderate) to `skills/code-review/SKILL.md` or a `rules/**` file for an antipattern that an existing bullet already covers at a different severity (e.g. Critical), typically gated on an optional package; no dedup/gating clause is present.
- Rule:    Apply the canonical dedup/gating pattern from `skills/code-review/SKILL.md` "Inline validation guards" — "raise one finding per violation, never both". Gate the two bullets with mutually exclusive conditions (e.g. package installed → Critical; package absent → Moderate; never both). Add the gating clause to every file that carries either half of the conflicting pair so the severity is deterministic regardless of project context.
- Example: PR #646 added a Moderate bullet for inline Eloquent chains in `skills/code-review/SKILL.md` (line ~115) and a Moderate entry in `rules/laravel/architecture.mdc` (line ~279) without gating; existing Critical bullets in the same files covered the same surface. `composer build` stayed green, but argos flagged a Moderate severity-collision in iteration 1. Fix commit `2b1ebe4` added symmetric gating clauses ("without the package raise a Moderate … never both" / "when the package is installed the Critical rule applies instead … never both") to both files; re-review converged at 0 Critical + 0 Moderate.
- Source:  https://github.com/pekral/cursor-rules/pull/646   Added: 2026-06-20
