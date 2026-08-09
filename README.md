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

## API surface

All endpoints are versioned under `/api/v1`.

**Public**
```
POST   /v1/register     Register a user + auto-create their company, returns a Sanctum token
POST   /v1/login        Authenticate, returns a Sanctum token
```

**Authenticated** (`Authorization: Bearer <token>`)
```
POST   /v1/logout

GET    /v1/projects        List the caller's company projects (with tasks)
POST   /v1/projects        Create a project
GET    /v1/projects/{id}   Show a single project

POST   /v1/tasks           Create a task on a project the caller's company owns
GET    /v1/tasks/{id}      Show a task (policy-gated)
PATCH  /v1/tasks/{id}      Update a task (policy-gated)
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

- Wire up the `assign` endpoint route (controller method exists, route doesn't yet).
- Implement `Project@update/destroy` and `Task@index/destroy` (currently stubs).
- Decide whether Admin and Manager should diverge in permissions (identical today).
- Expand test coverage beyond the framework's default skeleton tests.

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
