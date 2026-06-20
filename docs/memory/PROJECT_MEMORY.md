# Project memory — cursor-rules

### agent-file-vs-registration — Adding agents/<name>.md does not make the agent dispatchable

- Trigger: a daidalos run tries to dispatch a newly documented agent (e.g. `apollon`) via the Task tool, or any orchestrator step assumes a new `agents/<name>.md` entry is immediately executable as a subagent.
- Rule:    An `agents/<name>.md` file is documentation only. For an agent to be dispatchable in Claude tooling the agent type must also be installed/registered (the installer syncs copies into `.claude/`). Until that happens, the agent cannot be spawned and the orchestrator must fall back to available registered agents or treat the step as blocked. Document the dependency explicitly in the agent's own file and in the issue that introduces the agent.
- Example: `agents/apollon.md` was added in #628; daidalos correctly noted "agent type `apollon` is not registered in this environment" and continued with `metis / talos / argos`. The push-level gate becomes effective only after `apollon` is installed.
- Source:  https://github.com/pekral/cursor-rules/pull/633   Added: 2026-06-20
