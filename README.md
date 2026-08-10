# TaskFlow API

A multi-tenant SaaS backend for project & task management, built with **Laravel 12**. Companies sign up, get their own isolated workspace, and manage projects and tasks with role-based permissions — all behind a clean, token-authenticated REST API.

This project is a showcase of backend architecture and API design: multi-tenancy, authentication, and role-based authorization built from scratch on Laravel's core primitives (Eloquent, Sanctum, Policies) plus one well-known package (Spatie Permissions) — no scaffolding magic, no black boxes.

## Why this project

Most CRUD tutorials skip the part that actually makes a SaaS app hard: making sure Company A can never see Company B's data, and that a Developer can't do what a Manager can. This repo is a focused example of solving both problems cleanly:

- **Tenant isolation** is enforced explicitly at the query layer — every read scopes through `auth()->user()->company`, so there is no route, controller, or Eloquent shortcut that can leak another tenant's rows.
- **Authorization** is modeled with a real Laravel `Policy` (`TaskPolicy`) instead of scattered `if` checks, cleanly separating "can this role act on this resource" from the controller logic.
- **Roles** are backed by [Spatie's `laravel-permission`](https://spatie.be/docs/laravel-permission) rather than a hand-rolled `role` column, so permissions are database-driven and extensible without a schema migration for every new role.

## Tech stack

| Layer | Choice |
|---|---|
| Framework | Laravel 12 (PHP 8.2+) |
| Auth | Laravel Sanctum (API token auth) |
| Authorization | Native Laravel Policies + [spatie/laravel-permission](https://spatie.be/docs/laravel-permission) |
| Database | SQLite (dev/test) — swappable via standard Laravel config |
| Testing | PHPUnit / `php artisan test` |
| Tooling | Laravel Pint (code style), Laravel Pail (log tailing) |

## Domain model

```
Company  1 ──< User
Company  1 ──< Project  1 ──< Task >── User (assigned_to)
```

- **Company** — the tenant. Owns users and projects.
- **User** — belongs to exactly one company; authenticates via Sanctum; has a role (`admin`, `manager`, `developer`).
- **Project** — belongs to a company; owns tasks.
- **Task** — belongs to a project (and transitively a company); optionally assigned to a user; has a `status` (`pending` / `in_progress` / `completed`).

A `Task` deliberately has no `company_id` of its own — its tenant is reached by walking the relation graph (`task → project → company`), which is the pattern every controller follows when scoping queries.

## Role-based access control

| Role | Task visibility | Task actions |
|---|---|---|
| Admin | all tasks in own company | view, update, assign |
| Manager | all tasks in own company | view, update, assign |
| Developer | only tasks assigned to self | view, update own |

Enforced by `app/Policies/TaskPolicy.php` and called from controllers via `$this->authorize(...)` — the standard Laravel authorization flow, not a custom middleware stack.

## API reference

All endpoints are versioned under `/api/v1`. Send `Accept: application/json` on every request; authenticated routes also need `Authorization: Bearer <token>`.

**Testing note:** registration always assigns the new user the `admin` role for a brand-new company (see `AuthService::register()`) — there is no signup path for `manager` or `developer`. To exercise the Manager/Developer branches of `TaskPolicy`, seed roles (`php artisan db:seed --class=RoleSeeder`) and either assign a role via tinker (`$user->assignRole('developer')`) or add a second user to the same `company_id` and change their role that way.

### Auth

| Method | Path | Auth | Body |
|---|---|---|---|
| POST | `/v1/register` | none | `name` (required, string, max:255), `email` (required, email, max:255, unique), `password` (required, string, min:6, confirmed), `password_confirmation` (required, must match `password`) |
| POST | `/v1/login` | none | `email` (required, email), `password` (required) |
| POST | `/v1/logout` | Bearer token | — |

`register` and `login` both respond with `{ status, message, data: { user, token } }`. `token` is a Sanctum plain-text token — pass it as `Authorization: Bearer <token>` on every subsequent request. `logout` revokes only the calling request's current token.

### Projects

All project routes are scoped to `auth()->user()->company` — you can only ever see/edit/delete your own company's projects; no `ProjectPolicy` exists, scoping is done by hand in the controller.

| Method | Path | Body | Notes |
|---|---|---|---|
| GET | `/v1/projects` | — | Lists the caller's company projects, each with its `tasks` eager-loaded |
| POST | `/v1/projects` | `name` (required, string), `description` (nullable, string) | 201 on success |
| GET | `/v1/projects/{id}` | — | 404 if the project belongs to another company |
| PUT/PATCH | `/v1/projects/{id}` | `name` (sometimes, string), `description` (nullable, string) | Partial updates allowed |
| DELETE | `/v1/projects/{id}` | — | 204 No Content |

### Tasks

Task visibility/actions are role-gated via `TaskPolicy` (Admin/Manager: anything in their company; Developer: only tasks assigned to them). `assign` is further restricted to Admin/Manager only.

| Method | Path | Body | Who can call it |
|---|---|---|---|
| GET | `/v1/tasks` | — | Everyone — Admin/Manager see all company tasks, Developer sees only tasks `assigned_to` them |
| POST | `/v1/tasks` | `project_id` (required, must exist, and belong to caller's company), `title` (required, string), `description` (nullable, string), `assigned_to` (nullable, must exist in `users`) | Anyone authenticated (no `create` policy is enforced) |
| GET | `/v1/tasks/{id}` | — | Admin/Manager (same company) or the assigned Developer — else 403 |
| PUT/PATCH | `/v1/tasks/{id}` | `status` (**required**, one of `pending`/`in_progress`/`completed`), `title` (sometimes, string), `description` (sometimes, string) | Admin/Manager (same company) or the assigned Developer — else 403 |
| DELETE | `/v1/tasks/{id}` | — | Admin/Manager only (same company) — else 403 |
| POST | `/v1/tasks/{id}/assign` | `assigned_to` (required, must exist in `users`, and belong to the task's company) | Admin/Manager only — else 403 |

Note `status` is marked `required` on update even though the endpoint is also used for title/description-only edits — send the task's current `status` back if you're not changing it, or the request will fail validation.

### Quick test walkthrough

```bash
# 1. Register (creates a new company, returns a token, caller becomes that company's admin)
curl -s -X POST http://localhost:8000/api/v1/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"name":"Ada Lovelace","email":"ada@example.com","password":"secret123","password_confirmation":"secret123"}'
# → copy data.token from the response into TOKEN below

TOKEN="paste-token-here"

# 2. Create a project
curl -s -X POST http://localhost:8000/api/v1/projects \
  -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" \
  -d '{"name":"Launch Plan","description":"Q3 rollout"}'
# → copy the returned project "id" into PROJECT_ID below

PROJECT_ID=1

# 3. Create a task on that project
curl -s -X POST http://localhost:8000/api/v1/tasks \
  -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" \
  -d "{\"project_id\":$PROJECT_ID,\"title\":\"Write launch email\"}"

# 4. List tasks visible to the caller
curl -s http://localhost:8000/api/v1/tasks \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"

# 5. Update a task's status (TASK_ID from step 3's response)
curl -s -X PATCH http://localhost:8000/api/v1/tasks/1 \
  -H "Accept: application/json" -H "Content-Type: application/json" -H "Authorization: Bearer $TOKEN" \
  -d '{"status":"in_progress"}'
```

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate        # uses SQLite by default — database/database.sqlite
composer dev                # serve + queue:listen + pail + vite, concurrently
```

Run the test suite:

```bash
composer test
```

## Project status & roadmap

This is an actively developed portfolio project, not a finished product — and that's tracked deliberately rather than hidden. `PRD.md` and `MEMORIES.md` in this repo are a running spec and decision log, kept up to date as the project evolves. Currently open:

- Decide whether Admin and Manager should diverge in permissions (identical today).
- Decide whether there should be a signup path for `manager`/`developer` users, or whether role assignment should stay a manual/admin-only action.
- Expand test coverage beyond the framework's default skeleton tests — nothing is currently verified by an automated test.

## Project structure

```
app/
  Http/Controllers/Api/V1/   # Versioned REST controllers
  Http/Requests/              # Form request validation (RegisterRequest, LoginRequest)
  Models/                     # Company, User, Project, Task
  Policies/                   # TaskPolicy — role-based authorization rules
  Services/                   # AuthService — registration/login/logout business logic
database/
  migrations/                 # Schema, including Spatie permission tables
  seeders/                    # RoleSeeder — admin/manager/developer roles
routes/
  api.php                     # All API routes (/api/v1/...)
```
