# IIS – Informační systém pro správu událostí

Webový informační systém pro správu událostí a akcí vytvořený jako projekt do předmětu **Informační systémy (IIS)** na FIT VUT v Brně (varianta *Správa událostí*).

| Položka | Hodnota |
| --- | --- |
| Předmět | Informační systémy (IIS), FIT VUT v Brně |
| Varianta zadání | Systém pro správu událostí |
| Autoři | *(doplnit jména a loginy členů týmu)* |
| Veřejná URL aplikace | *(doplnit URL po nasazení na server Eva / hosting)* |
| Komentované video (~5 min) | *(doplnit odkaz na video)* |

---

## 1. Uživatelské role a funkce systému

- **Neregistrovaný uživatel**
  - Prochází a filtruje veřejný katalog událostí (fulltext, hierarchické kategorie včetně podkategorií, místo konání, termín, vstupné zdarma/placené).
  - Zobrazuje detail události, katalog míst konání a strom kategorií.
  - Může se zaregistrovat a přihlásit.
- **Registrovaný uživatel (`user`)**
  - Přihlašování uživatelským jménem nebo e-mailem, úprava profilu a změna hesla.
  - **Účastník události:** registruje se na události s výběrem kategorie vstupného (s transakční kontrolou kapacity události i konkrétního vstupného), sleduje stav registrací, hlásí úhradu placeného vstupného, může registraci před začátkem zrušit, po skončení události přidává hodnocení (1–5 hvězdiček) a komentář.
  - **Osobní kalendář:** přehled nadcházejících a proběhlých událostí (jako účastník i pořadatel), export do `.ics` (RFC 5545) a odkaz pro přidání akce do Google kalendáře.
  - **Správce události:** zakládá události (koncept nebo rovnou k moderaci), definuje více typů vstupného (včetně bezplatných i s omezenou kapacitou), nahrává titulní fotku, spravuje účastníky, potvrzuje nebo odmítá úhrady, navrhuje nová místa konání a kategorie a sleduje stav svých návrhů.
- **Moderátor (`moderator`)**
  - Schvaluje a zamítá události (včetně automatického upozornění na časovou kolizi ve stejném místě).
  - Schvaluje, zamítá, přidává, upravuje a maže místa konání a hierarchické kategorie.
  - Moderuje komentáře (skrývání a opětovné zobrazení).
- **Administrátor (`admin`)**
  - Má všechna oprávnění moderátora.
  - Spravuje uživatelské účty (vytváření, úpravy, změna role, reset hesla, aktivace/deaktivace, mazání účtů bez referenčních vazeb).

### Implementovaná rozšíření
- **Propojení s Google kalendářem** – přímý odkaz z detailu události s předvyplněným názvem, časem, popisem a místem konání.
- **Export osobního kalendáře do `.ics`** – generování standardního iCalendar souboru (`RFC 5545`) pro import do kalendářových klientů.

---

## 2. Použité technologie a verze knihoven

- **Backend:** PHP `8.1+`, Laravel Framework `10.50.4` (`laravel/framework`), `laravel/sanctum 3.3.3`, `guzzlehttp/guzzle 7.16.3`
- **Databáze:** MySQL / MariaDB (`InnoDB`, `utf8mb4`), přístup přes Eloquent ORM a migrace Laravelu
- **Frontend:** HTML5, CSS3, Blade šablony, Bootstrap `5.3.3` (uložen lokálně v `public/assets/vendor/`), čistý JavaScript (ověřeno v Chrome a Firefox)
- **Testování (lokálně):** PHPUnit `10.5.67` (`tests/Feature/`), Faker `1.24.1`

---

## 3. Architektura a mapování případů užití

Aplikace používá serverovou MVC architekturu Laravelu doplněnou o servisní vrstvu v `app/Services/` (transakční zápisy, kontrola kapacity, práce se stromem kategorií, nahrávání obrázků, generování `.ics`) a vlastní middleware v `app/Http/Middleware/`.

