# 🗓️ Calendar, 📄 Circulars & 📇 Employee Directory

This project is a PHP web app that bundles three modules in one UI:
- A responsive calendar for special dates (holidays, poya days, etc.)
- A company circulars board with categories and pagination
- An employee telephone directory with department filters, search, and quick actions

The Circulars + Directory live together in `circular.php` with tab navigation.

---

## 🚀 Features

- ✅ Calendar: 4‑month dynamic view with database‑driven special dates
- ✅ Calendar: Color coding for holidays/poya + weekend highlighting
- ✅ Calendar: Tooltip descriptions and optional PDF links per date
- ✅ Admin: Manage special dates + super admin for user management
- ✅ Security: Hashed passwords and SQL‑injection‑safe queries
- ✅ Circulars: Category support and pagination (see `admin/posts/`)
- ✅ Directory: Department filter, keyword search, and grouped results
- ✅ Directory: Shows position, extension, multiple phone numbers, and email
- ✅ Directory: Quick actions — Call and Email buttons stay visible on all rows
- ✅ Directory: Phone numbers have small copy buttons next to each number

---

## 🛠️ Tech Stack

✅ Frontend: HTML5, CSS3, JavaScript
✅ Backend: PHP 8+
✅ Database: MySQL (via phpMyAdmin)
✅ Server: WAMP / XAMPP / Laragon (localhost testing)

> Tip (Laragon): Place this folder in `C:\laragon\www\` and browse to
> `http://localhost/calendar-app/circular.php`.

---

## 🧭 Modules Overview

- **Calendar** — traditional 4‑month grid, data from MySQL.
- **Circulars** — end‑user list with pagination; managed via `admin/posts/`.
- **Employee Directory** — searchable list grouped by Department with actions:
   - Call button (`tel:`) and Email button (`mailto:`)
   - Copy buttons for individual phone numbers
   - We removed the old “Copy Details” button from the Actions column so the Call/Email buttons remain visible.

Open the combined view at: `circular.php` → tabs “Circulars” and “Employee Directory”.

---

## #️⃣ To Hash Password

Use the included `update_passwords.php` script:

1. Edit the script with YOUR secure passwords
2. Run: `php update_passwords.php`
3. Delete or secure the script after use

**⚠️ SECURITY WARNING:** Never commit real passwords to your repository!

---

## 🔧 Setup Instructions

1. ✅ Clone the repo:

   ```bash
   git clone https://github.com/AnuNirmani/calendar-app
   cd calendar-app
   git checkout main
   ```

2. ✅ Start WAMP/XAMPP/Laragon and place files in your web root
   - XAMPP: `htdocs/calendar-app`
   - Laragon: `C:\laragon\www\calendar-app`

3. ✅ Create a MySQL database (e.g., `calendar_app`) and run the SQL from the schema section above.

4. ✅ **Configure Database Connection:**
   - Copy `db.example.php` to `db.php`
   - Update `db.php` with your actual database credentials
   - **NEVER commit db.php to the repository**

5. ✅ **Set Secure Passwords:**
   - Edit `update_passwords.php` with your desired passwords
   - Run it once: `php update_passwords.php`
   - Delete or secure `update_passwords.php` after running

6. ✅ Access via browser:

   ```
http://localhost/calendar-app/circular.php       # Circulars + Directory
http://localhost/calendar-app/index.php          # Calendar
http://localhost/calendar-app/admin/             # Admin (login)
   ```

7. ✅ (Optional) Initialize Circulars/Directory tables
   - Visit `admin/posts/create_posts_table.php` once (if present) to create base tables.
   - Manage directory and circular entries via pages in `admin/posts/`.

---

## 🙌 Credits

📍Developed and Maintained by **Web Publishing Department** in collaboration with WNL Time Office. © All rights reserved, Wijeya Newspapers Ltd. — 2025

---
