#!/usr/bin/env bash
# Usage: scripts/changed-classes.sh [base-branch]
# Lists PHP classes modified in the current branch compared to base branch.
# Default base branch: main

set -euo pipefail

BASE="${1:-main}"

git diff --name-only "$BASE"...HEAD -- '*.php' | sort
