# Example: Extract Method to Reduce Complexity

## Before

```php
public function processImport(Collection $rows): ImportResult
{
    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        if (empty($row['email']) || ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $skipped++;
            continue;
        }
        if (User::where('email', $row['email'])->exists()) {
            $skipped++;
            continue;
        }
        User::create([
            'name' => $row['name'],
            'email' => $row['email'],
        ]);
        $created++;
    }

    return new ImportResult($created, $skipped);
}
```

## After

```php
public function processImport(Collection $rows): ImportResult
{
    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        if (! $this->isValidRow($row) || $this->isDuplicate($row['email'])) {
            $skipped++;
            continue;
        }

        $this->createUser($row);
        $created++;
    }

    return new ImportResult($created, $skipped);
}

private function isValidRow(array $row): bool
{
    return ! empty($row['email'])
        && filter_var($row['email'], FILTER_VALIDATE_EMAIL) !== false;
}

private function isDuplicate(string $email): bool
{
    return User::where('email', $email)->exists();
}

private function createUser(array $row): void
{
    User::create([
        'name' => $row['name'],
        'email' => $row['email'],
    ]);
}
```

## What Changed
- Extracted validation to `isValidRow()`.
- Extracted duplicate check to `isDuplicate()`.
- Extracted creation to `createUser()`.

## Why
- Main method now reads as a high-level description of the process.
- Each extracted method has a single purpose and intention-revealing name.
- No behavioral change — same logic, better readability.
