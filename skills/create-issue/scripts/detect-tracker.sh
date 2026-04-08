#!/usr/bin/env bash
# Detect which issue tracker CLI tools are available in the environment.
# Usage: ./detect-tracker.sh
# Outputs the name of each detected tracker CLI tool.

set -euo pipefail

found=0

if command -v gh &>/dev/null; then
  echo "github: $(gh --version | head -1)"
  found=1
fi

if command -v jira &>/dev/null; then
  echo "jira: $(jira --version 2>&1 | head -1)"
  found=1
fi

if command -v linear &>/dev/null; then
  echo "linear: $(linear --version 2>&1 | head -1)"
  found=1
fi

if [ "$found" -eq 0 ]; then
  echo "ERROR: No issue tracker CLI tools found. Install gh, jira, or linear CLI." >&2
  exit 1
fi
