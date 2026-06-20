---
name: slack-messaging
description: Use when you need to send messages to a Slack channel or read recent
  messages from a channel via the Slack Web API
license: MIT
metadata:
  author: Petr Král (pekral.cz)
---

# Slack Messaging

## Purpose

Send and read Slack messages programmatically via the Slack Web API. Two
deterministic shell scripts backed by a shared helper library:

- `send.sh` — post a message to a channel via `chat.postMessage`
- `read.sh` — fetch recent messages from a channel via `conversations.history`
  and emit them as stable JSON

Credentials are read exclusively from the `SLACK_BOT_TOKEN` environment
variable. The token is never written to a file, never appears in script output
or logs, and is never committed to the repository.

---

## Constraints

- Token only from env var `SLACK_BOT_TOKEN`. Never from a file, never echoed.
- TLS is never disabled — no `curl -k`, no `--insecure`, no `verify=false`.
- No silent download-and-execute (`curl … | sh`).
- Errors on network/security operations are always surfaced — no `2>/dev/null`
  on calls whose result matters, no empty `catch`.
- `set -euo pipefail` in every script.
- Exit-code contract: **0** success; **1** usage/argument error; **2** missing
  tool (`curl`/`jq`) or missing `SLACK_BOT_TOKEN`; **3** API failure (HTTP
  non-2xx, network error, or Slack `ok:false`).

---

## Setup — co a kam vložit

### 1. Vytvoř Slack App

1. Přejdi na https://api.slack.com/apps a klikni **Create New App** →
   **From scratch**.
2. Vyber workspace a pojmenuj appku (např. `messaging-bot`).
3. V sekci **OAuth & Permissions** → **Bot Token Scopes** přidej scopy:

   | Scope | Použití |
   |---|---|
   | `chat:write` | odesílání zpráv (`send.sh`) |
   | `channels:history` | čtení zpráv z veřejných kanálů (`read.sh`) |
   | `channels:read` | přístup k metadatům veřejných kanálů |
   | `groups:history` | čtení zpráv z privátních kanálů (`read.sh`) |

4. Klikni **Install to Workspace** a potvrď oprávnění.
5. Zkopíruj **Bot User OAuth Token** (začíná `xoxb-…`) z OAuth & Permissions.

### 2. Bezpečné nastavení tokenu

Nastav token jako env var — NIKDY ho necommituj do repa, NIKDY ho nevkládej
do `.env` souboru sledovaného Gitem:

```bash
# shell profil (mimo repo) — ~/.zshrc nebo ~/.bash_profile
export SLACK_BOT_TOKEN=xoxb-...

# CI secret (GitHub Actions):
# Settings → Secrets → New repository secret → Name: SLACK_BOT_TOKEN
# V workflow: env: SLACK_BOT_TOKEN: ${{ secrets.SLACK_BOT_TOKEN }}
```

### 3. Pozvi bota do kanálu

V každém kanálu, do kterého chceš psát nebo číst, spusť příkaz ve Slacku:

```
/invite @jmeno-tveho-bota
```

### 4. Zjisti Channel ID

V Slacku klikni na název kanálu → **View channel details** → posuň se dolů →
**Channel ID** (začíná `C…` nebo `G…` u privátních). To ID předávej skriptům.

---

## Usage

### send.sh — odeslat zprávu

```bash
# TEXT jako argument
send.sh C0123456789 "Zpráva z terminálu"

# TEXT ze stdin (přes -)
echo "Zpráva z pipeline" | send.sh C0123456789 -

# Výstup: ts odeslané zprávy na stdout, log na stderr
# 1718870400.123456
# action=sent channel=C0123456789 ts=1718870400.123456
```

**Chybové stavy:**

```bash
send.sh                     # exit 1: usage (chybějící argumenty)
send.sh C0123 ""            # exit 1: prázdný text
unset SLACK_BOT_TOKEN
send.sh C0123 "text"        # exit 2: chybějící token
```

### read.sh — číst zprávy

```bash
# Posledních 20 zpráv (výchozí)
read.sh C0123456789

# Posledních 5 zpráv
read.sh C0123456789 5

# Výstup: stabilní JSON pole seřazené od nejstarší zprávy
# [
#   { "user": "U0123456789", "text": "ahoj",  "ts": "1718870400.123456" },
#   { "user": "U0567890123", "text": "díky",  "ts": "1718870461.234567" }
# ]
```

**Chybové stavy:**

```bash
read.sh                     # exit 1: usage (chybějící channel ID)
read.sh C0123 0             # exit 1: neplatný limit (musí být 1..200)
read.sh C0123 abc           # exit 1: neplatný limit (musí být celé číslo)
unset SLACK_BOT_TOKEN
read.sh C0123               # exit 2: chybějící token
```

### Stabilní JSON tvar zpráv

```json
[
  { "user": "U0123456789", "text": "zpráva", "ts": "1718870400.123456" },
  { "user": null,          "text": "bot msg", "ts": "1718870461.234567" }
]
```

- `user` je `null` pro zprávy od botů bez `user` pole
- `text` je `""` když Slack pole chybí
- `ts` je Slack timestamp (řetězec, unikátní ID zprávy)
- Pole je seřazeno od nejstarší po nejnovější (reverse oproti API)
