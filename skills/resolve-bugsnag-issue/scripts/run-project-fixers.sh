#!/usr/bin/env bash
# Discover and run project-level code fixers (Phing, Composer scripts, etc.).
# Usage: ./run-project-fixers.sh [PROJECT_ROOT]
# Searches for common fixer configurations and runs them.

set -euo pipefail

PROJECT_ROOT="${1:-.}"

echo "=== Discovering project fixers in: $PROJECT_ROOT ==="

# Check for Composer scripts
if [ -f "$PROJECT_ROOT/composer.json" ]; then
  echo ""
  echo "--- Composer scripts available ---"
  php -r "
    \$json = json_decode(file_get_contents('$PROJECT_ROOT/composer.json'), true);
    if (isset(\$json['scripts'])) {
      foreach (array_keys(\$json['scripts']) as \$script) {
        echo \"  composer \$script\n\";
      }
    } else {
      echo \"  (none)\n\";
    }
  " 2>/dev/null || echo "  (could not read composer.json)"

  # Run common fixer script names
  for SCRIPT in fix cs-fix lint-fix format; do
    if composer run-script --list 2>/dev/null | grep -q "$SCRIPT"; then
      echo ""
      echo "--- Running: composer $SCRIPT ---"
      cd "$PROJECT_ROOT" && composer "$SCRIPT"
    fi
  done
fi

# Check for Phing
if [ -f "$PROJECT_ROOT/build.xml" ]; then
  echo ""
  echo "--- Phing build.xml found ---"
  echo "  Run: phing -f $PROJECT_ROOT/build.xml <target>"
fi

# Check for local tool configs
for CONFIG in .php-cs-fixer.php .php-cs-fixer.dist.php phpcs.xml phpcs.xml.dist phpstan.neon phpstan.neon.dist; do
  if [ -f "$PROJECT_ROOT/$CONFIG" ]; then
    echo "  Found config: $CONFIG"
  fi
done

echo ""
echo "=== Fixer discovery complete ==="
