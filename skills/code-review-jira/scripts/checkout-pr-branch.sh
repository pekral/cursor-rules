#!/usr/bin/env bash
# Checkout the branch for a PR locally and pull latest changes.
# Usage: ./checkout-pr-branch.sh <PR_NUMBER>

set -euo pipefail

PR="${1:?Usage: checkout-pr-branch.sh <PR_NUMBER>}"

gh pr checkout "$PR"
git pull --ff-only
