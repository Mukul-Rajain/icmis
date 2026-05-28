# Phase Notes — What's Built & What's Next

## ✅ What This Scaffold Includes

### Database Layer (11 migrations)
- `users` extended with judicial fields (user_type, phone, senior_citizen, bar_council_number…)
- `courts` — multi-court support with types and jurisdictions
- `judges` — assignments, availability, daily capacity
- `case_types` — 22 seeded Indian case types driving DCFM logic
- `cases` (main) — with track, priority_score, stage, flags
- `case_parties`, `case_lawyers` — relational data
- `hearings`, `adjournments` — hearing workflow + reason tracking
- `cause_lists`, `cause_list_items` — daily listing snapshots
- `case_documents` — document management
- `priority_score_history` — audit trail of score evolution

### Models (12 Eloquent models)
Complete with relationships, casts, scopes, computed attributes (`is_overdue`, `age_in_days`, `days_until_expected_disposal`), and activity logging on User + CourtCase.

### Services (6 — the brain of the system)
- **TrackClassifier** — rule-based fast/standard/complex assignment with explainability
- **PriorityScorer** — weighted formula: base + age + urgency + adjournments + stage + stakeholder
- **CauseListGenerator** — collects candidates, orders by track + score, detects lawyer conflicts, allocates time slots
- **DelayPredictor** — flags at-risk cases (safe/watch/at_risk/overdue) using statistical thresholds
- **CaseRegistrationService** — orchestrates registration with all DCFM rules applied
- **CaseNumberGenerator** — race-safe unique number generation

### Controllers
- DashboardController (role-aware: admin / judge / lawyer / litigant)
- CaseController (index, show, update stage, manual rescore)
- CauseListController (generate, view, publish, download PDF)
- HearingController (record outcome, adjourn with reason)
- AnalyticsController (KPIs, disposal trends, adjournment patterns, at-risk)
- PublicCaseController (public case status lookup)

### Livewire Components
- `CaseRegistration` with **live track preview** as user picks fields

### Views (15+ Blade files)
- Layouts with judicial aesthetic (Fraunces serif + Inter body, maroon accents)
- Admin dashboard with KPIs, track distribution, recent cases
- Judge dashboard with today's cause list and at-risk alerts
- Case detail view with **priority score breakdown card** (viva showcase)
- Cause list view with intelligent ordering + downloadable PDF
- Analytics with disposal trend charts (Chart.js)
- Public case status lookup

### Jobs, Commands, Notifications
- `RescoreAllCases` command (daily cron at 00:30)
- `GenerateDailyCauseLists` queued job (daily at 23:00)
- `HearingScheduledNotification`, `AdjournmentNotification`

### Tests
- `PriorityScorerTest` — 6 unit tests covering algorithm correctness
- `TrackClassifierTest` — 6 unit tests for classification rules

### Seeders
- 22 case types, 3 courts, demo users for every role, ~32 demo cases across all tracks with realistic priority spread

---

## 🚧 What You Still Need to Build

These are the gaps. Most are mechanical (forms, factories, edge cases) — the hard architectural work is done.

### 1. Auth scaffold (~30 mins)
Run `composer require laravel/breeze --dev && php artisan breeze:install blade` for login/register. Then customize the auth views to match the layout aesthetic.

### 2. Model factories (~1 hour)
Needed for tests to pass. Create:
- `database/factories/UserFactory.php` (extend Laravel's default)
- `CaseTypeFactory`, `CourtFactory`, `JudgeFactory`, `CourtCaseFactory`

Example for `CourtCaseFactory`:
```php
public function definition(): array {
    return [
        'case_number' => 'CASE/2026/' . str_pad($this->faker->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
        'title' => $this->faker->sentence(4),
        'case_type_id' => CaseType::factory(),
        'court_id' => Court::factory(),
        'filing_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
        'track' => 'standard',
        'priority_score' => 50,
        'current_stage' => 'registered',
        'status' => 'active',
        'hearing_count' => 0,
        'adjournment_count' => 0,
    ];
}
```

### 3. Document upload (~2 hours)
Wire up `CaseDocument` storage. Use Laravel's storage facade with a `case-documents` disk. Add a Livewire component for upload with file validation, mime check, and version tracking.

### 4. Hearing scheduling form (~1 hour)
Add a Livewire component or simple form to schedule hearings from the case detail view. The HearingController has the routes but no form yet.

### 5. Lawyer assignment from case detail (~30 mins)
Add a way to engage/disengage lawyers on a case from the case detail view.

### 6. Lawyer/Litigant dashboards (~2 hours)
Stubs are in `DashboardController` but `dashboard/lawyer.blade.php` and `dashboard/litigant.blade.php` views still need building. Pattern from `judge.blade.php`.

### 7. Notification dispatching wiring (~1 hour)
The notification classes exist but aren't dispatched yet. Add to:
- `HearingController::recordOutcome` (if next_hearing_date set, notify parties)
- `HearingController::adjourn` (notify of new date)
- `CauseListController::publish` (notify all stakeholders)

```php
$case->parties->filter->user_id->each(
    fn ($p) => $p->user->notify(new HearingScheduledNotification($hearing))
);
```

### 8. Hearings index/show views (~1 hour)
The controller exists, the routes exist, the views need building. Pattern from cases.

### 9. Adjournment patterns analytics view (~30 mins)
The controller method is built — `analytics/adjournments.blade.php` view needs creating.

### 10. PHPStan/static analysis polish
Add `larastan` (`composer require larastan/larastan --dev`) and fix any type issues.

---

## 🎯 Recommended Final Order

If you have 4 weeks left:

**Week 1:** Auth scaffold, model factories, run all migrations + seeders successfully, verify the case registration flow works end-to-end.

**Week 2:** Build hearing scheduling form, complete lawyer/litigant dashboards, wire up notifications.

**Week 3:** Document upload, hearings index views, adjournment analytics view, polish the UI.

**Week 4:** Write tests for `CauseListGenerator` and `DelayPredictor`, finalize the project report, prepare the viva demo with the **three wow moments** (see VIVA_GUIDE.md).

---

## 📦 Deployment

For a free demo deployment:
- Railway.app (free PostgreSQL + PHP)
- Render.com (similar)
- Or just localhost for the viva (perfectly acceptable for a college project)

For the viva, run `php artisan serve` + `php artisan queue:work` + `npm run dev` in three terminals.
