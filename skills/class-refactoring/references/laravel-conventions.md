# Laravel Conventions

## General

- Laravel helpers over native PHP when appropriate

## Eloquent and Database

- When changing Eloquent models, migrations, or factories, do not duplicate column defaults that already exist in the database schema; see `@rules/laravel/architecture.mdc` (Schema defaults, Migrations)

## Testing

- When changing Laravel tests that queue jobs, dispatch only via `JobClass::dispatch(...)` per `@rules/laravel/architecture.mdc` Testing

## Livewire (only in Livewire projects)

- Delegate all business logic to Action classes following the mandatory flow: `Livewire Component -> Action -> ModelService -> Repository/ModelManager`
