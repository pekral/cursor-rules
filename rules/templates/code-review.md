# Task Overview

You are a **senior PHP Laravel programmer**.
Perform a code review for all current changes compared to the `master` branch in Git.  
Follow all rules defined for the assignment (originally written in Czech) and apply the complete rule set from `.cursor/rules/*.mdc`.

---

# Assignment



# Possible solutions to problems




# TODO Checklist for This Task

## General Rules
- DynamoDB is used in this project as a NoSQL database and caching layer.
- All changes must comply with the rules defined in `.cursor/rules/*.mdc`. Carefully read *all* rule files.
- Never modify code during this task — this is a code review only.

---

## Git & Change Analysis
- Identify current changes compared to the `master` branch (list commits on the current branch).
- Evaluate whether the changes match the original assignment.
- Determine how the new changes may impact other parts of the application.
- Suggest improvements or optimizations where applicable (without modifying the code).
- Identify any potential security risks introduced by the changes.

---

## Database & Query Review
- If there are any SQL query changes:
    - Inspect related database tables through migrations.
    - Check indexes.
    - Analyze the execution logic of the SQL query.
- If DynamoDB operations are changed, ensure they follow established project patterns and performance expectations.
- Analyze any DB queries for potential production issues.

---

## Architecture & Application Logic
- If a controller introduces a new action:
    - Confirm a corresponding custom Request class exists.
    - Ensure the Request provides consistent data for business logic.
- Check for places where behavior may be affected across the project.

---

## Concurrency, Caching & Stability
- Check for race conditions.
- Check for cache stampede risks.
- Check backward compatibility.
- Review potential performance issues.
- Look for security concerns.
- Check for memory leaks.
- Validate correct timezone handling.
- Identify possible N+1 query problems.

---

## Tests & Coverage
- Determine current coverage **only for the changed files**.
- Ensure new code is properly tested.
- Identify missing test value variations that would reveal hidden bugs.

---

## Final Output
Generate a brief summary describing:
- All detected issues,
- Potential risks,
- Suggested improvements (without modifying code).