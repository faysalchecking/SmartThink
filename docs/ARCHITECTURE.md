# Architecture

## Style
Modular monolith. One Laravel application (API, authentication, business logic, scheduled jobs, queue jobs), one React single-page application (built to static files), one PostgreSQL 18 database, and one private file directory.

## Components
Browser
React + TypeScript SPA (Vite build)
| HTTPS in production, JSON, session cookie, CSRF header
v
Reverse proxy (production) / Vite dev server with proxy (development)

static files from frontend/dist
/api and /sanctum forwarded to PHP
v
Laravel application (backend/)
Sanctum SPA authentication
Company resolution middleware
Policies and Form Requests
Modules: Companies, Members, Recruitment, Documents, Cv, Exports, Reports, Audit
Queue (database driver) and scheduler
|
+--> PostgreSQL 18: connection smartthink_app (runtime), smartthink_migrator (migrations only)
+--> Private file disk: backend/storage/app/private
+--> PDF renderer (chosen in ADR-0005), no external network call

## Backend module layout
backend/app/Modules/<Name>/
Http/Controllers/ thin: receive request, call service, return resource
Http/Requests/ Form Request validation and authorisation hook
Http/Resources/ output shape; never exposes hashes, storage keys, or internal columns
Models/ Eloquent models; tenant models use BelongsToCompany
Policies/ authorisation decisions
Services/ business rules and multi-step writes in transactions
Controllers never contain SQL. Services never decide permissions alone: the Policy is always checked. Shared code lives in `backend/app/Support/` and is kept small.

## Frontend layout
frontend/src/
app/ router, providers, layout
features/<name>/ pages, components, API calls for one feature
shared/ UI primitives, formatting, error boundary
i18n/ English strings (translation keys, no hard-coded UI text)

## Request lifecycle for a protected company route
1. Session cookie checked by Sanctum. Unauthenticated requests get 401.
2. Company resolved from the route parameter and checked against an active membership of the signed-in user. No membership gives 404 (not 403).
3. Company context is set for the database connection (see ADR-0003).
4. Policy checked for the action.
5. Form Request validates input before the controller runs.
6. Service runs. Tenant models add `company_id` from the resolved company.
7. Audit entry written for privileged or data-changing actions, in the same transaction.
8. Response resource returned. Errors use a fixed JSON shape.
9. Middleware `terminate()` resets the company context.

## Background work
- Queue: database driver, table `jobs`. Worker runs as a long-lived process (`php artisan queue:work`).
- Scheduler: `php artisan schedule:run` runs every minute from the operating system scheduler (Windows Task Scheduler in development, cron in production).
- No Redis in the MVP.

## Configuration
All configuration comes from `backend/.env`, read by Laravel's `config/` files. Startup checks: `APP_KEY` must be set, `APP_DEBUG` must be `false` when `APP_ENV=production`, and `SESSION_SECURE_COOKIE` must be `true` in production. A Phase 1 test verifies these rules.

## Scaling path (not needed for MVP)
1. Move files to an S3-compatible disk (configuration change plus one adapter package).
2. Run the queue worker on a second process or machine.
3. Add a read replica if reporting becomes slow.
Each step needs its own ADR before implementation.
