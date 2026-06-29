#!/usr/bin/env bash
# scan-attachments.sh — mandatory, deterministic security gate over downloaded
# issue-tracker attachments. Runs AFTER download-attachments.sh and BEFORE any file
# is read or rendered by the analysis.
#
# Usage:
#   scan-attachments.sh <DEST_DIR>     # scan the quarantine described by DEST_DIR/attachments-manifest.json
#   scan-attachments.sh --self-test    # generate malicious + benign fixtures and assert the verdicts
#
# For each quarantined file the scan derives a verdict written back into the manifest:
#   pass    — type is on the analysis allowlist and carries no active content; copied to <DEST>/safe/
#   block   — clearly unsafe (executable, archive, script, HTML, SVG with active content,
#             polyglot, declared/actual MIME mismatch, over size limit); NEVER promoted, only reported
#   review  — type is outside the allowlist but not obviously malicious; the agent must route it to the
#             security-review skill and MUST NOT open it until that verdict clears
#
# Allowlist (intended for analysis): png, jpg/jpeg, gif, webp, pdf, txt, log, csv, json.
#
# Security contract: nothing is opened before this scan; only `pass` files reach safe/.
# This script reads bytes with `file` and a bounded head sniff only — it never executes
# the scanned file and never disables TLS anywhere.
#
# Exit codes:
#   0  scan completed (verdicts written; blocked files are an expected outcome, not an error)
#   1  usage error
#   2  missing required tool (jq, file)
#   3  manifest missing or unreadable
#   4  --self-test detected a regression
set -euo pipefail

PROG="${0##*/}"

ATT_MAX_BYTES="${ATT_MAX_BYTES:-26214400}"

for bin in jq file; do
  if ! command -v "$bin" >/dev/null 2>&1; then
    echo "${PROG}: required tool not found: $bin" >&2
    exit 2
  fi
done

scan_file_size() {
  wc -c < "$1" | tr -d '[:space:]'
}

