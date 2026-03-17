# Analýza skills a doporučení k referenčním souborům

## 1. Validita pro Cursor editor

**Skills jsou platné.** Cursor načítá každý skill z adresáře `skill-name/` přes soubor `SKILL.md` (frontmatter `name` + `description` + tělo). Žádný skill nepřesahuje doporučených 500 řádků (nejdelší je `mysql-problem-solver` s 368 řádky), takže z hlediska délky není rozdělení nutné.

**Opravené reference (neplatné cesty/typos):**

| Soubor | Problém | Oprava |
|--------|---------|--------|
| resolve-github-issue, resolve-jira-issue, resolve-bugsnag-issue | `class-refacforing` | `class-refactoring` |
| resolve-github-issue | `code-review-github.md` | `code-review-github/SKILL.md` |
| resolve-github-issue, resolve-jira-issue, resolve-bugsnag-issue | `@./cursor/skills/...` | `@.cursor/skills/...` |
| test-like-human, code-review-github, code-review-jira | `interactive-browser-testing` | `interactive-testing` |

Reference na jiné skills (`@.cursor/skills/.../SKILL.md`) Cursor/agent řeší načtením příslušného souboru. Po opravách jsou všechny vazby platné.

---

## 2. Je rozdělení na referenční soubory potřeba?

**Pro validitu ne.** Cursor nevyžaduje `reference.md` ani `examples.md`. Skill je validní, pokud existuje `SKILL.md` s platným frontmatter a tělem.

**Pro doporučený styl (create-skill) je rozdělení volitelné:**

- Doporučení: *„SKILL.md do 500 řádků, detaily do reference/examples.“*
- Všechny tvé skills jsou pod 500 řádků → rozdělení není **nutné**.
- Rozdělení má smysl hlavně pro:
  - **Progressive disclosure** – v SKILL.md zůstane „kdy použít“ a hlavní postup, detailní checklisty / workflow / příklady jdou do zvláštních souborů.
  - **Čitelnost** – méně textu v jednom souboru.
  - **Budoucí rozšíření** – když skill poroste nad ~400 řádků, máš už strukturu připravenou.

---

## 3. Doporučení k rozdělení (volitelné)

Podle konvence *„v SKILL.md jen podstatné, zbytek do reference/examples“*:

### 3.1 `mysql-problem-solver` (368 řádků)

**Aktuálně:** jeden dlouhý soubor (workflow, EXPLAIN, indexy, výstup, terminál, Laravel, příklady).

**Navrhované rozdělení:**

- **SKILL.md** (~80–100 řádků): Purpose, When to use / When not to use, Core goals, krátký přehled workflow (1–2 věty na krok), Behavior rules, odkaz na referenci.
- **reference.md** (nový):  
  - Required investigation workflow (kroky 1–8 v plném znění),  
  - Output format (šablona reportu),  
  - Terminal guidance (příkazy, .env, mysql),  
  - Laravel-specific guidance,  
  - Example prompts.

V SKILL.md přidat na konec sekce „Additional resources“:

```markdown
## Další podklady
- Detailní workflow, výstupní formát a příklady: [reference.md](reference.md)
```

Agent pak při použití skillu načte SKILL.md a v případě potřeby i reference.md.

### 3.2 `code-review` (96 řádků)

**Aktuálně:** jeden hustý soubor (constraint + dlouhý checklist).

**Volitelné rozdělení:**

- **SKILL.md**: Constraint, hlavní kroky (Cancel CR při confliktu, compliance s rules, SRP/SOLID, severity, deliver), odkaz na checklist.
- **reference.md** nebo **CHECKLIST.md**: Celý detailní checklist (SQL, Laravel vrstvy, N+1, transakce, testy, atd.).

V SKILL.md:

```markdown
- Kompletní review checklist: [reference.md](reference.md)
```

### 3.3 Ostatní skills

- **test-like-human** (206), **interactive-testing** (213), **create-issue** (134), **security-review** (124), **class-refactoring-plan** (117): délka je v pohodě, rozdělení není potřeba, pokud je neplánuješ výrazně rozšiřovat.
- Zbytek skills je krátký (cca do 80 řádků) – referenční soubory nedávají velký benefit.

---

## 4. Jak Cursor referenční soubory čte

- **Načítání skillu:** Cursor používá `SKILL.md` (název, popis, instrukce). Referenční soubory se nenačítají automaticky.
- **Kdy se načtou:** Když v SKILL.md je explicitní instrukce typu „Pro detailní workflow viz [reference.md](reference.md)“ nebo „Pro checklist načti reference.md“, agent může použít nástroj Read a soubor načíst.
- **Doporučení:** Odkazy jen **jednoúrovňové** – z SKILL.md přímo na `reference.md` nebo `examples.md`. Žádné reference.md → další podstránky (create-skill varuje před „deeply nested references“).

Struktura adresáře skillu může vypadat takto:

```
skill-name/
├── SKILL.md        # povinný – hlavní instrukce, odkazy na reference
├── reference.md    # volitelný – detailní workflow, checklisty, šablony
└── examples.md     # volitelný – příklady použití
```

---

## 5. Shrnutí

| Otázka | Odpověď |
|--------|---------|
| Jsou skills validní pro Cursor? | Ano. Po opravě referencí ano. |
| Musíš je dělit na referenční soubory? | Ne. Žádný skill nepřesahuje 500 řádků. |
| Dává rozdělení smysl? | Ano, volitelně u `mysql-problem-solver` (a případně `code-review`) pro lepší čitelnost a progressive disclosure. |
| Jak na to? | V SKILL.md nechat „kdy a proč“ + hlavní postup, do `reference.md` přesunout dlouhé checklisty/workflow/šablony a v SKILL.md na ně odkazovat jedním odkazem. |

Pokud chceš, můžu konkrétně navrhnout úpravy `mysql-problem-solver` (rozsekání na SKILL.md + reference.md) krok za krokem.
