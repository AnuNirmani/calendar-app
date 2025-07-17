# 🗓️ PHP Calendar with Admin Panel

This is a responsive PHP-based calendar web application that displays special dates (e.g. holidays, poya days, etc.) with color-coded cells and optional descriptions. An admin panel allows managing these special dates via a secure login system.

---

## 🚀 Features

- ✅ **4-month dynamic calendar view**
- ✅ **Special dates from MySQL database**
- ✅ **Color-coded cells for weekends, holidays, and poya days**
- ✅ **Tooltip hover for date descriptions**
- ✅ **Clickable date cells (e.g. open PDFs)**
- ✅ **Admin panel for managing special dates**
- ✅ **Login system with admin/user roles**
- ✅ **Responsive layout (mobile/tablet friendly)**
- ✅ **Color picker and dropdown for type selection**
- ✅ **Pagination based on year (admin side)**

---

## 🗃️ Database Schema

### 1. `users`  
Stores user login credentials and roles.
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(50) NOT NULL,
  role ENUM('admin', 'user') DEFAULT 'user'
);
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

INSERT INTO users (username, password, role) VALUES ('superadmin', 'super123', 'super_admin')
ON DUPLICATE KEY UPDATE role = 'super_admin';

INSERT INTO users (username, password, role, created_by) VALUES ('admin1', 'admin123', 'admin', 1)
ON DUPLICATE KEY UPDATE role = 'admin';
```

---

## 👤 Default Users

```sql
-- Admin User
INSERT INTO users (username, password, role) VALUES ('admin', 'admin123', 'admin');

-- Normal User
INSERT INTO users (username, password, role) VALUES ('user1', 'user123', 'user');
```

> 🔐 *Passwords are stored in plain text (for demonstration only). Can use hashing in production.*

---

## 📂 Folder Structure

```
calendar-app/
│
├── admin/
│   ├── add.php
│   ├── index.php
│   └── save.php
│
├── images/
│   └── logo.jpg
│
├── css/
│   └── style.css
│
├── db.php
├── index.php
├── index.html
├── login.php
├── logout.php
├── home.php
├── pdf.html
└── README.md
```


---

## 🔧 Setup Instructions

1. ✅ Clone the repo:

   ```bash
   git clone https://github.com/AnuNirmani/php-calendar-app.git
   ```

2. ✅ Start XAMPP or MAMP and place files in your `htdocs` folder.

3. ✅ Create a MySQL database called `calendar_db` and run the SQL scripts from the schema section above.

4. ✅ Update `db.php` with your database credentials.

5. ✅ Access via browser:

   ```
   http://localhost/php-calendar-app/login.php
   ```

---

## 💡 Future Improvements

* Add password hashing
* Export calendar as PDF
* Multilingual support
* Event reminders/notifications

---

## 🙌 Credits

📍Developed and Maintained by **Web Publishing Department** in collaboration with WNL Time Office. © All rights reserved, Wijeya Newspapers Ltd. — 2025

---
