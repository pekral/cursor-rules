# PHP Coding Standards

## Clean Code Rules

- Clean, modern, optimized code
- Stateless PHP classes
- Collections over `foreach` where appropriate
- No magic numbers
- No deep nesting
- Prefer small, focused functions
- English comments only
- Complex logic commented
- Remove obvious comments; keep PHPStan-relevant docs
- No single-use variables

## PHPDoc

- PHPDoc for PHPStan analysis
- PHPDoc content: describe business logic and general purpose; avoid listing method calls or implementation steps

## Method Design

- Extract private methods if body exceeds ~30 lines
- Extract intention-revealing private methods
- Method signatures must remain expressive and minimal
