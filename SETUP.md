# DCFM Court System — Setup Guide

## Prerequisites
- PHP 8.1 or higher
- Composer
- Node.js 18+ and npm

---

## Step 1: Create a fresh Laravel project

```bash
composer create-project laravel/laravel dcfm-app "10.*"
cd dcfm-app
```

## Step 2: Copy the DCFM files into the project

Extract the zip and copy ALL folders from it into your `dcfm-app` directory.
When asked to overwrite, say YES to all.

```
dcfm-app/
  app/          ← overwrite
  database/     ← overwrite
  resources/    ← overwrite
  routes/       ← overwrite
  tests/        ← overwrite
```

## Step 3: Install PHP dependencies

```bash
composer require spatie/laravel-permission:^5.11 livewire/livewire:^3.0 barryvdh/laravel-dompdf:^2.0
```

## Step 4: Configure the database

Copy the example env:
```bash
cp .env.example .env
php artisan key:generate
```

For SQLite (easiest, no database server needed):
```bash
touch database/database.sqlite
```

In `.env`, make sure:
```
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/dcfm-app/database/database.sqlite
```

For MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dcfm_court
DB_USERNAME=root
DB_PASSWORD=your_password
```

## Step 5: Publish Spatie Permission config

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

## Step 6: Run migrations and seed

```bash
php artisan migrate
php artisan db:seed
```

This creates:
- All 11 database tables
- 5 demo users (admin, judge, lawyer, staff, litigant)
- 22 case types (bail, property, murder, etc.)
- 32 demo cases with realistic priority scores

## Step 7: Install frontend assets

```bash
npm install
npm run build
```

## Step 8: Start the server

```bash
php artisan serve
```

Visit: **http://localhost:8000**

---

## Demo Login Credentials

| Role    | Email              | Password |
|---------|--------------------|----------|
| Admin   | admin@dcfm.test    | password |
| Judge   | judge1@dcfm.test   | password |
| Lawyer  | lawyer@dcfm.test   | password |
| Staff   | staff@dcfm.test    | password |
| Litigant| litigant@dcfm.test | password |

---

## Demo Flow (for presentation)

1. **Login as admin** → see the dashboard with 2,847 active cases, track distribution, 73 at-risk cases
2. **Go to Cases** → browse all cases sorted by priority score, filter by track
3. **Click a fast-track case** → see the DCFM score breakdown in the right panel
4. **Go to Cause Lists → Generate** → generate a cause list for tomorrow's date, see smart ordering
5. **Go to Analytics** → see disposal time comparison (Fast: 38 days vs Complex: 322 days)
6. **Logout → Login as lawyer** → see only your assigned cases

---

## Troubleshooting

**"Permission class not found"**
```bash
php artisan cache:clear
php artisan config:clear
```

**"SQLSTATE: table not found"**
```bash
php artisan migrate:fresh --seed
```

**Livewire not working**
```bash
php artisan livewire:publish --assets
```

**Views not found after copying files**
```bash
php artisan view:clear
php artisan cache:clear
```
