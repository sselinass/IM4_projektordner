## Ready Set Dinner – Projekt-Dokumentation (IM4)

**Ready Set Dinner** ist ein webbasiertes „Dinner-Call“-Spiel: Eine Runde wird gestartet, Family Members drücken ihren Buzzer (Web oder physisch via ESP32), es werden **Reaktionszeiten gemessen** und **Punkte** vergeben. Punkte fliessen in ein **Goal-System** (Ziele sammeln/einlösen) sowie **Statistiken** (Woche/Trend).

### Features
- **Login/Session-Auth**: Registrierung, Login, Logout, geschützte API via PHP Sessions
- **Family Management**: bis zu 4 Members, Icon + Buzzer-Farbe, (Soft-)Delete
- **Dinner Call (Runden)**: Start, Buzzer-Events, Timeout/Cancel, Live-Sync (Polling)
- **Goals**: aktive/future Goals, aktiv setzen, einlösen (Punkte ausgeben)
- **Stats**: Wochenpunkte + Trend über mehrere Wochen
- **Physical Computing**: ESP32 sendet Events als JSON an die API (`api/load.php`)

Eine Erklärung zur Authentifizierung (Sessions/Cookies) findest du in [`sessions.md`](sessions.md).

---

### Architektur (kurz)
- **Frontend**: statische Seiten (`*.html`), Styles (`css/style.css`), Logik (`js/*.js`)
- **UI-Partials**: `components/top-nav.html`, `components/bottom-nav.html` werden per `fetch` geladen
- **Backend**: `api/*.php` als JSON-Endpunkte
- **Shared Backend**
  - `api/_init.php`: Session start, JSON helpers, `require_user_id()`, lädt DB-Config
  - `api/_game_logic.php`: zentrale Round-/Buzzer-/Punkte-Logik
- **Datenbank**: MySQL/MariaDB
- **Hardware**: Arduino/ESP32 Sketch in `hardware/esp32/`

---

### Projektstruktur
- **`/` (Root)**: HTML-Seiten (`index.html`, `home.html`, `goals.html`, `family.html`, `stats.html`, `login.html`, `register.html`)
- **`components/`**: HTML-Partials für Navigation
- **`css/`**: Styles
- **`js/`**: Frontend-Logik (API Calls, Rendering)
- **`api/`**: PHP-API-Endpunkte
- **`system/`**: DB-Konfiguration (`config.php`) + SQL (`db.sql`)
- **`hardware/esp32/`**: Physical-Computing Code (`*.ino`)
- **`resources/`**: Assets (Icons, Bilder, SVGs)

---

### Lokales Setup (Development)

#### 1) Datenbank (MySQL/MariaDB)
- Importiere mindestens `system/db.sql` (enthält die Tabelle `users`).
- Für das volle Projekt brauchst du zusätzlich Tabellen wie `members`, `goal`, `rounds`, `buzzer_events`, `input_events` (werden von den API-Endpunkten verwendet).
- Zusatz-/Änderungs-SQL liegt aktuell z.B. in `00_ChatGPT Dateien/db_update_goals.sql`.

#### 2) DB-Verbindung konfigurieren
In `api/_init.php` wird `../system/config.php` eingebunden. Dort muss ein `$pdo` existieren.

`system/config.php` ist aktuell leer und sollte z.B. so aufgebaut sein (Beispiel):

```php
<?php
declare(strict_types=1);

$pdo = new PDO(
  'mysql:host=localhost;dbname=READY_SET_DINNER;charset=utf8mb4',
  'DB_USER',
  'DB_PASS',
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);
```

#### 3) Lokalen PHP Server starten
Im Repo-Root:

```bash
php -S localhost:8000
```

Dann im Browser öffnen:
- `http://localhost:8000/index.html`

---

### Nutzung (User Flow)
- **Register**: `register.html` → `api/register.php`
- **Login**: `login.html` → `api/login.php` (Session wird gesetzt)
- **App**: `home.html`, `goals.html`, `family.html`, `stats.html`
  - Jede Seite ruft `js/auth.js` → `api/protected.php` auf. Bei `401` erfolgt Redirect auf `index.html`.

---

### Wichtige API-Endpunkte (Übersicht)

#### Auth
- **`POST api/register.php`**: `{ email, password }`
- **`POST api/login.php`**: `{ email, password }`
- **`GET api/logout.php`**
- **`GET api/protected.php`**: Session-Check

#### Home / Runden
- **`GET api/get_active_goal.php`**
- **`GET api/get_members.php`**
- **`POST api/start_round.php`**: startet Runde
- **`GET api/get_round_state.php`**: aktive Runde + Events (Polling)
- **`POST api/create_buzzer_event.php`**: `{ member_id }`
- **`POST api/cancel_round.php`**: bricht letzte Runde ab und zieht Punkte wieder ab
- **`POST api/load.php`**: Physical-Computing Ingest (ESP sendet `{ buzzer_events, id_users, timestamp }`)

#### Goals
- **`GET api/get_goals.php`**
- **`POST api/create_goal.php`**: `{ goal, points_required }`
- **`POST api/set_active_goal.php`**: `{ goal_id }`
- **`POST api/delete_goal.php`**: `{ goal_id }`
- **`POST api/collect_goal.php`**

#### Family + Stats
- **`GET api/get_family_members.php`**
- **`POST api/create_family_member.php`**
- **`POST api/update_family_member.php`**
- **`POST api/delete_family_member.php`**
- **`GET api/get_stats.php`**

---

### Physical Computing (ESP32 / Arduino)
- Sketch: `hardware/esp32/PysicalComputing.ino`
- Sendet Events (z.B. `Start`, `Buzzer_1..4`, `End`) als **HTTP POST JSON** an `api/load.php`.
- Nutzt NTP-Zeit, damit Events serverseitig korrekt zeitlich eingeordnet werden können.

---

### Hinweise / Known Issues
- **DB-Schema**: `system/db.sql` enthält nur die `users`-Tabelle; das Projekt benötigt darüber hinaus weitere Tabellen, die von den API-Endpunkten verwendet werden.
- **`system/config.php`**: muss `$pdo` bereitstellen, sonst schlagen alle `api/*` Requests fehl.

