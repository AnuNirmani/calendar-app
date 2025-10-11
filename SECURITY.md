# 🔒 Security Guidelines

## Critical Security Practices

### 1. Never Commit Sensitive Information
**NEVER commit these files to the repository:**
- `db.php` - Contains database credentials
- `.env` files - Environment variables
- `config.php` - Configuration with secrets
- Files with API keys, passwords, or tokens

### 2. Use Configuration Templates
- ✅ Commit: `db.example.php` (template without real credentials)
- ❌ Don't commit: `db.php` (actual credentials)
- Users copy the example and add their own credentials locally

### 3. Use .gitignore
The `.gitignore` file prevents sensitive files from being committed:
```
db.php
config.php
.env
```

### 4. Password Security
- Always hash passwords using `password_hash()` in PHP
- Never store plain text passwords in the database
- Use strong, unique passwords for each environment
- Default credentials like "admin123" should be changed immediately

### 5. If You've Already Committed Sensitive Data
If you've already pushed sensitive files to GitHub:
1. Change all exposed credentials immediately
2. Remove the sensitive files from git history (see commands below)
3. Force push the cleaned repository

## 🚨 If Credentials Were Exposed
1. **Change database passwords immediately**
2. **Rotate any API keys or tokens**
3. **Review GitHub repository access logs**
4. **Clean git history** (see instructions below)

---

## For Repository Maintainers

### Making Repository Private
If you want to restrict who can view/clone your code:
1. Go to GitHub repository settings
2. Scroll to "Danger Zone"
3. Click "Change visibility" → "Make private"

### Adding a License
A license file (like the MIT License included) defines how others can use your code legally.

---

**© 2025 Wijeya Newspapers Ltd. All rights reserved.**

