# Issue Structure Template

Each issue must follow this template:

```markdown
## Goal

<Clear, concise summary of what this step achieves — written for product managers
and non-technical stakeholders. Focus on the business value and user impact.>

## Original Assignment (context)

<Reference to the original assignment or a relevant excerpt that explains why
this step exists. Keep it brief — link to the parent issue when possible.>

## Technical Solution

<Detailed technical approach for developers and AI agents:>
- Architecture decisions and patterns to follow
- Key files, classes, or modules to create/modify
- Integration points and dependencies on other steps
- Edge cases and constraints to consider

## Acceptance Criteria

- [ ] <Specific, measurable criterion 1>
- [ ] <Specific, measurable criterion 2>
- [ ] <...>

## Testing Scenarios

<Concrete test cases a QA tester can execute:>

### Happy Path
- <Scenario description and expected result>

### Edge Cases
- <Scenario description and expected result>

### Regression
- <What existing functionality must remain unaffected>

## Dependencies

- <List any steps/issues that must be completed before this one>
- <Or state "None" if independent>

## Notes

- Source: <HTTP link to the original assignment or parent issue>
- This issue was created by the `create-issues-from-text` skill.
```

## Section Guidelines

- **Goal**: Must be understandable by non-technical stakeholders. Focus on business value, not implementation details.
- **Original Assignment**: Keep it brief. Link to parent issue rather than copying large blocks of text.
- **Technical Solution**: Write for developers and AI agents. Be specific about files, patterns, and integration points.
- **Acceptance Criteria**: Each criterion must be specific, measurable, and independently verifiable.
- **Testing Scenarios**: Provide concrete scenarios that a QA tester can execute without additional context.
- **Dependencies**: Always state dependencies explicitly. Use "None" if the step is independent.
