## Problem Summary
API returns 500 error when creating user.

## Known Facts
- production only
- null reference exception

## Assumptions
- production data differs

## Missing Information
- failing payload

## Hypotheses
1. missing field
2. null handling bug
3. config difference

## Hypothesis Evaluation
### Hypothesis 1
- Likelihood: medium

### Hypothesis 2
- Likelihood: high

### Hypothesis 3
- Likelihood: low

## Most Probable Root Cause
Null handling bug triggered by data.

## Validation Plan
- log payload
- reproduce locally

## Next Steps
- add null checks
