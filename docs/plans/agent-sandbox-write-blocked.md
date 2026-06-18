# Plán — sandbox blokuje zápis souborů u write-agentů (talos)

## Goal
Když je dispatchnutý write-agent (`talos`) zablokován harness sandboxem / permission-mode při zápisu souborů, běh se **zastaví a nahlásí jasný blocker s remediací**, místo aby hlavní vlákno tiše dokončilo implementaci mimo delegovaný a reviewovaný pipeline. Zároveň je v dokumentaci jasně popsaný prerekvizitní předpoklad prostředí (povolit subagentům Edit/Write) a jak ho splnit.

## Architecture
Příčina je na úrovni Claude Code harnessu (sandbox / permission-mode pro neinteraktivní subagenty), **ne** v definicích agentů — `talos` má `Write`/`Edit` v `tools`. Repozitář proto nemůže zápis „udělit", ale vlastní dvě věci, které tenhle případ řeší:

- **`rules/compound-engineering/general.mdc`** (nebo nové `rules/agents/general.mdc`, `alwaysApply: true`) — chování hlavního vlákna a orchestrátora: write-blocked subagent = hard blocker → stop + report, nikdy tiché převzetí práce do hlavního vlákna.
- **`agents/talos.md` + `agents/daidalos.md`** — handoff contract: `talos` při odmítnutém zápisu vrací `Blocked: sandbox denied file write` s remediací; `daidalos` ho eskaluje uživateli.
- **`docs/agents.md` (Troubleshooting) + `README.md`** — prerekvizita prostředí: session musí povolit subagentům Edit/Write a jak to zapnout. **Poznatek (ověřeno proti oficiální dokumentaci):** `defaultMode: acceptEdits` + `permissions.allow: ["Edit","Write"]` jsou *nutné, ale ne dostatečné* — dispatchnutý subagent přesto narazí na dvě hranice, které hlavní vlákno nemá: (1) **background** subagent auto-zamítne každý zápis, který by jinak vyžadoval prompt, a (2) OS-level **filesystem sandbox** ve výchozím stavu povolí zápis jen do cwd + `$TMPDIR`. Skutečná remediace je tedy `sandbox` vrstva (`"sandbox": { "enabled": true, "filesystem": { "allowWrite": ["."] } }`) a/nebo re-dispatch agenta v *foreground*, ne permission-mode. Zdroje: https://code.claude.com/docs/en/sandboxing , https://code.claude.com/docs/en/sub-agents .

~~Záměrně se **nemění installer** tak, aby automaticky překlápěl bezpečnostní nastavení (sandbox off / allow všech editů) — to by bylo příliš široké a v rozporu s „jen na vyžádání" a security pravidly.~~

**Update (na vyžádání uživatele):** installer **smí** to nastavení doplnit, ale **jen jako opt-in flag** `--allow-subagent-writes` (vzor `--allow-bundled-scripts`), nikdy automaticky. Zapisuje úzký `"sandbox": { "enabled": true, "filesystem": { "allowWrite": ["."] } }` do **projektového** `.claude/settings.json` (editor `claude`/`all`), existující `sandbox` blok nechá být a vygenerovaný blok validuje (`InstallerClaudeSettings::validateSandboxSettings`), aby nešel zapsat poškozený. „Jen na vyžádání" zůstává splněné — výchozí chování nepřeklápí nic.

## Implementation steps
1. Přidat behaviorální pravidlo (alwaysApply): sandbox-write-blocked write-agent je hard blocker → stop + report + remediace; zákaz tichého dokončení v hlavním vlákně.
2. Rozšířit handoff contract v `agents/talos.md` o terminální stav `Blocked: sandbox denied file write` (+ remediace) a v `agents/daidalos.md` o jeho eskalaci.
3. Přidat Troubleshooting sekci do `docs/agents.md` a krátkou poznámku do `README.md` s konkrétním návodem na povolení zápisu subagentům.
4. `composer build` (sync `.claude/` + fixers + checks + skill-check + testy) musí být zelený.

## Sources
- `agents/talos.md` (`tools: Read, Write, Edit, Glob, Grep, Bash`) — zápis povolen na úrovni agenta.
- `agents/daidalos.md` — delegation model, one-level nesting, handoff contract.
- `src/InstallerClaudeSettings.php` — installer spravuje jen `permissions.allow` (bundled scripts) + `includeCoAuthoredBy`, žádný sandbox klíč.
- `.claude/settings.local.json` — pouze `permissions.allow`, žádný `sandbox` / `defaultMode`.
- `docs/agents.md` — „Subagents of an agent" (one-level nesting), „Distribution".

## Success criteria
- Při zablokovaném zápisu subagent vrací jednoznačný blocker a hlavní vlákno NEPokračuje tichou implementací.
- Dokumentace popisuje prerekvizitu prostředí a postup, jak povolit zápis subagentům.
- `composer build` zelený (0 errors), `composer skill-check` 0 errors.
- Žádné automatické překlápění bezpečnostních nastavení v installeru.
