#!/usr/bin/env bash
# Find existing Postman collection and environment files in the repository.
# Usage: ./find-postman-files.sh

set -euo pipefail

echo "=== Postman Collections ==="
find . -type f -name '*.postman_collection.json' 2>/dev/null | sort

echo ""
echo "=== Postman Environments ==="
find . -type f -name '*.postman_environment.json' 2>/dev/null | sort

echo ""
echo "=== Postman Directories ==="
for dir in postman docs/postman; do
  if [ -d "$dir" ]; then
    echo "$dir/"
    ls -la "$dir/"
  fi
done
