#!/usr/bin/env bash
# Check for common secret patterns in the codebase.
# Usage: ./check-secrets.sh [directory]

set -euo pipefail

DIR="${1:-.}"

echo "=== Checking for hardcoded secrets ==="

echo ""
echo "--- .env files committed to git ---"
git -C "$DIR" ls-files --cached '*.env' '.env.*' | grep -v '.env.example' || echo "None found."

echo ""
echo "--- Potential hardcoded API keys/tokens/passwords ---"
grep -rn --include='*.php' --include='*.js' --include='*.ts' --include='*.py' --include='*.yaml' --include='*.yml' --include='*.json' \
  -iE '(api[_-]?key|api[_-]?secret|password|token|secret[_-]?key)\s*[:=]\s*["\x27][^"\x27]{8,}' \
  "$DIR" 2>/dev/null | head -50 || echo "None found."

echo ""
echo "--- Secrets in git history (last 20 commits) ---"
git -C "$DIR" log --oneline -20 --diff-filter=A -- '*.env' '.env.*' 2>/dev/null | grep -v '.env.example' || echo "None found."