# att_mime_compatible <declared> <detected> — true when the declared MIME is consistent
# with the magic-byte-detected MIME (exact, jpeg alias, or both text/*).
att_mime_compatible() {
  local declared="$1" detected="$2"
  [[ "$declared" == "$detected" ]] && return 0
  case "${declared}|${detected}" in
    image/jpg\|image/jpeg|image/jpeg\|image/jpg|image/pjpeg\|image/jpeg) return 0;;
  esac
  [[ "$declared" == text/* && "$detected" == text/* ]] && return 0
  return 1
}

# att_classify <path> <declaredMime> — echoes "<status>\t<reason>".
# Deterministic checks only; no execution of the scanned file.
att_classify() {
  local path="$1" declared="$2"
  local size detected head verdict

  size="$(scan_file_size "$path")"
  if (( size == 0 )); then
    printf 'block\tempty file'; return
  fi
  if (( size > ATT_MAX_BYTES )); then
    printf 'block\texceeds max size (%s > %s bytes)' "$size" "$ATT_MAX_BYTES"; return
  fi

  detected="$(file --mime-type -b "$path" 2>/dev/null || echo application/octet-stream)"
  head="$(head -c 4096 "$path" 2>/dev/null | LC_ALL=C tr -d '\000')"

  # --- clearly-unsafe categories (checked before the allowlist) ---
  case "$detected" in
    application/x-executable|application/x-elf|application/x-mach-binary|application/x-dosexec|application/x-sharedlib|application/x-pie-executable|application/x-object)
      printf 'block\texecutable binary (%s)' "$detected"; return;;
    application/zip|application/gzip|application/x-tar|application/x-bzip2|application/x-xz|application/x-7z-compressed|application/x-rar|application/vnd.rar)
      printf 'block\tarchive not permitted without explicit opt-in (%s)' "$detected"; return;;
    text/x-shellscript|application/x-sh|application/x-csh|text/x-perl|text/x-python|text/x-ruby|application/javascript|text/javascript|application/x-httpd-php|text/x-php)
      printf 'block\tscript content (%s)' "$detected"; return;;
    application/vnd.ms-office|application/x-msdownload)
      printf 'block\tOffice/macro-capable binary (%s)' "$detected"; return;;
  esac

  if [[ "$head" == '#!'* ]]; then
    printf 'block\tscript with shebang'; return
  fi

  shopt -s nocasematch
  if [[ "$detected" == text/html ]] || [[ "$head" == *'<!doctype html'* ]] || [[ "$head" == *'<html'* ]]; then
    shopt -u nocasematch
    printf 'block\tHTML content (stored-XSS risk)'; return
  fi

  if [[ "$detected" == image/svg+xml ]] || [[ "$head" == *'<svg'* ]]; then
    if [[ "$head" == *'<script'* ]] || [[ "$head" =~ [[:space:]]on[a-z]+[[:space:]]*= ]] || [[ "$head" == *'<foreignobject'* ]] || [[ "$head" == *'javascript:'* ]]; then
      shopt -u nocasematch
      printf 'block\tSVG with active content (<script>/on*/<foreignObject>)'; return
    fi
    shopt -u nocasematch
    printf 'block\tSVG not in analysis allowlist'; return
  fi

  case "$detected" in
    image/png|image/jpeg|image/gif|image/webp)
      if [[ "$head" == *'<script'* ]] || [[ "$head" == *'<html'* ]] || [[ "$head" == *'<?php'* ]] || [[ "$head" == *'<!doctype html'* ]]; then
        shopt -u nocasematch
        printf 'block\tpolyglot (image header with embedded HTML/JS/PHP)'; return
      fi;;
  esac
  shopt -u nocasematch

  # --- declared vs actual MIME ---
  if [[ -n "$declared" && "$declared" != "null" ]]; then
    local dnorm="${declared%%;*}"
    dnorm="${dnorm// /}"
    if ! att_mime_compatible "$dnorm" "$detected"; then
      printf 'block\tdeclared/actual MIME mismatch (declared=%s actual=%s)' "$dnorm" "$detected"; return
    fi
  fi

  # --- allowlist ---
  case "$detected" in
    image/png|image/jpeg|image/gif|image/webp|application/pdf|text/plain|text/csv|application/json)
      printf 'pass\tallowlisted (%s)' "$detected"; return;;
    text/*)
      printf 'pass\tallowlisted text (%s)' "$detected"; return;;
  esac

  printf 'review\ttype not in allowlist (%s) — route to security-review before opening' "$detected"
}

scan_manifest() {
  local dest="${1%/}"
  local manifest="${dest}/attachments-manifest.json"
  local safe="${dest}/safe"

  if [[ ! -r "$manifest" ]]; then
    echo "${PROG}: manifest not found or unreadable: $manifest" >&2
    exit 3
  fi
  mkdir -p "$safe"; chmod 700 "$safe"

  local n i status lp declared res verdict reason safePath tmp passes blocks reviews
  n="$(jq '.attachments | length' "$manifest")"
  passes=0; blocks=0; reviews=0
  for (( i = 0; i < n; i++ )); do
    status="$(jq -r ".attachments[$i].status" "$manifest")"
    if [[ "$status" != "downloaded" ]]; then
      [[ "$status" == "block" ]] && blocks=$((blocks + 1))
      continue
    fi
    lp="$(jq -r ".attachments[$i].localPath // \"\"" "$manifest")"
    declared="$(jq -r ".attachments[$i].declaredMime // \"\"" "$manifest")"
    if [[ -z "$lp" || ! -r "$lp" ]]; then
      verdict="block"; reason="quarantine file missing"
    else
      res="$(att_classify "$lp" "$declared")"
      verdict="${res%%$'\t'*}"; reason="${res#*$'\t'}"
    fi

    safePath="null"
    if [[ "$verdict" == "pass" ]]; then
      cp "$lp" "${safe}/$(basename "$lp")"
      chmod 600 "${safe}/$(basename "$lp")"
      safePath="\"${safe}/$(basename "$lp")\""
      passes=$((passes + 1))
    elif [[ "$verdict" == "review" ]]; then
      reviews=$((reviews + 1))
    else
      blocks=$((blocks + 1))
    fi

    tmp="$(mktemp)"
    jq --argjson i "$i" --arg s "$verdict" --arg r "$reason" --argjson sp "$safePath" \
      '.attachments[$i].status = $s | .attachments[$i].reason = $r | .attachments[$i].safePath = $sp' \
      "$manifest" > "$tmp" && mv "$tmp" "$manifest"
    chmod 600 "$manifest"
  done

  echo "${PROG}: scan complete — ${passes} pass, ${blocks} block, ${reviews} review. Read only files under ${safe}/." >&2
}

self_test() {
  local dir
  dir="$(mktemp -d)"
  # shellcheck disable=SC2064
  trap "rm -rf '$dir'" EXIT
  local q="${dir}/_quarantine"
  mkdir -p "$q"

  # benign 1x1 PNG (valid signature + IHDR) -> pass
  printf '\211PNG\r\n\032\n\000\000\000\015IHDR\000\000\000\001\000\000\000\001\010\006\000\000\000\037\025\304\211\000\000\000\012IDATx\234c\000\001\000\000\005\000\001\r\n-\262\000\000\000\000IEND\256B\140\202' > "${q}/000__ok.png"
  # malicious SVG with active content -> block
  printf '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' > "${q}/001__evil.svg"
  # HTML masquerading as .txt -> block
  printf '<!DOCTYPE html><html><body><script>steal()</script></body></html>' > "${q}/002__page.html"
  # polyglot: PNG magic with embedded script -> block
  printf '\211PNG\r\n\032\n<script>evil()</script>' > "${q}/003__poly.png"

  cat > "${dir}/attachments-manifest.json" <<EOF
{
  "tracker": "self-test", "issue": "fixture",
  "quarantineDir": "${q}", "safeDir": "${dir}/safe",
  "limits": { "maxBytes": ${ATT_MAX_BYTES}, "maxCount": 25 },
  "attachments": [
    { "id": "1", "name": "ok.png",   "declaredMime": "image/png",  "size": 0, "sha256": null, "localPath": "${q}/000__ok.png",   "status": "downloaded", "reason": null, "safePath": null },
    { "id": "2", "name": "evil.svg", "declaredMime": "image/svg+xml", "size": 0, "sha256": null, "localPath": "${q}/001__evil.svg", "status": "downloaded", "reason": null, "safePath": null },
    { "id": "3", "name": "page.html","declaredMime": "text/plain", "size": 0, "sha256": null, "localPath": "${q}/002__page.html","status": "downloaded", "reason": null, "safePath": null },
    { "id": "4", "name": "poly.png", "declaredMime": "image/png",  "size": 0, "sha256": null, "localPath": "${q}/003__poly.png", "status": "downloaded", "reason": null, "safePath": null }
  ]
}
EOF

  scan_manifest "$dir"

  local fail=0 m="${dir}/attachments-manifest.json"
  assert_status() {
    local id="$1" want="$2" got
    got="$(jq -r --arg id "$id" '.attachments[] | select(.id==$id) | .status' "$m")"
    if [[ "$got" != "$want" ]]; then
      echo "FAIL: attachment id=$id expected '$want' got '$got'" >&2
      fail=1
    fi
  }
  assert_status 1 pass
  assert_status 2 block
  assert_status 3 block
  assert_status 4 block

  # the benign PNG must have been promoted to safe/, the malicious ones must not be
  if [[ ! -f "${dir}/safe/000__ok.png" ]]; then
    echo "FAIL: benign PNG was not promoted to safe/" >&2; fail=1
  fi
  if [[ -f "${dir}/safe/001__evil.svg" || -f "${dir}/safe/002__page.html" || -f "${dir}/safe/003__poly.png" ]]; then
    echo "FAIL: a blocked file leaked into safe/" >&2; fail=1
  fi

  if (( fail == 0 )); then
    echo "${PROG}: --self-test PASS (benign PNG promoted, malicious SVG/HTML/polyglot blocked)"
    exit 0
  fi
  echo "${PROG}: --self-test FAILED" >&2
  exit 4
}

if [[ "${1:-}" == "--self-test" ]]; then
  self_test
fi

if [[ $# -ne 1 || -z "${1:-}" || "${1:-}" == -* ]]; then
  echo "Usage: scan-attachments.sh <DEST_DIR>" >&2
  echo "       scan-attachments.sh --self-test" >&2
  exit 1
fi

scan_manifest "$1"
