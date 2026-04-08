#!/usr/bin/env bash
# Detect and run project-level automatic fixers (composer scripts, phing, etc.).
# Searches for common fixer configurations and runs them.
# Usage: ./run-fixers.sh

set -euo pipefail

echo "=== Detecting project fixers ==="

# Check for composer scripts
if [ -f "composer.json" ]; then
  echo "--- Checking composer scripts ---"
  FIXER_SCRIPTS=$(php -r '
    $json = json_decode(file_get_contents("composer.json"), true);
    $scripts = $json["scripts"] ?? [];
    $keywords = ["fix", "lint", "cs", "format", "style", "phpcs", "phpstan", "rector"];
    foreach ($scripts as $name => $cmd) {
      foreach ($keywords as $kw) {
        if (stripos($name, $kw) !== false) { echo $name . "\n"; break; }
      }
    }
  ' 2>/dev/null || true)

  if [ -n "$FIXER_SCRIPTS" ]; then
    echo "Found composer fixer scripts:"
    echo "$FIXER_SCRIPTS"
    for script in $FIXER_SCRIPTS; do
      echo "--- Running: composer $script ---"
      composer "$script" || true
    done
  else
    echo "No fixer scripts found in composer.json"
  fi
fi

# Check for phing
if [ -f "build.xml" ]; then
  echo "--- Phing build file detected ---"
  echo "Run: phing <target> manually after reviewing available targets."
  phing -l 2>/dev/null || true
fi

# Check for npm/yarn scripts
if [ -f "package.json" ]; then
  echo "--- Checking package.json scripts ---"
  node -e '
    const pkg = require("./package.json");
    const scripts = pkg.scripts || {};
    const keywords = ["fix", "lint", "format", "prettier", "eslint"];
    Object.keys(scripts).forEach(name => {
      if (keywords.some(kw => name.toLowerCase().includes(kw))) {
        console.log(name);
      }
    });
  ' 2>/dev/null || true
fi

echo "=== Fixer detection complete ==="
