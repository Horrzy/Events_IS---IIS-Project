# IIS – Informační systém pro správu událostí

Webový informační systém pro správu akcí a událostí (semestrální projekt předmětu **IIS**, FIT VUT v Brně, varianta *Správa událostí*).

## Použité technologie
- **Backend:** PHP 8.1 + Laravel 10 (`laravel/framework ^10.10`)
- **Databáze:** MySQL / MariaDB (`InnoDB`, `utf8mb4`)
- **Frontend:** Blade šablony + Bootstrap 5.3.3 (lokálně v `public/assets/vendor/`) + čistý JavaScript
- **Testy:** PHPUnit 10 (`tests/Feature/`)

## Hlavní funkce podle rolí
- **Neregistrovaný uživatel:** prochází a filtruje schválené události, místa konání a hierarchické kategorie.
- **Registrovaný uživatel (`user`):**
  - registruje se na události (transakční kontrola kapacity), spravuje profil a osobní kalendář včetně exportu `.ics` a odkazu do Google kalendáře,
  - po skončení akce hodnotí (1–5 hvězdiček) a komentuje události, kterých se zúčastnil,
  - zakládá vlastní události (**správce události**), navrhuje nová místa a kategorie a potvrzuje úhrady vstupného.
- **Moderátor (`moderator`):** schvaluje/zamítá události, místa a kategorie, spravuje katalog a moderuje komentáře.
- **Administrátor (`admin`):** má všechna práva moderátora a spravuje uživatelské účty a role.

## Lokální spuštění
```bash
composer install
cp .env.example .env
php artisan key:generate
# V .env upravte připojení k MySQL/MariaDB databázi (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
php artisan migrate --seed
php artisan serve
```

## Spuštění testů
Testy běží nad izolovanou testovací databází `iis_test` (nastaveno v `phpunit.xml`):
```bash
php artisan test
```

## Dokumentace
Podrobná dokumentace k implementaci, mapování případů užití na controllery/servisy a přehled testovacích účtů jsou v souboru `docs/doc.html`.
