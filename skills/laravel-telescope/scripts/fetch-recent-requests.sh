#!/usr/bin/env bash
# Fetch recent Telescope request entries within a time window.
# Usage: ./fetch-recent-requests.sh <FROM_DATETIME> <TO_DATETIME> [LIMIT]
# Datetimes should be in ISO 8601 format, e.g., "2025-03-15 14:00:00"

set -euo pipefail

FROM="${1:?Usage: fetch-recent-requests.sh <FROM> <TO> [LIMIT]}"
TO="${2:?Usage: fetch-recent-requests.sh <FROM> <TO> [LIMIT]}"
LIMIT="${3:-100}"

php artisan tinker --execute="
    \$entries = DB::table('telescope_entries')
        ->where('type', 'request')
        ->whereBetween('created_at', ['$FROM', '$TO'])
        ->select('uuid', 'type', 'family_hash', 'created_at')
        ->orderByDesc('created_at')
        ->limit($LIMIT)
        ->get();

    if (\$entries->isEmpty()) {
        echo \"No request entries found between $FROM and $TO\n\";
        exit(1);
    }

    echo json_encode(\$entries, JSON_PRETTY_PRINT) . \"\n\";
"
