# Architecture

## Style
Modular monolith. One Node.js backend process, one React single-page app served as static files, one PostgreSQL database, one private file storage directory.

## Components
Browser (React + TypeScript, Vite build)
| HTTPS, JSON, HttpOnly session cookie
v
Fastify API (apps/api)
|- auth module
|- companies module (platform + company settings)
|- members module (users, roles, invitations)
|- candidates, clients, jobs, pipeline module
|- documents module --> StorageProvider (local disk now, S3-compatible later)
|- cv module --> deterministic HTML template --> PDF renderer
|- audit module
|- jobs module (in-process scheduled tasks stored in PostgreSQL)
v
PostgreSQL 18 (smartthink_dev locally; least-privilege role smartthink_app)

## Backend module rule
Each module has the same internal layout:
modules/<name>/
routes.ts HTTP only: parse, validate, call service, return
service.ts business rules and authorisation checks
repository.ts SQL via Kysely, always takes companyId as a parameter
schema.ts request and response validation (zod)
types.ts module-internal types
*.test.ts unit tests
Routes never query the database directly. Repositories never decide permissions.

## Frontend structure
apps/web/src/
app/ routing, providers, layout
features/<name>/ pages, components, hooks, API client calls for one feature
shared/ UI primitives, formatting, error boundaries
i18n/ English strings file

## Request lifecycle for a protected company route
1. Session cookie read, session looked up, expiry and status checked.
2. Company ID taken from the URL path, then checked against an active membership of the session user.
3. Permission for the action checked from the role's permission set.
4. Service runs with the membership's companyId. Repository adds `company_id = $1` to every query.
5. Audit row written for privileged or data-changing actions.
6. Response returned. Errors map to fixed codes, never raw SQL messages.

Details and tests: TENANT_ISOLATION.md.

## Data flow for CV export
Candidate verified fields + chosen template + section order + review confirmation -> pure function builds an HTML document -> PDF rendered locally -> PDF stored as a generated export (private) -> download through authorised endpoint. No external AI or network call is made.

## Configuration
All configuration comes from environment variables validated at startup with zod. The app refuses to start with missing or weak values (for example `SESSION_SECRET` shorter than 32 characters).

## Background work
Jobs such as session cleanup, export file purge, and backup reminders run in the API process using a PostgreSQL table for scheduling and a lock to avoid double-run. No Redis or external queue in MVP. Decision recorded in RISKS_AND_DECISIONS.md (D-06).

## Scaling path (not needed for MVP)
1. Move files to an S3-compatible bucket (change StorageProvider only).
2. Run a separate worker process using the same code and database.
3. Add a read replica if reporting becomes slow.
Each step needs its own ADR before implementation.

## Open technical questions (decided in Phase 1 with verification)
- Exact versions of Fastify, Kysely, node-pg-migrate, zod, argon2, and the PDF renderer. Each is checked with `npm view <pkg> version time` before pinning, and recorded in the ADR.
- PDF renderer choice: Chromium-based print versus a pure-JS PDF library. Evaluated in Phase 4 with the same fixtures. Decision in ADR-0005.

