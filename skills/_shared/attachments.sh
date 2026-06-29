#!/usr/bin/env bash
# attachments.sh — shared library for downloading issue-tracker attachments into a
# quarantine directory, writing a manifest, and handing every file to the mandatory
# security scan (scan-attachments.sh) before anything is promoted to safe/.
#
# Sourced, not executed. A tracker-specific download-attachments.sh wrapper:
#   1. sets PROG (used in messages) before sourcing
#   2. sources this file
#   3. resolves the attachment inventory as a JSON array of
#        { "id", "name", "declaredMime", "size", "contentUrl" }
#   4. writes a curl auth config file (chmod 600) carrying ONLY the auth header
#      (empty string when the source needs no auth)
#   5. calls att_run "<inventory_json>" "<auth_config_file>" "<dest_dir>" "<tracker>" "<ref>"
#
# Security contract (rules/security/backend.md + frontend.md):
#   - TLS validation is ALWAYS on. No -k / --insecure / --no-check-certificate, no
#     verify=false; downloads are pinned to https via --proto '=https'.
#   - The auth token lives only inside the 0600 curl --config file, never in argv,
#     never in the manifest, never in a log line.
#   - Downloaded bytes land in a dedicated quarantine dir with 0600 perms; nothing is
#     opened or rendered before scan-attachments.sh runs. Only files the scan marks
#     `pass` are copied into safe/ for the analysis to read.
#
# Exit codes honored by callers: 2 = missing tool, 3 = download/network failure.
set -euo pipefail

: "${PROG:=${0##*/}}"

ATT_LIB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Per-file size cap (25 MiB) and per-issue count cap. Overridable via env for tests.
ATT_MAX_BYTES="${ATT_MAX_BYTES:-26214400}"
ATT_MAX_COUNT="${ATT_MAX_COUNT:-25}"

att_require_tools() {
  local bin
  for bin in curl jq file; do
    if ! command -v "$bin" >/dev/null 2>&1; then
      echo "${PROG}: required tool not found: $bin" >&2
      exit 2
    fi
  done
}

# att_default_dest — default quarantine root under the session scratchpad (never the repo).
att_default_dest() {
  local base="${CLAUDE_SCRATCHPAD_DIR:-${TMPDIR:-/tmp}}"
  printf '%s/attachments' "${base%/}"
}

# att_file_size <path> — byte count, portably (no GNU/BSD stat divergence).
att_file_size() {
  wc -c < "$1" | tr -d '[:space:]'
}

# att_sha256 <path> — lowercase hex digest only.
att_sha256() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$1" | awk '{print $1}'
  else
    shasum -a 256 "$1" | awk '{print $1}'
  fi
}

# att_safe_name <index> <name> — collision-free, traversal-free local filename.
# Strips any directory component and reduces to [A-Za-z0-9._-]; prefixes a zero-padded
# index so two attachments named "image.png" cannot overwrite each other.
att_safe_name() {
  local idx="$1" name="$2" base
  base="${name##*/}"
  base="$(printf '%s' "$base" | LC_ALL=C tr -c 'A-Za-z0-9._-' '_')"
  [[ -z "$base" || "$base" == '.' || "$base" == '..' ]] && base="attachment"
  printf '%03d__%s' "$idx" "$base"
}

# att_download <url> <out> <auth_config_file> — fetch one URL to <out> with TLS on.
att_download() {
  local url="$1" out="$2" cfg="$3"
  local args=(
    --location               # follow redirects (e.g. GitHub asset -> signed storage URL)
    --proto '=https'         # HTTPS only; no downgrade. TLS validation stays ON (never -k).
    --proto-redir '=https'   # a redirect may not downgrade to http either
    --fail-with-body         # non-2xx -> non-zero exit, keep body for diagnostics
    --silent --show-error    # suppress the progress meter only; body goes to --output, never piped to a shell
    --max-time 120
    --max-filesize "$ATT_MAX_BYTES"   # abort oversized transfers before they fill the quarantine disk
    --output "$out"
  )
  # Auth header is read from the 0600 config file so the token never reaches argv/logs.
  [[ -n "$cfg" ]] && args+=( --config "$cfg" )
  ( umask 077; curl "${args[@]}" "$url" )
}

# att_run <inventory_json> <auth_config_file> <dest_dir> <tracker> <ref>
# Downloads every inventory item into <dest_dir>/_quarantine/, writes
# attachments-manifest.json, then runs the mandatory security scan.
att_run() {
  local inventory="$1" cfg="$2" dest="$3" tracker="$4" ref="$5"

  local quarantine="${dest%/}/_quarantine"
  local safe="${dest%/}/safe"
  local manifest="${dest%/}/attachments-manifest.json"
  mkdir -p "$quarantine" "$safe"
  chmod 700 "$quarantine" "$safe"

  local count
  count="$(printf '%s' "$inventory" | jq 'length')"

  local entries='[]'
  local i name declared url out localPath size sha status reason
  for (( i = 0; i < count; i++ )); do
    name="$(printf '%s' "$inventory" | jq -r ".[$i].name // \"attachment\"")"
    declared="$(printf '%s' "$inventory" | jq -r ".[$i].declaredMime // \"\"")"
    url="$(printf '%s' "$inventory" | jq -r ".[$i].contentUrl // \"\"")"

    status="downloaded"; reason=""; localPath=""; size=0; sha=""

    if (( i >= ATT_MAX_COUNT )); then
      status="block"; reason="exceeds max attachment count (${ATT_MAX_COUNT})"
    elif [[ -z "$url" ]]; then
      status="block"; reason="no downloadable contentUrl in inventory"
    else
      out="${quarantine}/$(att_safe_name "$i" "$name")"
      if att_download "$url" "$out" "$cfg"; then
        chmod 600 "$out"
        localPath="$out"
        size="$(att_file_size "$out")"
        sha="$(att_sha256 "$out")"
      else
        echo "${PROG}: download failed for attachment '${name}'" >&2
        rm -f "$out"
        status="block"; reason="download failed (network/auth) — TLS validation stayed on"
      fi
    fi

    entries="$(jq -c \
      --argjson e "$(printf '%s' "$inventory" | jq -c ".[$i]")" \
      --arg lp "$localPath" --arg st "$status" --arg rs "$reason" \
      --arg sz "$size" --arg sha "$sha" \
      '. + [{
        id: ($e.id // null),
        name: ($e.name // null),
        declaredMime: ($e.declaredMime // null),
        size: ($sz | tonumber? // 0),
        sha256: (if $sha == "" then null else $sha end),
        localPath: (if $lp == "" then null else $lp end),
        status: $st,
        reason: (if $rs == "" then null else $rs end),
        safePath: null
      }]' <<< "$entries")"
  done

  ( umask 077; jq -n \
    --arg tracker "$tracker" --arg ref "$ref" \
    --arg quarantine "$quarantine" --arg safe "$safe" \
    --argjson maxBytes "$ATT_MAX_BYTES" --argjson maxCount "$ATT_MAX_COUNT" \
    --argjson attachments "$entries" \
    '{
      tracker: $tracker,
      issue: $ref,
      quarantineDir: $quarantine,
      safeDir: $safe,
      limits: { maxBytes: $maxBytes, maxCount: $maxCount },
      attachments: $attachments
    }' > "$manifest" )
  chmod 600 "$manifest"

  echo "${PROG}: downloaded ${count} attachment(s) to quarantine; running security scan…" >&2
  "${ATT_LIB_DIR}/scan-attachments.sh" "$dest"
}
