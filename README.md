# DCFM Court Case Management System

A Laravel-based judicial case listing system implementing **Differentiated Case Flow Management** with intelligent priority scoring and automated cause list generation.

## Tech Stack

- **Laravel 11** — PHP 8.2+
- **MySQL 8** / PostgreSQL 14+
- **Livewire 3** — reactive UI
- **Tailwind CSS** — styling
- **Spatie Permission** — role-based access
- **Spatie ActivityLog** — audit trail
- **Redis** — queues & caching

## Initial Setup

```bash
# 1. Create fresh Laravel project
composer create-project laravel/laravel dcfm-court
cd dcfm-court

# 2. Install required packages
composer require spatie/laravel-permission spatie/laravel-activitylog \
                 livewire/livewire barryvdh/laravel-dompdf \
                 maatwebsite/excel intervention/image

# 3. Install Tailwind
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# 4. Copy the files from this scaffold into your project

# 5. Configure .env with database credentials, then:
php artisan migrate
php artisan db:seed

# 6. Publish vendor configs
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"

# 7. Run
php artisan serve
npm run dev
```

## Project Structure

```
app/
├── Models/              # Eloquent models
├── Services/            # Business logic (TrackClassifier, PriorityScorer, CauseListGenerator)
├── Http/
│   ├── Controllers/
│   └── Livewire/        # Livewire components
├── Console/Commands/    # Artisan commands for scheduled jobs
├── Jobs/                # Queued jobs (cause list generation, notifications)
└── Notifications/       # Email/SMS notifications

database/
├── migrations/          # Schema definitions
└── seeders/             # Demo data

resources/views/         # Blade templates
routes/                  # web.php, api.php
```

## Build Phases

- **Phase 1** Foundation: auth, roles, users, base UI
- **Phase 2** Case management core: registration, parties, documents
- **Phase 3** DCFM engine: TrackClassifier + PriorityScorer
- **Phase 4** Cause list generation with conflict detection
- **Phase 5** Hearings, adjournments, notifications
- **Phase 6** Dashboards & analytics
- **Phase 7** Polish, seeders, deployment

See PHASE_NOTES.md for what's included in this scaffold and what to build next.
