## Ready Set Dinner – Projekt-Dokumentation (IM4)

## Kurzbeschreibung des Projekts
**Ready Set Dinner** ist ein webbasiertes „Dinner-Call“-Spiel: Eine Runde wird gestartet, Family Members drücken ihren Buzzer (Web oder physisch via ESP32), es werden **Reaktionszeiten gemessen** und **Punkte** vergeben. Punkte fliessen in ein **Goal-System** (Ziele sammeln/einlösen) sowie **Statistiken** (Woche/Trend).

* **Modul:** Interaktive Medien 4 an der Fachhochschule Graubünden (FS26)  
* **Themenfeld:** IoT-Applikation zum Thema Eltern mit kleinen Kindern  
* **Name des Projekts:** Ready Set Dinner   
* **Team Physical Computing:** Rebecca Baumberger und Selina Schöpfer 
* **Team WebApp:** Mathis Tobler und Melanie Bürgin
* **Welches Problem im Alltag von Eltern mit kleinen Kindern wird gelöst?**  Die Eltern können durch einen Knopfdruck die Kinder an den Tisch beordern, ohne durch die ganze Wohnung schreien zu müssen oder die Kinder im Haus zusammen zu suchen. Die Eltern profitieren, indem sie ihre Kinder schnell und einfach rufen können. Die Kinder haben davon ein gemeinsames lustiges Erlebnis und die ganze Famile vermeidet unnötigen streit durch zu spät kommen. 
* **Was ist der „Sinn und Zweck“ des Systems?** Es wird anhand eines gemeinsamen Spiels daran gearbeitet, dass die ganze Familie pünktlich am Esstisch sitzt.


### UX & Konzeption

**UX-Abgabe gemeinsame Schritte (WebApp und Physical Computing)** 
* Absprache der Funktionen und welche Informationen gesammelt werden können vom Physical Computing Team
* Ganzes Team: Erster Entwurf erstellt mit Figma Make
* Review im Team ob die Umsetzung möglich ist
* WebApp Team: Teilweise Anpassungen und vereinfachungen
* WebApp Team: Erstellung von drei möglichen Styles
* Auswahl des Styles im Team
* WebApp Team: erstellen der 3 Style Versionen
* Testen mit Usern
* WebApp Team: Anpassungen und Ausarbeitung des Designs


