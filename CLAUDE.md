# Church Facility Manager — Project Instructions

## What This Is
A web-based facility management app for a church. Staff use it to manage buildings/rooms, book reservations, schedule cleaning and maintenance, track equipment, and manage room setups. It runs locally at **http://localhost**.

---

## Tech Stack
- **Backend:** PHP (no framework)
- **Database:** MySQL — database name is `facilitymanager`, connection via `config/database.php`
- **Frontend:** Tailwind CSS (loaded via CDN `https://cdn.tailwindcss.com`), vanilla JavaScript — no JS frameworks
- **Auth:** Session-based, handled in `includes/auth.php`

---

## Folder Structure
```
churchmanager/
├── config/
│   └── database.php          # getDB() returns a PDO instance
├── includes/
│   ├── auth.php              # requireLogin(), isAdmin(), getCurrentUser()
│   └── nav.php               # Opens <html>, <head>, <body>, and renders top nav
├── pages/                    # Full page views
│   ├── facilities.php
│   ├── reservations.php
│   ├── room_profile.php
│   ├── users.php
│   └── ...
├── api/                      # AJAX/JSON endpoints
│   └── reservations_api.php  # ?action=... routing pattern
├── dashboard.php
├── index.php
├── login.php
├── logout.php
└── run_migration.php         # DB migration runner (browser-clickable)
```

---

## How Pages Are Structured
Every page starts like this:
```php
<?php
$pageTitle = 'Page Name — Church Facility Manager';
require_once __DIR__ . '/../includes/nav.php';  // opens html/head/body, renders nav
require_once __DIR__ . '/../config/database.php';
$db = getDB();
?>
<!-- HTML content here -->
</body>
</html>
```
`nav.php` opens the HTML document (DOCTYPE, html, head, body tags) so pages must close `</body></html>` at the end.

---

## Auth Helpers
```php
requireLogin();          // redirects to login.php if not authenticated
isAdmin();               // returns true if current user is admin
getCurrentUser();        // returns array: id, name, email, role
```

---

## Database Pattern
```php
$db   = getDB();                          // PDO instance
$stmt = $db->prepare("SELECT ...");
$stmt->execute([$param]);
$rows = $stmt->fetchAll();                // PDO::FETCH_ASSOC (default)
$row  = $stmt->fetch();
$id   = (int)$db->lastInsertId();
```

---

## API File Pattern
API files live in `/api/` and handle AJAX requests with JSON responses:
```php
<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
$db     = getDB();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
switch ($action) {
    case 'get_something': ...  echo json_encode($data); break;
    case 'save_something': ... echo json_encode(['success' => true]); break;
    default: echo json_encode(['error' => 'Unknown action']);
}
```

---

## Migration Pattern
Any time a SQL change is needed, create a `run_migration.php` in the project root. It must:
- Require login + admin check
- Show a confirmation screen listing what will be created/changed
- Execute on POST with `confirm=yes`
- Report per-statement success/failure
- Use `CREATE TABLE IF NOT EXISTS` so it's safe to run multiple times
- Link to the relevant page on success

Always present the migration file with a direct browser link: **http://localhost/run_migration.php**

---

## UI Conventions
- **Primary color:** Blue (`blue-600` / `blue-800` for nav)
- **Card style:** `bg-white rounded-2xl shadow-sm p-6 border border-transparent hover:border-*-200`
- **Button style (primary):** `bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm transition`
- **Button style (secondary):** `border border-gray-300 hover:bg-gray-50 text-gray-600 rounded-lg px-4 py-2 text-sm transition`
- **Section labels:** `text-xs font-bold text-gray-400 uppercase tracking-wider`
- **Page max-width for standard pages:** `max-w-6xl mx-auto px-4`
- **Full-bleed layout pages** (like Reservations): `height: calc(100vh - 56px)` with flex columns

---

## Facility Data Model
```
buildings
  └── floors  (building_id, floor_order)
        └── rooms  (floor_id, name, room_number, capacity, description)
```

---

## Modules & Status
| Module       | Status      | Notes |
|-------------|-------------|-------|
| Facilities   | ✅ Built    | buildings → floors → rooms CRUD |
| Reservations | ✅ Built    | 4-panel layout: tray / nav / calendar / timeline |
| Cleaning     | 🔜 Planned  | Will reuse Reservations UI pattern |
| Maintenance  | 🔜 Planned  | Will reuse Reservations UI pattern |
| Room Setup   | 🔜 Planned  | Default + event-specific configurations |
| Equipment    | 🔜 Planned  | Inventory, borrowing, repairs |
| Users        | ✅ Built    | Admin only |

---

## Reservations Module (key tables)
- `organizations` — id, name (creatable combobox source)
- `reservations` — id, title, organization_id, start_datetime, end_datetime, notes, is_recurring, recurrence_rule, recurrence_end_date, created_by
- `reservation_rooms` — reservation_id, room_id (many-to-many)
- API: `/api/reservations_api.php`

---

## Key UX Patterns Established
- **Creatable combobox:** Any text field that might repeat (like organization name) should search existing DB entries and offer "Create X" if no match. Saves to its own table for future autocomplete.
- **4-wide button grids:** Building/floor/room navigation uses `grid grid-cols-4 gap-1` with small `nav-btn` styled buttons that wrap naturally.
- **Selected rooms tray:** A narrow persistent left column shows selected rooms as chips with a "Deselect All" button at the top.
- **Timeline:** Vertical, 6am–11pm, 60px/hour (1px/minute). Reservation blocks positioned absolutely. Overlap detection assigns columns.
- **Calendar dots:** Busy dates show a small blue dot indicator below the day number.

---

## Things to Always Do
- Present any new migration file with a clickable browser link: `http://localhost/[filename].php`
- Use `IF NOT EXISTS` on all `CREATE TABLE` statements
- Keep all JS inline in the PHP file (no separate .js files unless asked)
- Run `requireLogin()` at the top of every page and API file
- Use `htmlspecialchars()` on all output, `intval()` / `trim()` on all input

---

## Dev Login
- **Email:** k.gunn@northsummit.com
- **Password:** Welcome1!

---

## Local File Path
- Project files on host machine: `C:\Users\kgunn\Documents\churchmanager`
