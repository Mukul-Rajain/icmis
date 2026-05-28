# DCFM Court System — Quick Start Guide

> **Goal:** Get this project running on your machine in 10 minutes.

---

## ⚡ Fastest Path (SQLite, no MySQL needed)

### Prerequisites

You need these installed first. If you don't have them, install before continuing:

| Tool | Check command | Download |
|------|---------------|----------|
| **PHP 8.1+** | `php --version` | https://www.php.net/downloads |
| **Composer** | `composer --version` | https://getcomposer.org/download |
| **Node.js 18+** | `node --version` | https://nodejs.org |

**Windows users:** install [XAMPP](https://www.apachefriends.org/) (gives you PHP) + Composer + Node.js.
**Mac users:** `brew install php composer node`
**Linux users:** `sudo apt install php php-cli php-mbstring php-xml php-sqlite3 composer nodejs npm`

---

### Step-by-step (copy-paste these commands)

**1. Open your terminal and navigate to where you want the project:**

```bash
cd Desktop
```

**2. Create a fresh Laravel 10 project:**

```bash
composer create-project laravel/laravel:^10.0 dcfm-app
cd dcfm-app
```

**3. Extract `dcfm-court-laravel.zip` into THIS folder.**

When prompted "overwrite?" → say **YES to all**. You should now see folders like `app/`, `database/`, `resources/`, `routes/` updated with DCFM code.

**4. Install the extra packages we need:**

```bash
composer require spatie/laravel-permission:^5.11 livewire/livewire:^3.0 barryvdh/laravel-dompdf:^2.0
```

**5. Set up the database file (using SQLite — no MySQL needed):**

Mac/Linux:
```bash
touch database/database.sqlite
```

Windows (PowerShell):
```powershell
New-Item database/database.sqlite -ItemType File
```

**6. Edit your `.env` file** — find these lines and make them look like this:

```env
DB_CONNECTION=sqlite
# Comment out or delete DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
```

**7. Generate app key and run migrations:**

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
```

You should see output like:
```
✓ Migrating: 2024_01_01_000001_extend_users_table
✓ Migrating: 2024_01_01_000002_create_courts_table
...
Created demo users. Login credentials:
+--------+-------------------+----------+
| Role   | Email             | Password |
+--------+-------------------+----------+
| Admin  | admin@dcfm.test   | password |
...
Created 32 demo cases.
```

**8. Start the server:**

```bash
php artisan serve
```

**9. Open your browser:**

→ **http://localhost:8000**

You'll see the login page. Use `admin@dcfm.test` / `password`.

---

## 🎬 Demo Flow for Your Presentation

Once logged in as admin:

1. **Dashboard** — Shows 2,847 active cases, track distribution chart, 73 at-risk cases flagged
2. **Cases** menu → see all cases sorted by priority score
3. **Click any "fast" track case** → see priority score breakdown on the right (Base + Age + Urgency + Adjournment + Stage + Stakeholder)
4. **Register New Case** button → see live track classification as you fill the form (Livewire)
5. **Cause Lists** → Generate → pick tomorrow's date → see auto-ordered list
6. **Analytics** → see how Fast track averages 38 days vs Complex 322 days

---

## 🔑 All Demo Logins

| Role | Email | Password | What they see |
|------|-------|----------|---------------|
| Admin | `admin@dcfm.test` | `password` | Full operational dashboard, all cases, analytics |
| Judge | `judge1@dcfm.test` | `password` | Their assigned cases, today's cause list |
| Lawyer | `lawyer@dcfm.test` | `password` | Only their cases, upcoming hearings |
| Staff | `staff@dcfm.test` | `password` | Case registration, cause list generation |
| Litigant | `litigant@dcfm.test` | `password` | Their case status only |

---

## 🐛 Troubleshooting

### "php: command not found"
PHP isn't installed or isn't in your PATH. Install it (see Prerequisites above).

### "SQLSTATE: unable to open database file"
Make sure the SQLite file exists and the `.env` `DB_DATABASE` path is correct. Try absolute path:
```env
DB_DATABASE=/Users/yourname/Desktop/dcfm-app/database/database.sqlite
```

### "Class 'Spatie\\Permission\\...' not found"
You skipped step 4. Run:
```bash
composer require spatie/laravel-permission:^5.11
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### Views look broken / no styling
The Tailwind CDN should load from the layout. If your computer has no internet, you'll need to build assets locally:
```bash
npm install
npm run build
```

### "Target class [DashboardController] does not exist"
```bash
composer dump-autoload
php artisan cache:clear
```

### Migrations failed
Drop the database and retry from scratch:
```bash
rm database/database.sqlite
touch database/database.sqlite
php artisan migrate:fresh --seed
```

### Want to use MySQL instead?
In `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dcfm_court
DB_USERNAME=root
DB_PASSWORD=
```
Then create the database in MySQL (`CREATE DATABASE dcfm_court;`) and run `php artisan migrate:fresh --seed`.

---

## 📁 What's In This Zip

```
dcfm-court-laravel/
├── app/
│   ├── Console/          ← Artisan command for re-scoring all cases nightly
│   ├── Http/Controllers/ ← 7 controllers (Dashboard, Case, CauseList, etc.)
│   ├── Http/Livewire/    ← Live track-preview component for registration
│   ├── Jobs/             ← Nightly cause list generation job
│   ├── Models/           ← 12 Eloquent models
│   ├── Notifications/    ← Email notifications for hearings/adjournments
│   └── Services/         ← 6 service classes (the brain of DCFM)
│       ├── TrackClassifier.php       ← Rule-based fast/standard/complex
│       ├── PriorityScorer.php        ← Weighted score formula ⭐
│       ├── CauseListGenerator.php    ← Smart hearing ordering
│       ├── DelayPredictor.php        ← Flags at-risk cases
│       ├── CaseRegistrationService.php
│       └── CaseNumberGenerator.php
├── database/
│   ├── factories/        ← Model factories for tests/seeders
│   ├── migrations/       ← 11 tables
│   └── seeders/          ← 32 realistic Indian court cases
├── resources/views/      ← 20+ Blade templates with judicial-themed UI
├── routes/               ← web.php + auth.php
└── tests/Unit/           ← Unit tests for the scoring engine
```

**The headline algorithm is in `app/Services/PriorityScorer.php`** — this is what to show in your viva.

---

## ❓ Need Help?

If you hit any error, copy the full error message and ask. Most issues are:
1. Missing PHP extensions (`php-sqlite3`, `php-mbstring`, `php-xml`)
2. PATH issues (Composer/PHP not in PATH)
3. Permission issues on the `storage/` and `bootstrap/cache/` directories — fix with `chmod -R 775 storage bootstrap/cache`
