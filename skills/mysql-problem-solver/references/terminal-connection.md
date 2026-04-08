# Terminal Connection Guide

When terminal access is available, the skill should try to discover how to connect safely to MySQL.

## Credential discovery

Check these sources in order:

1. `.env` file (`DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`)
2. `config/database.php` (Laravel config)
3. Docker compose files (`docker-compose.yml`, `docker-compose.override.yml`)
4. Local dev scripts
5. CI configuration or docs mentioning DB access

Use `scripts/discover-db-credentials.sh` to automate credential discovery.

## Connection patterns

```bash
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW TABLES;"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "SHOW CREATE TABLE users\G"
mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" -e "EXPLAIN SELECT ..."
```

## Additional tools

```bash
php artisan env
php artisan tinker
mysql --version
```

## When access is unavailable

If credentials are unavailable or access fails, continue with static analysis and state that runtime verification could not be completed.