**Abgaben Links**
* **Figma File:** [Link zum Figma File](https://www.figma.com/design/hP7t6wIKBcyJFWRaTB8l5O/IM-4-%E2%80%93-App-Konzeption_ReadySetDinner?node-id=78-325&t=qRlRDmXuE1G3IGcx-1)
* **Figma Prototyp:** [Link zum Prototyp](https://www.figma.com/proto/hP7t6wIKBcyJFWRaTB8l5O/IM-4-%E2%80%93-App-Konzeption_ReadySetDinner?node-id=40000181-87&p=f&viewport=943%2C-666%2C0.22&t=swagsM9NcMdZJ2Q2-1&scaling=scale-down&content-scaling=fixed&starting-point-node-id=40000181%3A87&show-proto-sidebar=1&page-id=78%3A322)


**User Flow** (Screenshot aus Figma)
![Bild Userflow](resources/img-README/IM%204%20–%20App-Konzeption_ReadySetDinner_Seite_1.jpg)

**Screen Flow** (Screenshot aus Figma)
![Bild Userflow](resources/img-README/IM%204%20–%20App-Konzeption_ReadySetDinner_Seite_2.jpg)

**Welche Features waren angedacht?**

login.html/ registrieren.html:
* Konto erstellen: Registrieren
* Login und Logout

home.html:
* ⁠Run starten
* ⁠Einzelne Buzzer in der App drücken
* ⁠Punkte werden live zu goals hinzugezählt
* ⁠Run reset [*Punkte werden dann automatisch wieder weggenommen nach 5min wird der Run eingetragen und alle nicht regristrierten wird kein Dinner call in doe stats geschrieben, den anderen schon*\]

goals.html:
* ⁠Goal Punkte collecten (collect Button = Punkte werden abgezogen und neues Hauptziel kann ausgewählt werden)
* ⁠Goals anlegen
* Goals löschen
* Goal als aktives Goal setzen. Wird auf home.html angezeigt. (Übernahme der gesammelten Punkte)

family.html:
* ⁠Bis zu 4 Mitglieder efassen (mit Name, Icon und Farbe)
* ⁠Mitglieder löschen
* Miglieder bearbeiten
* ⁠Family wechseln/ logout 
* ⁠Überblick über gesamt Punktzahl seit erfassung des Mitglieds
* Nachteilsausgleich: Spielern speziefisch einen Nachteilsausgleich zuweisen, wenn beispielsweise ein Altersunterschied zwischen den Kindern besteht oder wenn die Zimmer nicht gleich weit von der Küche entfernt sind ect.

stats.html
* ⁠Überblick über alle Punkte in dieser Woche 
* Die durchschnittliche Punktzahl der letzten Woche pro Mitglied anzeigen lassen
* Archiv der letzten Monate
* Rangverkündigung nach jedem Spiel

**Welche Features wurden nicht umgesetzt? (Warum)**
* Familienmitglieder hinzufügen wurde Begrenzt auf 4 Mitglieder
Nicht umgesetzt weil: Nicht genügend Komponenten im Physical Computing vorhanden waren.

*  Nachteilsausgleich
Nicht umgesetzt weil: Mathematisch wäre es schwierig gewesen eine Formel zu erstllen, welche in jedem Fall anwendbar gewesen wäre.

* Statistik der vergangenen Spiele wurde Begrenzt auf 3 Wochen
Nicht umgesetzt weil: Wir haben uns auf grund der Übersichtlichkeit der App entschieden die Statistik auf 3 Wochen zu begrenzen.

* Archiv der letzten Monate
Nicht umgesetzt weil: Wir haben den Nutzen abgewägt und uns gegen die Idee entschieden.

* Rangverkündigung nach jedem Spiel
Nicht umgesetzt weil: Wir haben uns dagegen entschieden, weil das bedeuten würde, dass die Famile nach jedem Spiel auf ihre Screens schaut. Wir möchten, dass die Familie möglichst wenig Zeit am Screen verbringt.

### Setup

* **WebApp:** [Link zur Redy Set Dinner Website](https://im4.mathis-tobler.ch)  
* **Video-Dokumentation:** [Link zum Video auf Youtube FEHLT NOCH!!!!](http://link.zum.video) 

#### Installationsanleitung WebApp

***verständliche** Schritt-für-Schritt-Anleitung für Aussenstehende, um das Projekt zu klonen und auf einem eigenen Server zu installieren*

**1. Infrastruktur:**
* Datenbank
* Webhosting und Domain (Bsp. Informaniak oder Hostpoint)
* Visual Studio Code (oder ähnliches Programm) zum coden und programmieren
* Arduino (oder ähnliches Programm), um Daten der physichen Komponenten in die Datenbank zu speichern
* Komponenten Physical Computing: FEHLT NOCH

**2. Installationen auf Webserver:**
* FEHLT NOCH

**3. Datenbank import:** 
Die Relationale Datenbank wurde in phpMyAdmin erstellt. Die einzelnen Datensäte werden anhand der php Dateien importiert.
Wichtige API-Endpunkte (Übersicht):

Authentification:
- **`POST api/register.php`**: `{ email, password }`
- **`POST api/login.php`**: `{ email, password }`
- **`GET api/logout.php`**
- **`GET api/protected.php`**: Session-Check

Home / Runden:
- **`GET api/get_active_goal.php`**
- **`GET api/get_members.php`**
- **`POST api/start_round.php`**: startet Runde
- **`GET api/get_round_state.php`**: aktive Runde + Events (Polling)
- **`POST api/create_buzzer_event.php`**: `{ member_id }`
- **`POST api/cancel_round.php`**: bricht letzte Runde ab und zieht Punkte wieder ab
- **`POST api/load.php`**: Physical-Computing Ingest (ESP sendet `{ buzzer_events, id_users, timestamp }`)

Goals:
- **`GET api/get_goals.php`**
- **`POST api/create_goal.php`**: `{ goal, points_required }`
- **`POST api/set_active_goal.php`**: `{ goal_id }`
- **`POST api/delete_goal.php`**: `{ goal_id }`
- **`POST api/collect_goal.php`**

Family + Stats:
- **`GET api/get_family_members.php`**
- **`POST api/create_family_member.php`**
- **`POST api/update_family_member.php`**
- **`POST api/delete_family_member.php`**
- **`GET api/get_stats.php`**

Physical Computing (ESP32 / Arduino):
- Sketch: `hardware/esp32/PysicalComputing.ino`
- Sendet Events (z.B. `Start`, `Buzzer_1..4`, `End`) als **HTTP POST JSON** an `api/load.php`.
- Nutzt NTP-Zeit, damit Events serverseitig korrekt zeitlich eingeordnet werden können.

**4. DB-Credentials eintragen** 
* config.php 

**5. Coden/programmieren der einzelnen Seiten** 

| .html           | .css      | .js           | .php                  |
|-----------------|-----------|---------------|-----------------------|
| index.html      | style.css | auth.js       | load.php              
|                 |           |               | backup_load.php
|                 |           |               | protected.php
|                 |           |               | _init.php
| register.html   |           | register.js   | register.php
| login.html      |           | login.js      | login.php
|                 |           | logout.js     | logout.php
| home.html       |           | home.js       | get_active_goal.php
|                 |           |               | _game_logic.php
|                 |           |               | start_round.php
|                 |           |               | create_buzzer_event.php
|                 |           |               | get_round_state.php
|                 |           |               | cancel_round.php
|                 |           |               | get_family_members.php
| goals.html      |           | goals.js      | set_active_goal.php
|                 |           |               | get_goals.php
|                 |           |               | delete_goal.php
|                 |           |               | collect_goal.php
| family.html     |           | family.js     | create_family_member.php
|                 |           |               | update_family_member.php
|                 |           |               | delete_family_member.php
|                 |           |               | get_members.php
| stats.html      |           | stats.js      | get_stats.php
| bottom-nav.html |           | bottom-nav.js | 
| top-nav.html    |           | top-nav.js    |



**6. In Betriebnahme physische Artefakt**
* Wie? FEHLT NOCH

#### Bauanleitung Physical Computing FEHLT NOCH

* ***Was muss ich wie bauen, verbinden, installieren?***  
* *ergänze: **Komponentenplan** (betrifft Physical Computing, vgl. Slides Kapitel 15): Schaubild enthält*  
  * *die eingesetzten Komponenten*  
  * *die verbundenen Sensoren und Aktoren*  
  * *die Programme (mit Dateinamen)*  
  * *die Kommunikationswege*  
* *ergänze: **Steckplan** (betrifft Physical Computing, vgl. Slides Kapitel 15): generiert z.B. mit Fritzing (empfohlen), Tinkercad, Wokwi*  
  * *beachtet die [Fritzing Parts](https://github.com/Interaktive-Medien/im_physical_computing/tree/main/15_Intro_Projektdoku) extra für euch*  
* *ggf. **Bildmaterial***

## Technische Details

// Hier sollte das Verständnis ersichtlich sein / Wie stehen die Dateien in Beziehung zueinander, Wie reden Die Dateien miteinander, Wie ist der Weg der Daten

* **Projektstruktur / Code-Struktur:** \
[*Hinweis: Der Code selbst muss im Repository liegen und im Kopfbereich jeder Datei eine kurze Zusammenfassung enthalten.DONE Bitte noch prüfen*\]  

BILD REBECCA anpassen


* **Datenschnittstelle:** [*zwischen WebApp und Physical Computing*\] 
Die Datenschnittstelle liegt in der Datenbank bei buzzer_events oder input_events???

* **ERM:** [*Erklärung und Schaubild*\]  


* **Authentifizierung:** [*Erklärung*\]

## Known Bugs

* Was funktioniert noch nicht einwandfrei? 
soweit haben wir keine Bugs entdeckt. 

* Was ist uns aufgefallen bei der Entwicklung?  
Ein sinnvoller Aufbau der Datenbank muss anfänglich besprochen werden und dann strikt befolgt.

* Was könnte noch verbessert werden?
Die App könnte noch weiter ausgebaut werden mit den angänglich geplanten Funktionen.

## Umsetzungsprozess

**Reflexion / Erfahrung / Lernfortschritt:** 
*Was haben wir gelernt?*
* Nutzen und austesten von Figma Make
* Umgang und Austausch zwischen WebApp und Physical Computing Team
* Frontend und Backend coden/ programmieren und wie die einzelnen Files zusammenarbeiten
* Repetition html und css


*Würden wir es nochmal genauso machen? Was war gut, was war schlecht?*  
* Das Projekt hat sehr gut funktioniert und wir sind zufrieden mit der Umsetzung.

**Herausforderungen & Lösungen:** [*Verworfene Ansätze, Fehler, Umplanungen*\] 
* Das schwierigste war das Aufsetzen des Projekts, so dass wir alle zusammenarbeiten konnten und immer auf dem aktuellen Stand waren. 
* WebApp: Uns hat die Datenbankstruktur und die Logik in den Javascript Files ab und zu vor herausvorderungen gestellt.
* Unser Wissen betreffend Javascript und php ist noch nicht sehr ausgereift, daher war es anfangs schwierig zu verstehen was der Code macht respektive was die Fehlermeldungen bedeuten und wie wir diese lösen.
* WebApp: Es war schwer eine funktionierende Datenbankstruktur aufzusetzen, die auch für das Physical Computing Team funktioniert. Hauptsächlich weil unserem Teil des Teams das ganze Wissen zu Physical Computing fehlte, da wir in diesem Bereich keine Einführung hatten.


**KI-Einsatz:** *Dokumentation der verwendeten KI-Tools und deren Nutzen (KI ist nicht verboten)*  
* Wir haben mit ChatGPT gearbeitet, um uns beim Coden und bei Fehlermeldungen zu helfen.
* Zum korrigieren des Codes, für Erklärungen oder bei Fehlermeldungen haben wir Copilot verwendet.


**Fazit:**  
* Wir sind sehr zufrieden, wie das Team zusammengearbeitet und sich gegenseitg ausgeholfen hat. Das Endresultat funktioniert so, wie wir es uns vorgestellt haben und kann sich sehen lassen.










## ZUSAMMENFASSUNG REBECCA - noch löschen?

### Features
- **Login/Session-Auth**: Registrierung, Login, Logout, geschützte API via PHP Sessions
- **Family Management**: bis zu 4 Members, Icon + Buzzer-Farbe, Delete
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

