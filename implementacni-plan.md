# Implementační plán – Systém pro správu událostí (IIS)

## 1. Cíl projektu
Vytvořit webový informační systém pro správu událostí podle obecného zadání (`zadani.md`) a specifického zadání (`spec-zadani.md`), připravený pro běh na školním serveru **Eva** (PHP 8.1 + MySQL/MariaDB) i lokálně přes Docker.

---

## 2. Zvolené technologie a architektura

| Vrstva | Technologie | Odůvodnění |
|---|---|---|
| **Backend** | PHP 8.1 + **Laravel 10** | Nejnovější verze Laravelu plně kompatibilní s PHP 8.1 na serveru Eva. |
| **Architektura** | MVC + `Services` | Tenké `Controllers`, datové modely v `Eloquent ORM`, složitější a transakční operace (kapacity, schvalování, platby) v `app/Services/`. |
| **Autentizace** | Vlastní `AuthController` nad Laravel `Auth` | Jednoduché, čitelné a obhajitelné řešení bez zbytečných souborů z balíčků třetích stran. |
| **Databáze** | MySQL / MariaDB (`InnoDB`, `utf8mb4`) | Relační databáze spravovaná přes Laravel migrace a seedery. |
| **Frontend** | **Blade** šablony + **Bootstrap 5.3** (lokálně) + čistý JS | Serverové vykreslování bez nutnosti Node.js a buildu při nasazení na Evu. |
| **Testování** | PHPUnit 10 (Feature + Unit testy) | Ověření klíčových scénářů a oprávnění. |

---

## 3. Uživatelské role a oprávnění

1. **Neregistrovaný uživatel (host)**
   - Prochází a filtruje schválené události, místa konání a kategorie.
   - Zobrazuje detail události, místa a čte veřejné komentáře a hodnocení.

2. **Registrovaný uživatel (`user`)**
   - Spravuje svůj profil a heslo, prohlíží svůj osobní kalendář (včetně exportu `.ics` a odkazu do Google kalendáře).
   - **Jako účastník události:**
     - Registruje se na schválené události (vybírá kategorii vstupného) a může registraci zrušit před začátkem akce.
     - U placených akcí může označit platbu jako odeslanou („Uhradil jsem“).
     - Po skončení události (`ends_at`) může událost ohodnotit (1–5 hvězdiček) a přidat komentář (pokud má potvrzenou registraci).
   - **Jako správce události (`events.manager_id = user.id`):**
     - Zakládá nové události (koncept `draft`), upravuje je a odesílá ke schválení (`pending`).
     - Navrhuje nová místa konání a nové kategorie do katalogu (`pending`).
     - Vidí seznam registrovaných účastníků své události a potvrzuje nebo odmítá úhradu vstupného předem (`pending` → `confirmed`).

3. **Moderátor (`moderator`)**
   - Má všechna práva registrovaného uživatele.
   - Schvaluje a zamítá události (`pending` → `approved` / `rejected`).
   - Schvaluje, zamítá a spravuje místa konání a kategorie.
   - Moderuje komentáře u událostí (skrytí / opětovné zobrazení nevhodného komentáře).

4. **Administrátor (`admin`)**
   - Má všechna práva moderátora.
   - Spravuje uživatelské účty (vytvoření, úprava údajů a rolí, aktivace/deaktivace, smazání, reset hesla).

---

## 4. Hlavní doménová pravidla

- **Unikátní označení události:** Každá událost má automaticky generovaný unikátní `slug` v URL (např. `koncert-komorni-hudby`) a čitelný kód odvozený z roku a ID (např. `U-2026-0001`).
- **Kapacita události a vstupenek:**
  - `events.capacity = NULL` znamená neomezenou kapacitu, kladné číslo znamená maximální počet účastníků.
  - Každá událost má alespoň jeden typ vstupného (`ticket_types`). Cena `0` znamená vstup zdarma.
  - Kontrola volné kapacity při registraci probíhá v databázové transakci se zámkem (`lockForUpdate()`), aby nedošlo k přeplnění. Do obsazené kapacity se počítají registrace ve stavu `pending` i `confirmed`.
- **Vstupné a potvrzení úhrady:**
  - Pokud je vybraný typ vstupenky zdarma (`price == 0`), registrace se rovnou uloží jako `confirmed`.
  - Pokud je vstupenka placená (`price > 0`), registrace vznikne ve stavu `pending` a čeká na potvrzení úhrady správcem události (`confirmed`).
- **Hierarchické kategorie:**
  - Kategorie tvoří strom pomocí `parent_id` (maximální hloubka 4, kontrola proti cyklům).
  - Při filtrování událostí podle nadřazené kategorie (např. *Vzdělání*) se vyhledají i události ve všech jejích podkategoriích (např. *Seminář*, *Workshop*).
- **Hodnocení a komentáře:**
  - Přidat hodnocení (1–5) a komentář může pouze uživatel s `confirmed` registrací, a to až po skončení události (`ends_at < now()`).
- **Osobní kalendář a Google kalendář:**
  - Přihlášený uživatel vidí v osobním kalendáři přehled událostí, na které je registrován nebo které sám pořádá.
  - Kalendář lze stáhnout jako standardní soubor `.ics` a u detailu události je přímé tlačítko *„Přidat do Google kalendáře“*.

---

## 5. Databázový model (8 tabulek)

1. **`users`**
   - `id`, `username` (unique), `email` (unique), `password`, `name`, `role` (`user`, `moderator`, `admin`), `is_active`, `last_login_at`, `timestamps`