| Případ užití | Routy (`routes/web.php`) | Controller / Service / Middleware | Šablony (`resources/views/`) |
| --- | --- | --- | --- |
| Úvodní stránka a rychlé hledání | `GET /` | `HomeController` | `home.blade.php` |
| Registrace, přihlášení, odhlášení, timeout relace | `GET/POST /registrace`, `GET/POST /prihlaseni`, `POST /odhlaseni` | `AuthController`, `EnsureSessionIsValid` | `auth/register.blade.php`, `auth/login.blade.php` |
| Správa vlastního profilu a hesla | `GET/POST /profil`, `POST /profil/heslo` | `ProfileController` | `profile/edit.blade.php` |
| Správa uživatelů a rolí (admin) | `/admin/uzivatele/*` | `AdminUserController`, `RequireRole` | `admin/users/index.blade.php`, `admin/users/form.blade.php` |
| Katalog událostí, filtrování a detail | `GET /udalosti`, `GET /udalosti/{slug}` | `EventController`, `CategoryService` | `events/index.blade.php`, `events/show.blade.php` |
| Zakládání a správa událostí + vstupné | `GET /moje-udalosti`, `GET/POST /udalosti/nova`, `/udalosti/{event}/upravit\|odeslat\|zrusit` | `EventController`, `EventService`, `ImageUploadService` | `events/mine.blade.php`, `events/form.blade.php` |
| Registrace na událost a potvrzování plateb | `POST /udalosti/{slug}/registrace`, `GET /moje-registrace`, `GET /udalosti/{event}/ucastnici`, `POST /registrace/{registration}/zrusit\|uhrazeno\|potvrdit\|odmitnout` | `RegistrationController`, `RegistrationService` | `registrations/index.blade.php`, `registrations/participants.blade.php` |
| Osobní kalendář, `.ics` export, Google kalendář | `GET /muj-kalendar`, `GET /muj-kalendar.ics` | `CalendarController`, `IcsService`, `EventController` | `calendar/index.blade.php` |
| Hodnocení a komentáře po skončení akce | `POST /udalosti/{slug}/hodnoceni`, `POST /udalosti/{slug}/komentar`, `POST /komentare/{comment}/smazat` | `FeedbackController` | `events/show.blade.php` |
| Katalog míst, návrh místa, moje návrhy | `GET /mista`, `GET /mista/{venue}`, `GET/POST /mista/navrhnout`, `GET /moje-navrhy` | `VenueController`, `ImageUploadService` | `venues/*.blade.php`, `proposals/index.blade.php` |
| Hierarchické kategorie a návrh kategorie | `GET /kategorie`, `GET/POST /kategorie/navrhnout` | `CategoryController`, `CategoryService` | `categories/index.blade.php`, `categories/create.blade.php` |
| Moderace událostí a kontrola kolizí | `/moderace/udalosti/*` | `ModerationEventController`, `EventService`, `RequireRole` | `moderation/events/index.blade.php`, `moderation/events/show.blade.php` |
| Moderace a správa míst konání | `/moderace/mista/*` | `ModerationVenueController`, `ImageUploadService`, `RequireRole` | `moderation/venues/index.blade.php`, `moderation/venues/form.blade.php` |
| Moderace a správa kategorií | `/moderace/kategorie/*` | `ModerationCategoryController`, `CategoryService`, `RequireRole` | `moderation/categories/index.blade.php`, `moderation/categories/form.blade.php` |
| Moderace komentářů | `/moderace/komentare/*` | `ModerationCommentController`, `RequireRole` | `moderation/comments/index.blade.php` |

---

## 4. Databázové schéma

Schéma je definováno v migracích v adresáři `database/migrations/` a inicializační data jsou v `database/seeders/DatabaseSeeder.php`. Aplikace využívá 8 vlastních tabulek s cizími klíči a unikátními omezeními:

