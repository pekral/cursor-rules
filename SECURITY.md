# Security

## Plugin trust model

`agentic-vibes/laravel-agent-skills` is a **Composer plugin** (`"type": "composer-plugin"`). Composer requires explicit opt-in before running any plugin — including this one — to guard against supply-chain attacks from unknown packages.

### `allow-plugins` in `composer.json`

When you `composer require agentic-vibes/laravel-agent-skills`, Composer may ask:

```
Do you trust "agentic-vibes/laravel-agent-skills" to execute code and wish to enable it now? (yes/no)
```

If you answer `yes`, Composer writes the following entry to your project's `composer.json`:

```json
{
  "config": {
    "allow-plugins": {
      "agentic-vibes/laravel-agent-skills": true
    }
  }
}
```

This is the **standard Composer plugin-trust mechanism** (`allow-plugins`). It is project-scoped, version-controlled alongside your `composer.json`, and must be deliberately set to `true` by you. The package never modifies `allow-plugins` on its own behalf — you remain in full control of which plugins your project trusts.

If you prefer to give a non-interactive answer (e.g. in CI), you can pass the flag explicitly:

```bash
composer require agentic-vibes/laravel-agent-skills --dev --no-plugins   # skip the plugin during install
composer config allow-plugins.agentic-vibes/laravel-agent-skills true     # then grant trust manually
```

### Auto-install hook

Granting `allow-plugins: true` also enables the package's Composer plugin to react to `post-install-cmd` and `post-update-cmd` events. By default the plugin does **nothing** on these events. The auto-install hook is activated only when you add the following opt-in to your project's `composer.json`:

```json
{
  "extra": {
    "agent-skills": {
      "auto-install": true,
      "editor": "claude"
    }
  }
}
```

When `auto-install` is `true`, every `composer install` or `composer update` automatically runs `Installer::run(['agent-skills', 'install', '--force', '--editor=<editor>'])` — the same installer that you would call manually, with `--force` and without any opt-in flags (`--allow-bundled-scripts`, `--allow-subagent-writes`). **Security implication:** any package that ships a `post-install-cmd` / `post-update-cmd` hook and is trusted via `allow-plugins` can trigger code execution during a routine `composer install`. Review the `extra.agent-skills` block in your `composer.json` before enabling `auto-install`, and treat it the same way you treat other Composer script hooks.

See also: [README — Automatic Installation via Composer Plugin](README.md#automatic-installation-via-composer-plugin).

## Installer security flags

All security-sensitive installer flags are **opt-in by design** — the package grants no additional permissions by default.

### `--allow-bundled-scripts`

**What it does.** When `--editor=claude` or `--editor=all` is used alongside this flag, the installer idempotently appends a narrow allow-list for this package's bundled scripts to `~/.claude/settings.json` (`permissions.allow`):

```
Bash(*skills/code-review-github/scripts/load-issue.sh:*)
Bash(*skills/code-review-jira/scripts/load-issue.sh:*)
```

These two patterns pre-approve the GitHub and JIRA `load-issue.sh` scripts that the `code-review-github` and `code-review-jira` skills invoke, so Claude Code stops prompting for confirmation on every run.

**What it does not do.** It grants access only to the two specific, version-controlled scripts shipped in this package. All other entries in `~/.claude/settings.json` are preserved untouched. The flag has no effect when `--editor=cursor` or `--editor=codex` is used, or when neither `HOME` nor `USERPROFILE` is available.

**Implementation reference.** `src/InstallerClaudeSettings.php` — `applyIfRequested()` → `ensureBundledScriptPermissions()`.

See also: [README — CLI Switches](README.md#cli-switches).

### `--allow-subagent-writes`

**What it does.** When `--editor=claude` or `--editor=all` is used alongside this flag, the installer prepends two scoped permission entries to `permissions.allow` in the project's `.claude/settings.local.json`:

```
Edit(//<absolute-project-path>/**)
Write(//<absolute-project-path>/**)
```

These entries pre-allow dispatched subagents (e.g. `talos`) to write files inside the project tree without requiring an interactive approval on each operation. A dispatched subagent runs non-interactively, so a write is denied at runtime unless the path is already in `permissions.allow`.

**Why `settings.local.json` and not `settings.json`.** The entries carry a machine-absolute path — they are personal and not portable. `settings.local.json` is git-ignored by Claude Code by default, so the absolute path never leaks into version control.

**Safety guarantees.** The flag is idempotent: it only adds missing entries and never removes or modifies existing ones. After writing, the installer reads the file back and validates that every required entry is present (`InstallerClaudeSettings::validateSubagentWritePermissions()`), so a malformed file can never be produced. The package grants nothing by default — this flag is the explicit, human-owned opt-in.

**Implementation reference.** `src/InstallerClaudeSettings.php` — `applySubagentWritesIfRequested()` → `ensureSubagentWritesEnabled()`.

See also: [docs/agents.md — Troubleshooting (subagent file writes blocked)](docs/agents.md#troubleshooting--subagent-file-writes-blocked) and [docs/plans/agent-sandbox-write-blocked.md](docs/plans/agent-sandbox-write-blocked.md).

## Files this package writes

| Path | Created by | Condition |
|------|-----------|-----------|
| `~/.claude/settings.json` — sets `includeCoAuthoredBy: false` | `install` (unconditional) | `--editor=claude` or `--editor=all`; `HOME`/`USERPROFILE` set; key absent — never overwrites an existing value |
| `~/.claude/settings.json` — adds `permissions.allow` bundled-script entries | `--allow-bundled-scripts` | `--editor=claude` or `--editor=all`; `HOME`/`USERPROFILE` set |
| `.claude/settings.local.json` | `--allow-subagent-writes` | `--editor=claude` or `--editor=all` |
| `.cursor/rules/`, `.claude/rules/`, `.codex/rules/` | `install` | always, for the chosen editor |
| `.cursor/skills/`, `.claude/skills/`, `.codex/skills/` | `install` | always, for the chosen editor |
| `.claude/agents/` | `install` | `--editor=claude` or `--editor=all` only |
| `CLAUDE.md` | `install` | `--editor=claude` or `--editor=all`; never overwrites an existing file |

The installer never writes outside the project directory and the user's home directory, and it never modifies `composer.json` or any project source file.

## Reporting a vulnerability

If you discover a security issue in this package, please report it privately so it can be addressed before public disclosure.

**Contact:** open a [GitHub Security Advisory](https://github.com/agentic-vibes/laravel-agent-skills/security/advisories/new) (preferred) or email `kral.petr.88@gmail.com`.

Please include a description of the issue, reproduction steps, and the potential impact. You will receive a response within a reasonable time. Public disclosure is coordinated after a fix is available.
