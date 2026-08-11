# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 12 API-only SaaS backend (multi-tenant: companies → projects → tasks) with Sanctum auth and Spatie roles/permissions. No frontend framework is wired up — `resources/views` is the stock Laravel welcome page; the real surface is `routes/api.php`.

**Read `PRD.md`, `MEMORIES.md`, and `memory/MEMORY.md` (plus the files it indexes) at the start of every session, before doing any work.** `PRD.md` is the product spec (reverse-engineered from code — correct it as real intent becomes clear). `MEMORIES.md` is a running decision log of what changed, why, and what's still open — append to it (don't rewrite history) whenever you make a non-obvious decision or find something worth remembering across sessions.

## Memory

All persistent Claude memory for this project is stored **locally on disk in this repo directory**, never in the global cross-session memory directory (`~/.claude/projects/.../memory/`) — see `memory/feedback_local_memory_only.md`. Both `MEMORIES.md` and `memory/` are gitignored: they exist only in the local working copy, are never committed or pushed, and are read straight off disk each session regardless of git tracking status. When you'd normally save a user/feedback/project/reference-type memory:

- Write it to `memory/<type>_<slug>.md` using the same frontmatter convention as the files already there (`name`, `description`, `metadata.type`, `metadata.modified`).
- Add a one-line pointer to it in `memory/MEMORY.md`.
- Do not create or write to any file under the global `~/.claude/projects/.../memory/` path for this project.

`MEMORIES.md` (repo root) stays the running decision log for code-level changes; `memory/` is for the more structured, cross-session user/feedback/project/reference memory types.

## Commands

```bash
composer install && npm install       # install deps
php artisan key:generate              # after copying .env.example to .env
php artisan migrate                   # sqlite by default (database/database.sqlite)

composer dev                          # runs serve + queue:listen + pail + vite concurrently
php artisan serve                     # API server only

composer test                         # clears config cache, then runs the suite
php artisan test                      # same, without the config:clear
php artisan test --filter=testName    # single test by name
php artisan test tests/Feature/ExampleTest.php   # single file

vendor/bin/pint                       # code style (Laravel Pint)
npm run build / npm run dev           # Vite (Tailwind v4), largely unused by any real view
```

Test env (`phpunit.xml`) forces `sqlite :memory:`, `array` cache/session/mail, `sync` queue — tests never touch the dev `database.sqlite`.

## Architecture

**Tenancy model**: `Company` → `hasMany` `User` and `Project`; `Project` `belongsTo` `Company`, `hasMany` `Task`; `Task` `belongsTo` `Project` and `belongsTo` `User` (as `assignedUser`, via `assigned_to`). `Task` has no `company_id` of its own — its tenant is reached via `task->project->company`.

**Tenant isolation is manual, not structural.** There is no global scope, no `BelongsToCompany` trait, and no middleware that binds a "current company." Every controller enforces scoping by hand-chaining through the relation, e.g. `auth()->user()->company->projects()->findOrFail(...)` (`app/Http/Controllers/Api/V1/TaskController.php`, `ProjectController.php`). When touching these controllers or adding new ones, follow the same chain-through-`company` pattern explicitly — nothing else prevents a cross-tenant query.

**Authorization**: `TaskPolicy` (`app/Policies/TaskPolicy.php`) is the only policy. Admins/managers are authorized by `task->project->company_id === user->company_id`; developers only by `task->assigned_to === user->id`. It resolves via Laravel's default naming-convention auto-discovery (`Task` → `TaskPolicy`) — `AuthServiceProvider::$policies` is declared but inert because the provider extends the generic `Illuminate\Support\ServiceProvider` instead of the auth-specific base, and `registerPolicies()` is never called.

**Routes** (`routes/api.php`): everything under `Route::prefix('v1')`. Public: `POST v1/register`, `POST v1/login`. Two `auth:sanctum` groups: one for `POST v1/logout`, another for `apiResource('projects', ...)` and `apiResource('tasks', ...)`. `routes/web.php` only has `/` (welcome view) and `/admin` (gated by Spatie's `role:admin` middleware alias).

**Roles/permissions**: Spatie `laravel-permission` with default config/table names (`config/permission.php` unmodified, `teams => false`). `User` uses `HasRoles`. Role checks in code use lowercase names (`hasRole(['admin','manager'])` in `TaskPolicy`, `role:admin` in `routes/web.php`) — keep any new role-related code lowercase to match.

## Known issues (relevant when touching auth/roles/seeding)

- ~~`App\Models\Role` bugs~~ — fixed 2026-08-09. `RoleSeeder` now imports `Spatie\Permission\Models\Role` and seeds lowercase names (`admin`, `manager`, `developer`) via `firstOrCreate` (idempotent, fills `guard_name` from config). `AuthService::register()` now calls `$user->assignRole('admin')` instead of setting a `role_id` column. `User::$fillable` no longer lists `role_id` (the column never existed).
- ~~`Company::$fillable` empty~~ — fixed 2026-08-09. Added `protected $fillable = ['name'];` to `app/Models/Company.php`. `AuthService::register()` now completes end-to-end (verified via tinker).
- ~~`Project`/`Task` `$fillable` empty~~ — fixed 2026-08-09, same bug class as `Company` above. Neither model declared `$fillable`, so `ProjectController::store()` and `TaskController::store()` threw `MassAssignmentException` on every call. Added `Project::$fillable = ['name', 'description']` and `Task::$fillable = ['title', 'description', 'status', 'assigned_to']` (`project_id`/`company_id`-style relation keys are deliberately excluded — set via relation, not user input). **If you add a new model with a `create()`/`update()` call site, check `$fillable` explicitly — this codebase has now hit this exact bug three times.**
- ~~`TaskController::assign()` had no registered route~~ — fixed 2026-08-09. Added `Route::post('tasks/{task}/assign', ...)` in `routes/api.php` (`apiResource` only exposes the standard 7 actions).
- ~~`ProjectController@update/destroy` and `TaskController@index/destroy` were unimplemented stubs~~ — fixed 2026-08-09. All four now follow the existing patterns: `Project@update/@destroy` chain through `company` like `show()`; `Task@index` scopes by company and further by `assigned_to` for non-admin/manager roles; `Task@destroy` authorizes via `TaskPolicy::delete()` (previously hardcoded `false` — now mirrors `update()`'s admin/manager-in-company rule).
