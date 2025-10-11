# 🗓️ PHP Calendar with Admin Panel

This is a responsive PHP-based calendar web application that displays special dates (e.g. holidays, poya days, etc.) with color-coded cells and optional descriptions. An admin panel allows managing these special dates via a secure login system.

---

## 🚀 Features

- ✅ **4-month dynamic calendar view**
- ✅ **Special dates from MySQL database**
- ✅ **Color-coded cells for holidays and poya days**
- ✅ **Colored dates for weekends**
- ✅ **Tooltip hover for date descriptions**
- ✅ **Clickable date cells (to open attendance PDFs)**
- ✅ **Admin panel for managing special dates**
- ✅ **Super Admin panel for managing Admins**
- ✅ **Pagination based on year (admin side)**
- ✅ **Search a date by dropdowns**
- ✅ **Hashed passwords**
- ✅ **SQL Injection Protection**

---

## 🛠️ Tech Stack

✅ Frontend: HTML5, CSS3, JavaScript
✅ Backend: PHP 8+
✅ Database: MySQL (via phpMyAdmin)
✅ Server: WAMP / XAMPP (localhost testing)

---

## 🗃️ Database Schema

### 1. `users with default users`  
Stores user login credentials and roles.
```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (username, password, role) VALUES ('superadmin', 'YOUR_HASHED_PASSWORD', 'super_admin')
ON DUPLICATE KEY UPDATE role = 'super_admin';

INSERT INTO users (username, password, role, created_by) VALUES ('admin1', 'YOUR_HASHED_PASSWORD', 'admin', 1)
ON DUPLICATE KEY UPDATE role = 'admin';

> 🔐 *Use the update_passwords.php script to set secure passwords. Never commit actual passwords to the repository.*

````

### 2. `special_types`

Stores types of special dates (e.g. Holiday, Poya).

```sql
CREATE TABLE special_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(100) NOT NULL,
  description TEXT
);
```

### 3. `special_dates`

Stores the actual dates.

```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```


---

## 0️⃣ Default Special Dates

```sql
INSERT INTO special_types (type, description) VALUES
('holiday', 'Public Holiday'),
('poya', 'Full Moon Poya Day');
```

---

## #️⃣ To Hash Password

Use the included `update_passwords.php` script:

1. Edit the script with YOUR secure passwords
2. Run: `php update_passwords.php`
3. Delete or secure the script after use

**⚠️ SECURITY WARNING:** Never commit real passwords to your repository!

---

## 📂 Folder Structure

```
calendar-app/
│
├── admin/
│   ├── add.php
│   ├── edit.php
│   ├── edit_user.php
│   ├── index.php
│   ├── manage_users.php
│   └── save.php
│
├── css/
│   ├── fonts/
│   │   └── static/
│   │       ├── Inter-Bold.woff
│   │       ├── Inter-Bold.woff2
│   │       ├── Inter-Light.woff
│   │       ├── Inter-Light.woff2
│   │       ├── Inter-Medium.woff
│   │       ├── Inter-Medium.woff2
│   │       ├── Inter-Regular.woff
│   │       ├── Inter-Regular.woff2
│   │       ├── Inter-SemiBold.woff
│   │       └── Inter-SemiBold.woff2
│   ├── fonts.css
│   └── style.css
│
├── images/
│   └── logo.jpg
│
├── .hintrc
├── auth.php
├── circular.html
├── db.php
├── index.html
├── index.php
├── login.php
├── logout.php
├── README.md
├── update_passwords.php
```
- **admin/**: Admin panel PHP files  
- **css/**: Stylesheets and font files  
- **images/**: App images  
- Root: Main PHP/HTML files

---

## 🔧 Setup Instructions

1. ✅ Clone the repo:

   ```bash
   git clone https://github.com/AnuNirmani/calendar-app
   cd calendar-app
   git checkout main2.0
   ```

2. ✅ Start XAMPP or MAMP and place files in your `htdocs` folder.

3. ✅ Create a MySQL database called `calendar_app` and run the SQL scripts from the schema section above.

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
   http://localhost/calendar-app/index.php
   http://localhost/calendar-app/login.php
   ```

---

## 💡 Future Improvements

* Export calendar as PDF

---

## 🙌 Credits

📍Developed and Maintained by **Web Publishing Department** in collaboration with WNL Time Office. © All rights reserved, Wijeya Newspapers Ltd. — 2025

---
