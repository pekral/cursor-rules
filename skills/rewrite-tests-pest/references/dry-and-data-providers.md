# DRY Principles and Data Providers

## Data Providers

- Use data providers to reduce repetition across similar test cases
- After creating or modifying tests, analyze all similar tests that can be simplified using data providers and refactor them
- Data providers should contain only the varying parts; shared setup belongs in `beforeEach` or helper methods

## DRY

- Eliminate duplicated setup, assertions, and helper logic
- Extract reusable setup into `beforeEach` blocks or helper functions in `Pest.php`
- Keep individual test cases as simple and focused as possible
