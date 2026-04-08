## Problem Summary
CI fails intermittently.

## Known Facts
- random failures

## Hypotheses
1. flaky test
2. race condition
3. external service

## Evaluation
- flaky: high
- race: medium
- external: medium

## Most Probable Root Cause
Flaky test.

## Validation Plan
- rerun tests multiple times

## Next Steps
- stabilize test
