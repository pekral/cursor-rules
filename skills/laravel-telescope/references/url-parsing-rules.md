# URL Parsing Rules

## Extracting the Telescope target

From a Telescope URL, extract the following:

| Component | Example | Notes |
|---|---|---|
| Environment | `local`, `staging`, `production` | Infer from hostname |
| Host | `app.example.com` | Base URL of the Telescope instance |
| Path | `/telescope/requests/abc-123` | Determines entry type and UUID |
| Query params | `?tag=user:5&before=...` | Filters that affect record matching |
| Request identifier | `abc-123` (UUID segment) | Primary key for DB lookup |

## Identifying entry type from path

| Path segment | Entry type |
|---|---|
| `/requests/` | HTTP request |
| `/exceptions/` | Exception |
| `/queries/` | Database query |
| `/jobs/` | Queued job |
| `/cache/` | Cache operation |
| `/dumps/` | Dump |
| `/logs/` | Log entry |
| `/events/` | Event |
| `/mail/` | Mail |
| `/notifications/` | Notification |
| `/schedule/` | Scheduled task |

## Capturing filters

Always capture all active filters because they affect which records are relevant:

- Time window (`before`, `after`, date range)
- Status code filter
- Tag filter (e.g., `user:5`, `order:42`)
- Batch or family hash
- Search keyword

If filters are present, they must be included in the analysis report under "Scope / filters".
