# Example: Complete JIRA Issue Draft

Source PR: #87 — refactor(api): restructure validation layer

---

## Cíl

Restrukturalizovat validační vrstvu API tak, aby byly validace konzistentní napříč všemi endpointy a snížil se počet duplicitních pravidel.

## Původní zadání (beze změn)

Potřebujeme sjednotit validaci vstupů na API. Momentálně každý controller validuje po svém a jsou tam duplicity. Viz komentáře v PR.

## Technický kontext z PR

- PR diff ukazuje nekonzistentní validaci v `src/Controllers/UserController.php` a `src/Controllers/OrderController.php`
- Review od @senior-dev identifikoval chybějící validaci na `PATCH /orders/{id}` endpointu
- Testy ve `tests/Validation/` pokrývají pouze happy path scénáře

## Požadavky pro implementaci

- [ ] Vytvořit sdílené validační pravidla v `src/Validation/Rules/`
- [ ] Nahradit inline validaci ve všech controllerech sdílenými pravidly
- [ ] Přidat validaci na `PATCH /orders/{id}` endpoint
- [ ] Doplnit testy pro chybové scénáře (nevalidní vstup, chybějící povinná pole)

## Akceptační kritéria

- [ ] Žádný controller neobsahuje inline validační pravidla
- [ ] Všechny API endpointy vrací 422 s popisem chyby při nevalidním vstupu
- [ ] Test coverage validační vrstvy je minimálně 80%
- [ ] Existující API kontrakty se nezměnily (zpětná kompatibilita)

## Poznámky

- Zdroj: https://github.com/org/repo/pull/87
- Výstup je naformátovaný pro JIRA issue, původní zadání zůstalo obsahově beze změn.
