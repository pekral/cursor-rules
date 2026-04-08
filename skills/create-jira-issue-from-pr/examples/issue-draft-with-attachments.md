# Example: JIRA Issue Draft With Attachments

Source PR: #145 — feat(reports): add PDF export for monthly summaries

---

## Cíl

Přidat možnost exportu měsíčních souhrnů do PDF formátu podle přiloženého vzoru.

## Původní zadání (beze změn)

Zákazníci potřebují exportovat měsíční souhrny do PDF. Vzor je v příloze (screenshot). Export musí obsahovat hlavičku s logem firmy a tabulku s přehledem.

## Technický kontext z PR

- PR přidává nový `PdfExportService` v `src/Services/Export/`
- Screenshot v PR ukazuje požadovaný layout: hlavička s logem, tabulka s 5 sloupci
- Review od @tech-lead požaduje lazy loading pro velké datasety (> 1000 řádků)
- Příloha (screenshot) úspěšně stažena a analyzována

## Požadavky pro implementaci

- [ ] Implementovat `PdfExportService` s podporou hlavičky a tabulkového layoutu
- [ ] Přidat lazy loading pro datasety nad 1000 řádků
- [ ] Integrovat logo firmy do hlavičky exportu
- [ ] Přidat endpoint `GET /reports/monthly/{id}/pdf`

## Akceptační kritéria

- [ ] PDF export obsahuje hlavičku s logem a tabulku podle vzoru
- [ ] Export datasetu s 5000 řádky se dokončí do 10 sekund
- [ ] PDF je validní a otevře se v běžných prohlížečích (Chrome, Firefox)
- [ ] Endpoint vrací 404 pro neexistující report

## Poznámky

- Zdroj: https://github.com/org/repo/pull/145
- Příloha (screenshot layoutu) byla analyzována a požadavky z ní jsou zahrnuty výše.
- Výstup je naformátovaný pro JIRA issue, původní zadání zůstalo obsahově beze změn.
