# Refactoring Principles

## SRP — Single Responsibility Principle
- A class should have one reason to change.
- If you can describe a class with "and", it likely has multiple responsibilities.
- Split by responsibility, not by technical layer.

## DRY — Don't Repeat Yourself
- Duplicated logic must be extracted into a shared method or class.
- Apply only when duplication is real (same intent), not coincidental (same code, different purpose).

## KISS — Keep It Simple
- Prefer the simplest solution that works.
- Avoid clever code — clarity beats brevity.
- If a junior developer cannot understand it in 30 seconds, simplify.

## YAGNI — You Aren't Gonna Need It
- Do not introduce abstractions for hypothetical future requirements.
- Refactor for today's needs, not tomorrow's guesses.
- Three similar lines are better than a premature abstraction.

## Small Safe Changes
- Each change should be independently correct and testable.
- If a refactoring step fails, only that step needs to be reverted.
- Prefer multiple small commits over one large rewrite.
- Leave the code in a working state after every step.