2. **`venues`** (místa konání)
   - `id`, `name`, `street`, `city`, `postal_code` (nullable), `description` (nullable), `photo_path` (nullable), `status` (`pending`, `approved`, `rejected`), `proposed_by` (FK `users`), `reviewed_by` (FK `users`, nullable), `reviewed_at` (nullable), `rejection_reason` (nullable), `timestamps`
3. **`categories`** (kategorie událostí)
   - `id`, `name`, `slug` (unique), `parent_id` (FK `categories`, nullable), `description` (nullable), `status` (`pending`, `approved`, `rejected`), `proposed_by` (FK `users`), `reviewed_by` (FK `users`, nullable), `reviewed_at` (nullable), `rejection_reason` (nullable), `timestamps`
4. **`events`** (události)
   - `id`, `slug` (unique), `title`, `description`, `starts_at`, `ends_at`, `venue_id` (FK `venues`), `category_id` (FK `categories`), `capacity` (unsigned int, nullable = neomezená), `cover_path` (nullable), `payment_note` (nullable), `status` (`draft`, `pending`, `approved`, `rejected`, `cancelled`), `manager_id` (FK `users`), `submitted_at` (nullable), `reviewed_by` (FK `users`, nullable), `reviewed_at` (nullable), `rejection_reason` (nullable), `cancelled_reason` (nullable), `timestamps`
5. **`ticket_types`** (typy vstupenek)
   - `id`, `event_id` (FK `events`), `name`, `price` (`DECIMAL(10,2)`), `capacity` (unsigned int, nullable), `timestamps`
6. **`registrations`** (registrace účastníků)
   - `id`, `event_id` (FK `events`), `user_id` (FK `users`), `ticket_type_id` (FK `ticket_types`), `unit_price` (`DECIMAL(10,2)` – snapshot ceny), `status` (`pending`, `confirmed`, `cancelled`), `payment_claimed_at` (nullable), `confirmed_by` (FK `users`, nullable), `confirmed_at` (nullable), `cancelled_at` (nullable), `timestamps`
7. **`ratings`** (hodnocení po skončení)
   - `id`, `event_id` (FK `events`), `user_id` (FK `users`), `stars` (1–5), `timestamps` (unikátní dvojice `event_id + user_id`)
8. **`comments`** (komentáře po skončení)
   - `id`, `event_id` (FK `events`), `user_id` (FK `users`), `body`, `is_hidden` (boolean), `hidden_by` (FK `users`, nullable), `hidden_at` (nullable), `timestamps`

---

## 6. Bezpečnost a požadavky na formuláře (dle `zadani.md`)

- **Ošetření vstupů a zachování dat:** Všechny vstupy validujeme na serveru (Form Request / Validator). Při chybě se formulář vrátí s chybovou hláškou a původně vyplněnými hodnotami (`old()`).
- **Povinná vs. nepovinná pole:** Povinná pole jsou vizuálně odlišena (`*`), nepovinné údaje (např. PSČ, popis místa, fotka) nejsou vyžadovány.
- **Formáty a výchozí hodnoty:** U dat a časů je naznačen formát a pole pro datum jsou smysluplně předvyplněna.
- **Výběr ze seznamu místo ID:** Uživatel nikdy nezadává ID cizího klíče ručně – místa, kategorie, typy vstupenek i role se vybírají ze seznamů (`<select>`).
- **Automatické odhlášení a aktivita účtu:** Nečinnost po 30 minutách automaticky ukončí relaci; deaktivovaný uživatel je okamžitě odhlášen.
- **Ochrana dat:** CSRF tokeny ve všech `POST`/`PUT`/`DELETE` formulářích, escapování výstupů v Blade (`{{ }}`), bezpečný upload obrázků (kontrola MIME typu a velikosti).

---

## 7. Postup implementace krok za krokem

1. **Krok 1 – Implementační plán (`implementacni-plan.md`)** *(aktuální krok)*
2. **Krok 2 – Čistý Laravel 10, Docker prostředí a základní layout**
   - Založení čistého projektu Laravel 10, nastavení `.env`, `Dockerfile` + databáze MariaDB pro lokální běh, společný Blade layout s Bootstrapem 5.3 a navigací.
3. **Krok 3 – Databázové migrace a Eloquent modely**
   - Vytvoření migrací pro všech 8 tabulek a modelů (`User`, `Venue`, `Category`, `Event`, `TicketType`, `Registration`, `Rating`, `Comment`) včetně relací.
4. **Krok 4 – Autentizace, profil a správa uživatelů (Admin)**
   - Přihlášení, registrace, odhlášení, automatické odhlášení po nečinnosti, úprava profilu a administrace uživatelů (`/admin/uzivatele`).
5. **Krok 5 – Místa konání a hierarchické kategorie**
   - Veřejný přehled míst, návrh nového místa a kategorie uživatelem, strom kategorií, schvalování a správa moderátorem.
6. **Krok 6 – Události a typy vstupenek**
   - Veřejný katalog s vyhledáváním a filtrováním (podle kategorie včetně podkategorií, místa, data), detail události, vytvoření/úprava události se vstupenkami, odeslání ke schválení a moderace událostí.
7. **Krok 7 – Registrace na události, platby a osobní kalendář**
   - Transakční přihlášení na událost s kontrolou kapacity, potvrzení úhrady správcem události, zrušení registrace, osobní kalendář + export `.ics` + Google kalendář odkaz.
8. **Krok 8 – Hodnocení, komentáře a jejich moderace**
   - Přidání hodnocení (1–5) a komentáře po skončení akce potvrzeným účastníkem, skrytí/zobrazení komentáře moderátorem.
9. **Krok 9 – Ukázková data (Seeder), testy a dokumentace `doc.html`**
   - Naplnění databáze realistickými daty pro všechny role, automatické testy a finální dokumentace `docs/doc.html` pro odevzdání.
