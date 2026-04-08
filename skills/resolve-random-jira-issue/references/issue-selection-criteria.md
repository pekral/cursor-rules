# Issue Selection Criteria

## Eligible issues

An issue is eligible for AI resolution when ALL of the following are true:

- The issue is **not resolved** (status is not Done, Closed, or equivalent)
- The issue carries the label `Resolve_by_AI`
- The issue is assigned to the current project board (or other configured locations)

## Where to search

- Primary: the default JIRA project board
- Secondary: any additional boards or filters referenced in project configuration or `@rules/jira-operations.mdc`

## Filtering rules

- Exclude issues that already have a linked pull request in GitHub
- Exclude issues in a blocked or on-hold status
- Only include issue types that represent actionable work (Bug, Task, Story) — skip Epics and Sub-tasks unless explicitly configured

## Random selection

- After filtering, select one issue at random
- Do not prefer higher or lower priority unless instructed otherwise
- Log which issue was selected and why it was eligible
