#!/usr/bin/env bash
# Fetch a single Telescope entry by UUID from the database.
# Usage: ./fetch-entry.sh <UUID> [DB_CONNECTION]
# DB_CONNECTION defaults to the project's default database.

set -euo pipefail

UUID="${1:?Usage: fetch-entry.sh <UUID> [DB_CONNECTION]}"
DB="${2:-}"

CONNECTION_FLAG=""
if [ -n "$DB" ]; then
  CONNECTION_FLAG="--database=$DB"
fi

php artisan tinker $CONNECTION_FLAG --execute="
    \$entry = DB::table('telescope_entries')
        ->where('uuid', '$UUID')
        ->first();

    if (!\$entry) {
        echo \"No entry found for UUID: $UUID\n\";
        exit(1);
    }

    echo json_encode([
        'uuid' => \$entry->uuid,
        'type' => \$entry->type,
        'family_hash' => \$entry->family_hash,
        'content' => json_decode(\$entry->content),
        'created_at' => \$entry->created_at,
    ], JSON_PRETTY_PRINT) . \"\n\";
"
