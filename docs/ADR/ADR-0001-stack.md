# ADR-0001: Technology stack

Status: Accepted by owner, 2026-10-29. Supersedes the earlier ADR-0001 (Fastify, Kysely, Node-only backend).
Date: 2026-10-29

## Context
SmartThink is a multi-tenant ATS for one developer on a Windows 11 PC, later one Linux server. The owner has chosen the following stack and asked that it be final.

Verified on the owner's machine (audit output, 2026-10-28):
- Windows 11 Home, 64-bit
- Node.js 24.21.0, npm 11.19.0
- Git 2.55.0
- PostgreSQL 18.6, service running on port 5432

Not yet verified: PHP, Composer, Laravel version. These must be checked before Phase 1 starts (see INSTALLATION.md).

## Decision
1. Architecture: modular monolith. One Laravel application serves the JSON API. One React single-page application is the user interface.
2. Backend: Laravel, the latest stable release at Phase 1 install time, pinned by `composer.lock`.
3. Frontend: React with TypeScript, built with Vite. No server-side rendering. Routing with React Router.
4. Database: PostgreSQL 18. Application connects as a least-privilege role `smartthink_app`. Schema changes run as `smartthink_migrator` (owner of tables, no superuser, no BYPASSRLS).
5. Authentication: Laravel Sanctum in SPA mode (first-party cookie session with CSRF protection). No API tokens are issued to browsers in the MVP.
6. Password hashing: Argon2id through Laravel's hashing configuration.
7. Documentation: Markdown in `docs/`.
8. Scripts: PowerShell for Windows. Composer scripts and npm scripts for the individual parts.
9. Production topology: one Linux server. A reverse proxy serves the built React files and forwards `/api` and `/sanctum` to PHP-FPM running Laravel. Exact proxy choice is recorded in ADR at Phase 7.

## Languages used
- PHP: backend application, validation, authorisation, business rules, migrations (as Laravel classes).
- SQL: PostgreSQL-specific statements inside migrations (Row-Level Security, indexes, constraints, extensions).
- TypeScript (TSX): React frontend and frontend tests.
- PowerShell: Windows setup, checks, and backup scripts.
- Markdown: documentation.
- JavaScript: only inside frontend tooling config, and only where a tool requires it. No Node backend.

## Alternatives considered
- Fastify + React (previous decision): rejected by owner. Equally viable technically.
- NestJS: rejected. Adds a second large framework on the Node side.
- Laravel with Blade server-rendered pages: rejected. The owner asked for React.
- Laravel + Inertia: rejected for now. Adds a coupling layer the team does not need; the API-plus-SPA split is clearer for tenant testing.
- Next.js: rejected. Adds a Node server and a second backend concern.

## Consequences
- The backend test suite is written in PHP (PHPUnit). The frontend test suite is written in TypeScript (Vitest). Browser tests are written in TypeScript (Playwright).
- The previous Node backend prototype (`apps/api`, root npm workspace, version scripts) is not used. It is archived outside the repository after owner approval.
- Laravel's conventions (Eloquent, Policies, Form Requests, Middleware) become the standard for backend code. Custom abstractions are avoided.
- Two PHP-specific risks are recorded in RISKS_AND_DECISIONS.md: request-scoped database context, and Eloquent global-scope bypass.

## Cost model
- One-time: no licence cost. All components are open source. Developer time is the main cost.
- Recurring (categories, prices not verified): domain name, one Linux server, off-site backup storage, transactional email if needed.
- Optional and not required: paid AI API.