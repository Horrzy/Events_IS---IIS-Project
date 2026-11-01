# IIS – systém pro správu událostí

Informační systém pro správu akcí: uživatelé zakládají události, navrhují místa a kategorie, moderátoři je schvalují, účastníci se registrují a po akci hodnotí. Semestrální projekt předmětu IIS, FIT VUT, varianta „Správa událostí“.

## Rychlé odkazy

| Dokument | K čemu slouží |
|---|---|
| [`implementacni-plan.md`](implementacni-plan.md) | rozsah, datový model, routy, rozdělení práce, termíny, testy, nasazení, odevzdání |
| [`AGENTS.md`](AGENTS.md) | závazná pravidla pro vývoj a práci s AI |
| [`zadani.md`](zadani.md) | obecné zadání předmětu |
| [`spec-zadani.md`](spec-zadani.md) | specifické zadání naší varianty |
| [Issues](https://github.com/lukas-dudek/IIS/issues) | konkrétní úkoly, chyby a rozhodnutí |

## Stav projektu

Aktuální stav k 1. 11. 2026: analýza, plán a repozitářová struktura jsou hotové. Implementace začíná milníkem M0; repozitář zatím záměrně neobsahuje spustitelnou aplikaci.

| Oblast | Stav |
|---|---|
| analýza, role, datový model, plán | hotovo |
| pravidla pro vývoj a AI | hotovo |
| struktura repozitáře | hotovo |
| databázové schéma a seed | M0, návrh |
| skeleton aplikace a deploy na Evu | M0 |
| funkce P0 | M1–M2 |
| testy, dokumentace, video, odevzdání | M3–M4 |

## Tým

| Člen | Role | Odpovědnost |
|---|---|---|
| Lukáš Dudek | vedoucí, backend | jádro, autentizace, správa uživatelů, katalog míst a kategorií, moderace UI, deployment, bezpečnost, dokumentace |
| Honzík | backend + frontend | události, vyhledávání, registrace a vstupenky, platby, kalendář, komentáře a hodnocení, seed data |

Oba reviewujeme cizí změny. Vlastnictví souborů a pravidla pro zásah do cizí oblasti jsou v [`AGENTS.md`](AGENTS.md).

## Co systém umí

- **Neregistrovaný uživatel** prochází schválené události, místa a kategorie, vyhledává a filtruje.
- **Registrovaný uživatel** se registruje na akce, spravuje profil, zakládá události, navrhuje místa a kategorie, vidí osobní kalendář.
- **Správce události** upravuje svoji událost, odesílá ji ke schválení, vidí účastníky a potvrzuje úhrady.
- **Moderátor** schvaluje události, místa a kategorie, spravuje katalog a moderuje komentáře.
- **Administrátor** má práva moderátora a navíc spravuje uživatele a role.

Podrobná matice oprávnění a toky jsou v plánu, kapitoly 4 a 5.

## Technologie

| Vrstva | Volba |
|---|---|
| Backend | PHP 8.1, vlastní malé MVC, PDO, žádný framework ani ORM |
| Databáze | MySQL/MariaDB na serveru Eva, InnoDB, `utf8mb4` |
| Frontend | HTML5, CSS, Bootstrap 5.3 lokálně, vanilla JavaScript |
| Testy | PHPUnit 10 pouze lokálně, ruční E2E a bezpečnostní scénáře |
| Prostředí | Apache na Evě, `.htaccess`, UTF-8 |
| Verzování | Git + GitHub, privátní repozitář |

Runtime aplikace nemá Composer závislosti. Bootstrap je jediná třetí knihovna, bude nahraný na Evě a do odevzdávaného zipu nepatří; jeho verze bude v `doc.html`.

## Struktura repozitáře

```text
.
├── README.md
├── AGENTS.md
├── implementacni-plan.md
├── zadani.md
├── spec-zadani.md
├── .editorconfig
├── .gitignore
├── .github/
│   ├── pull_request_template.md
│   └── ISSUE_TEMPLATE/
├── app/
│   ├── bootstrap.php          (M0)
│   ├── Core/                  router, request, response, DB, session, auth, CSRF, validace
│   ├── Controllers/           tenké controllery
│   ├── Repositories/          explicitní SQL a mapování
│   ├── Services/              transakce a business pravidla
│   ├── Support/               helpery pro datum, slug, ICS
│   └── Views/
│       ├── layouts/           společný layout
│       ├── partials/          navigace, formulářové prvky, flash
│       └── errors/            403, 404, 500
├── bin/                       deploy a kontrolní skripty (M0)
├── config/
│   ├── config.example.php     (M0)
│   └── routes.php             (M0)
├── database/
│   ├── schema.sql             (M0)
│   └── seed.sql               (M0)
├── docs/
│   ├── diagrams/              ER, use case, architektura
│   ├── doc.html               dokumentace k odevzdání (M3)
│   └── obhajoba.md            vysvětlení klíčových částí (průběžně)
├── public/                    document root
│   ├── index.php              (M0)
│   ├── router.php             (M0, jen lokální server)
│   ├── .htaccess              (M0)
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   └── vendor/            Bootstrap, pouze na Evě
│   └── uploads/               uživatelské obrázky, mimo spouštění PHP
├── storage/
│   ├── logs/
│   └── tmp/
├── tests/
│   ├── bootstrap.php          (M1)
│   └── Unit/
└── vendor/                    Composer, pouze lokálně, necommitovat
```

Prázdné složky drží `.gitkeep`, aby byla struktura vidět ještě před první implementací. Soubory označené `(M0)`, `(M1)` nebo `(M3)` vzniknou v daném milníku; zbytek se doplňuje podle potřeby. Nic z toho není hotová funkce.

## Jak začít

1. Přečti [`implementacni-plan.md`](implementacni-plan.md), hlavně kapitoly 10–13, a [`AGENTS.md`](AGENTS.md).
2. Vyber issue nebo úkol z milníku M0/M1 a zkontroluj, kdo je vlastník oblasti.
3. Pracuj v krátké větvi `feat/<issue>-<nazev>` nebo `fix/...`, otevři PR a nech ho reviewovat druhým.
4. Po M0 bude v README doplněn přesný postup lokálního spuštění; příkazy jsou předepsané v plánu a `AGENTS.md`.

## Pravidla spolupráce

- `main` je vždy funkční a nasaditelná větev; do `main` se nepushuje přímo.
- Jeden soubor má jednoho vlastníka. Změnu v cizí oblasti řeš issue nebo PR, ne přepisem.
- Do gitu nikdy nepatří `config/config.php`, logy, produkční uploady ani osobní údaje.
- UI je česky, kód a databáze anglicky. Žádné emoji, anglické placeholdery ani lorem ipsum.
- Bez nové závislosti, ORM, frameworku nebo změny schématu bez dohody obou.
- Každá změna routy, UC nebo schématu znamená aktualizaci mapování v `docs/doc.html`.
- Každý kód musíme umět vysvětlit. AI je nástroj, ne omluva.

## Milníky

| Milník | Datum | Výstup |
|---|---|---|
| M0 | 2. 11. 2026 | schéma v1, skeleton, deploy spike, issues |
| M1 | 8. 11. 2026 | auth, role, katalog, vytvoření události |
| M2 | 15. 11. 2026 | všechny P0 toky funkční lokálně |
| Feature freeze | 16. 11. 2026 | jen opravy a dokumentace |
| M3 | 18. 11. 2026 | release candidate, QA, dokumentace, video |
| M4 | 21. 11. 2026 | odevzdání v IS |
| Deadline | 22. 11. 2026 23:59 | poslední možnost odevzdání |
| Provoz | do 13. 12. 2026 | aplikace musí zůstat dostupná |

## Nejbližší krok

M0: Honzík dodá návrh `database/schema.sql` a seed, Lukáš postaví skeleton a ověří deploy na Evě. Konkrétní úkoly a akceptační kritéria patří do Issues; plán je závazný rozsah.
