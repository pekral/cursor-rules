#!/usr/bin/env bash
# Fetch all Telescope entries sharing a family hash, with their tags.
# Usage: ./fetch-family.sh <FAMILY_HASH> [LIMIT]

set -euo pipefail

FAMILY_HASH="${1:?Usage: fetch-family.sh <FAMILY_HASH> [LIMIT]}"
LIMIT="${2:-200}"

php artisan tinker --execute="
    \$entries = DB::table('telescope_entries as te')
        ->leftJoin('telescope_entries_tags as tet', 'tet.entry_uuid', '=', 'te.uuid')
        ->where('te.family_hash', '$FAMILY_HASH')
        ->select('te.uuid', 'te.type', 'te.created_at', 'tet.tag')
        ->orderByDesc('te.created_at')
        ->limit($LIMIT)
        ->get();

    if (\$entries->isEmpty()) {
        echo \"No entries found for family_hash: $FAMILY_HASH\n\";
        exit(1);
    }

    echo json_encode(\$entries, JSON_PRETTY_PRINT) . \"\n\";
"
