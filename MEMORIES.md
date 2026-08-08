# Memories

A running decision log for this repo: what changed, why, and what's still open. Newest entries on top. This is project-local and checked into git — distinct from Claude's cross-session personal memory, which tracks user/workflow preferences rather than project history.

## Open items

- `TaskController::assign()` has no route registered.
- `ProjectController@update/@destroy` and `TaskController@index/@destroy` are unimplemented stubs.
- No tests exist beyond the default Laravel skeleton examples (`tests/Unit/ExampleTest.php`, `tests/Feature/ExampleTest.php`).
- Open product questions live in `PRD.md` (Admin vs Manager distinction, multi-company users, cascade behavior, UI plans).

## Decision log

### 2026-08-09
- Created `CLAUDE.md`, `PRD.md`, and this file. `PRD.md` is reverse-engineered from the current code (no prior spec existed) and is expected to need correction as real product intent is clarified. This file is meant to be updated whenever a non-obvious decision is made or a bug/gap is found worth remembering across sessions — append new entries here rather than editing history.
- Fixed the `App\Models\Role` bugs: `database/seeders/RoleSeeder.php` now imports `Spatie\Permission\Models\Role` and seeds lowercase role names (`admin`, `manager`, `developer`) via `firstOrCreate` — matches the casing already used by `TaskPolicy::hasRole()` and the `role:admin` middleware in `routes/web.php`, and fills the required `guard_name` column that `Role::insert()` was skipping. `AuthService::register()` now calls `$user->assignRole('admin')` instead of setting a nonexistent `role_id` column; removed `role_id` from `User::$fillable` (dead column, never existed in the DB). Verified via `php artisan migrate:fresh --seed` + a `Company::forceCreate()`-based tinker check that `assignRole('admin')`/`hasRole('admin')` round-trip correctly (bypassed `Company::create()` for the test since it hit an unrelated pre-existing bug — `Company` has no `$fillable` at all, logged as a new open item above). Local dev `database.sqlite` was reset via `migrate:fresh` (no seed) afterward to leave it in a clean, empty-but-migrated state.
- Fixed the `Company::$fillable` bug: added `protected $fillable = ['name'];` to `app/Models/Company.php` (the `companies` table only has `id`/`name`/timestamps, so `name` is the only mass-assignable column). This was blocking `AuthService::register()` at the `Company::create()` step. Verified full registration flow end-to-end via tinker (`AuthService::register()` → company created, user created, `admin` role assigned, token issued). Local dev `database.sqlite` reset via `migrate:fresh` (no seed) afterward.
