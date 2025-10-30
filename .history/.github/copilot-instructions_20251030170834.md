## Quick context — what this project is

- Small PHP/MySQL calendar app with an admin panel. Frontend is plain HTML/CSS/JS. Backend is procedural PHP (no framework). Runs on XAMPP/MAMP/WAMP or PHP built-in server.
- Key runtime entrypoints: `index.php`, `login.php`, and the `admin/` directory (admin UI).

## Architecture (big picture)

- Data: MySQL database (default schema in `README.md`). Main tables: `users`, `special_types`, `special_dates` (plus CMS-like `categories`, `posts`, `telephone_directory`).
- App: procedural PHP files that include `db.php` for DB connection and `auth.php` for session/role helpers.
- Admin UI: files under `admin/` (e.g. `admin/index.php`, `admin/list_categories.php`, `admin/dashboard.php`). Admin pages commonly `include '../db.php'` and `include '../auth.php'` and assume the working directory is `admin/`.

## Important files to read first

- `db.example.php` — copy to `db.php` and update credentials. Many files `include 'db.php'` or `include '../db.php'` so pay attention to relative paths.
- `auth.php` — contains `checkAuth($requiredRole = null)`, `isSuperAdmin()`, `isAdmin()`, `getCurrentUserId()` and session initialization. Use these helpers for access control.
- `update_passwords.php` — helper for hashing passwords; it is deliberate and should be run locally then removed or secured.
- `README.md` — contains schema snippets and setup notes.

## Project-specific conventions and patterns

- Relative includes: Admin files live in `admin/` and use `../` to include root files. When editing or creating new admin pages, mirror the existing include patterns (e.g. `include '../db.php'; include '../auth.php';`).
- Role checks: use `checkAuth()` at the top of admin pages. For super-admin-only flows use `checkAuth('super_admin')` or `isSuperAdmin()` when rendering UI.
- Session timeout: admin pages implement an inactivity timeout of 900 seconds (15 minutes). If you change it, update all admin pages that reimplement this logic or centralize it in `auth.php`.
- DB usage: both prepared statements and direct `$conn->query()` are used. Follow the local pattern when modifying an endpoint: use prepared statements for user-supplied inputs (examples: `login.php` uses `prepare()` for user lookup; `admin/list_categories.php` dynamically builds filters and uses `bind_param()` when parameters exist).
- UI styling: mixture of hand-rolled CSS (`css/style.css`) and Tailwind via CDN in some admin pages (e.g. `admin/list_categories.php`). Keep edits consistent with the file you edit.

## Security & operational notes (must-know)

- Do NOT commit real credentials: `db.php` must be created from `db.example.php` locally and never committed.
- Passwords are hashed; the repo contains `update_passwords.php` to (re)generate hashed password values — use it locally and then delete or protect it.
- `auth.php` redirects unauthorized users to `login.php` or `index.php?error=access_denied`. Changing redirect paths will affect many admin pages.

## Local dev & debug commands (Windows PowerShell)

1. Start XAMPP and put this project under `htdocs` (current setup uses XAMPP). Or run PHP built-in server for quick tests:

    php -S localhost:8000 -t .

    then open http://localhost:8000/index.php

2. DB setup:

    create DB and run SQL (use phpMyAdmin or mysql client). README.md contains table SQL snippets for `users`, `special_types`, `special_dates`.

3. Passwords:

    edit update_passwords.php with desired plain-text values, then
    php update_passwords.php
    afterwards remove or secure update_passwords.php

## Typical changes and where to look

- Add a new admin page: create file under `admin/`, `include '../db.php'; include '../auth.php';` and call `checkAuth()` at top.
- Add new DB fields: update SQL in README, run migration manually in phpMyAdmin and update affected queries (watch for `SELECT *` in admin pages).
- UI tweaks: `css/style.css` and `admin/*.php` (some use Tailwind CDN). Keep markup structure consistent with surrounding files.

## Examples to copy/paste

- Authenticate and redirect to dashboard (pattern from `login.php`):

    include 'db.php';
    // after verifying credentials
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    header('Location: admin/dashboard.php');
    exit;

- Protect an admin page (pattern used across `admin/*`):

    include '../db.php';
    include '../auth.php';
    checkAuth(); // or checkAuth('super_admin')
    // then page logic

## Cross-file patterns and pitfalls to be aware of

- Many admin pages rely on relative includes — creating a file in a different folder without adjusting `../` will break DB/auth includes.
- Some queries are constructed dynamically (filter builders in `admin/index.php` and `admin/list_categories.php`). Keep careful track of bound parameter types when modifying these.
- There are mixed DB styles (mysqli OOP vs procedural) — prefer using the existing style in the file you edit.

## When to ask the maintainer

- If a change needs a DB migration (schema change), ask for a migration plan (dump + apply) and whether production credentials/backups exist.
- If you plan to centralize session timeout or authentication behavior, discuss first — many files duplicate the same logic.

---

If you'd like, I can:
- merge these notes into an existing instruction file if you have one elsewhere, or
- expand any section with specific code snippets (e.g., sample prepared statements for a specific query). Which would you like next?
