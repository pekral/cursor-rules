# Plán — sandbox blokuje zápis souborů u write-agentů (talos)

## Goal
Když je dispatchnutý write-agent (`talos`) zablokován harness sandboxem / permission-mode při zápisu souborů, běh se **zastaví a nahlásí jasný blocker s remediací**, místo aby hlavní vlákno tiše dokončilo implementaci mimo delegovaný a reviewovaný pipeline. Zároveň je v dokumentaci jasně popsaný prerekvizitní předpoklad prostředí (povolit subagentům Edit/Write) a jak ho splnit.

## Architecture
Příčina je na úrovni Claude Code harnessu (sandbox / permission-mode pro neinteraktivní subagenty), **ne** v definicích agentů — `talos` má `Write`/`Edit` v `tools`. Repozitář proto nemůže zápis „udělit", ale vlastní dvě věci, které tenhle případ řeší:

- **`rules/compound-engineering/general.mdc`** (nebo nové `rules/agents/general.mdc`, `alwaysApply: true`) — chování hlavního vlákna a orchestrátora: write-blocked subagent = hard blocker → stop + report, nikdy tiché převzetí práce do hlavního vlákna.
- **`agents/talos.md` + `agents/daidalos.md`** — handoff contract: `talos` při odmítnutém zápisu vrací `Blocked: sandbox denied file write` s remediací; `daidalos` ho eskaluje uživateli.
- **`docs/agents.md` (Troubleshooting) + `README.md`** — prerekvizita prostředí: session musí povolit subagentům Edit/Write (permission-mode `acceptEdits`, příp. allow `Edit`/`Write` v `settings.json`, nebo úprava sandboxu) a jak to zapnout.

Záměrně se **nemění installer** tak, aby automaticky překlápěl bezpečnostní nastavení (sandbox off / allow všech editů) — to by bylo příliš široké a v rozporu s „jen na vyžádání" a security pravidly.

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