- **`users`** – uživatelské účty (`username`, `email`, `password`, `name`, `role`, `is_active`, `auth_version`, `last_login_at`).
- **`venues`** – místa konání (název, adresa, popis, volitelná fotka, `status`, `proposed_by`, `reviewed_by`, `rejection_reason`).
- **`categories`** – hierarchické kategorie s odkazem `parent_id` (max. hloubka 4 úrovně, kontrola cyklů v `CategoryService`), unikátní `slug`, stav schválení.
- **`events`** – události (unikátní `slug`, zobrazovaný kód `U-RRRR-NNNN`, `starts_at`/`ends_at` v UTC, `venue_id`, `category_id`, `manager_id`, volitelná `capacity`, `cover_path`, `payment_note`, stav a důvody zamítnutí/zrušení).
- **`ticket_types`** – typy vstupného navázané na událost (`name`, `price` jako `DECIMAL(10,2)`, volitelná `capacity`).
- **`registrations`** – registrace uživatelů na události (`event_id`, `user_id`, `ticket_type_id`, snapshot ceny `unit_price`, stav `pending`/`confirmed`/`cancelled`, čas nahlášení a potvrzení úhrady).
- **`ratings`** – hodnocení 1–5 hvězdiček s unikátním omezením `(event_id, user_id)`.
- **`comments`** – komentáře potvrzených účastníků po skončení události s příznakem skrytí moderátorem (`is_hidden`, `hidden_by`, `hidden_at`).

---

## 5. Ošetření vstupů a bezpečnost

- **Formuláře:** povinná pole jsou viditelně odlišena od nepovinných (`*` / `(nepovinné)`), cizí klíče se vybírají ze seznamů (místa, kategorie, vstupné), datum a čas události jsou předvyplněné a při chybě validace zůstávají správně vyplněné hodnoty zachovány (`old()`).
- **Relace a oprávnění:** automatické odhlášení po 30 minutách nečinnosti (`EnsureSessionIsValid`), okamžité zneplatnění starých relací při změně role, hesla nebo deaktivaci účtu (`auth_version`), kontrola rolí přes middleware `RequireRole`.
- **Konzistence dat:** registrace na událost probíhají v databázové transakci s pesimistickým zamykáním (`lockForUpdate()`), úprava schválené události ji vrací zpět ke schválení moderátorem a mazání uživatelů/míst/kategorií respektuje existující vazby.
- **Zabezpečení webu:** CSRF ochrana formulářů, automatické escapování výstupu v Blade, hashování hesel (`bcrypt`), validace MIME/velikosti nahrávaných obrázků a zákaz spouštění PHP skriptů v `public/uploads/.htaccess`.

---

## 6. Instalace a spuštění

### Požadavky
- PHP 8.1+ s rozšířeními `pdo_mysql`, `gd`, `zip`, `mbstring`, `openssl`
- Composer
- MySQL nebo MariaDB (v produkčním prostředí se nepoužívá SQLite)

### Postup instalace
```bash
composer install
cp .env.example .env
php artisan key:generate
```

V souboru `.env` nastavte připojení k MySQL/MariaDB databázi (`DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) a `APP_URL`.

Poté vytvořte databázové schéma a nahrajte ukázková data:
```bash
php artisan migrate --seed
```

Lokální vývojový server:
```bash
php artisan serve
```

### Nasazení na server (např. Eva)
- Nasměrujte webový kořen (`DocumentRoot` / `.htaccess`) na adresář `public/`.
- Nastavte kódování UTF-8 a práva zápisu pro webový server do adresářů `storage/`, `bootstrap/cache/` a `public/uploads/`.
- Do produkční verze na serveru nenahrávejte soubory s testovacími hesly.

### Testy
Automatické integrační testy (`tests/Feature/AuthAndAdminTest.php`, `tests/Feature/EventWorkflowTest.php`) se spouští příkazem:
```bash
php artisan test
```

---

## 7. Ukázková data a dokumentace k odevzdání

Seeder `DatabaseSeeder` vytváří ukázková data pro všechny role (`admin`, `moderator`, běžní uživatelé – pořadatelé i účastníci), místa konání, hierarchické kategorie, proběhlé i nadcházející události, registrace, hodnocení a komentáře.

V souladu s pokyny zadání nejsou v tomto `README.md` uvedena hesla k testovacím účtům. Kompletní dokumentace k odevzdání do IS (včetně přehledu testovacích účtů) je připravena v souboru `docs/doc.html`.
