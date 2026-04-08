#!/usr/bin/env bash
# Get the currently authenticated GitHub user login.
# Usage: ./get-current-user.sh

set -euo pipefail

gh api user --jq '.login'
