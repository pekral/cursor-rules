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
