# Official Letter Workflow (Demo)

A small, self-contained demo of an **internal official-correspondence system**: staff
draft letters, a verifier checks them, an administrator approves and numbers them, and
every step is recorded in an audit trail. Approved letters can be exported to PDF.

> ⚠️ **All data in this project is fictitious.** Names such as *"Warga Contoh 001"*,
> IDs such as *9999999999999001*, and the institution *"Dinas Contoh"* are invented for
> demonstration only. No real person, identifier, or organization is represented.

## Features

- **Role-based access control (RBAC)** with three roles: `admin`, `verifikator`, `staff`.
- **Letter creation** with required-field validation.
- **Automatic numbering** on approval, using an atomic per-year counter (`001/OLW/2026`).
- **Multi-level workflow**: `draft → submitted → verified → approved`, plus `rejected`.
- **Audit trail**: every action (who, from-status, to-status, note, timestamp) is logged.
- **PDF export** of a letter via [Dompdf](https://github.com/dompdf/dompdf).

## Security basics

- Passwords stored with `password_hash()` (bcrypt) and checked with `password_verify()`.
- **Prepared statements** (PDO) everywhere — no string-concatenated SQL.
- **CSRF protection** on every state-changing POST (per-session token, `hash_equals`).
- **Secure sessions**: `HttpOnly`, `SameSite=Lax`, `Secure` when served over HTTPS,
  and `session_regenerate_id()` on login to prevent fixation.
- Output escaped with `htmlspecialchars()` to prevent XSS.
- Workflow transitions are validated server-side and guarded by permissions;
  row-level locking (`SELECT ... FOR UPDATE`) prevents double-processing.

## Tech stack

PHP 8.1+ · MySQL/MariaDB (PDO) · Composer · Dompdf · vlucas/phpdotenv · plain CSS.
No framework — the goal is to show the fundamentals clearly.

## Getting started

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure the environment
cp .env.example .env
#   edit .env and set DB_USER / DB_PASS for your local MySQL/MariaDB

# 3. Create the database and load schema + fictitious seed data
mysql -u root -p -e "CREATE DATABASE olw_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p olw_demo < schema.sql
mysql -u root -p olw_demo < seed.sql

# 4. Run the app (PHP built-in server)
php -S localhost:8000 -t public public/index.php
```

Then open <http://localhost:8000>.

### Demo accounts

| Username      | Role        | Password       |
|---------------|-------------|----------------|
| `admin`       | admin       | `Password123!` |
| `verifikator` | verifikator | `Password123!` |
| `staff`       | staff       | `Password123!` |

### Try the workflow

1. Sign in as **staff** → create a letter → **Submit**.
2. Sign in as **verifikator** → open the letter → **Verify** (or **Reject**).
3. Sign in as **admin** → **Approve** (the letter receives an official number).
4. **Export PDF** from the letter page.

## Project structure

```
official-letter-workflow-demo/
├── public/
│   ├── index.php          # front controller + router + secure session
│   └── assets/style.css
├── src/
│   ├── Auth.php           # login / session / permission guards
│   ├── Csrf.php           # CSRF token helper
│   ├── Database.php       # PDO connection
│   ├── Letters.php        # workflow model, numbering, audit log
│   ├── Pdf.php            # Dompdf letter renderer
│   ├── Rbac.php           # permission → roles map
│   ├── helpers.php        # view(), e(), flash(), ...
│   └── Controllers/
│       ├── AuthController.php
│       └── LetterController.php
├── views/                 # PHP templates
├── schema.sql             # database schema
├── seed.sql               # fictitious demo data
├── .env.example
├── composer.json
└── LICENSE                # MIT
```

## License

[MIT](LICENSE) © 2026 Noken13
