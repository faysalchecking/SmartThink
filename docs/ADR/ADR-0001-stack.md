# ADR-0001: Technology stack and architecture style

Status: Proposed (becomes Accepted after owner approval of Phase 0)
Date: 2026-10-28

## Context
SmartThink is a multi-tenant ATS for recruitment companies. It must run on one Windows development PC now and on one small Linux server later. The owner is a solo developer with a 30-day target. The stack must be free to use, well supported, and easy to test strictly.

Verified environment (audit on 2026-10-28):
- Windows 11 Home, 64-bit
- Node.js v24.21.0, npm 11.19.0
- Git 2.55.0
- PostgreSQL 18.6, service running, port 5432 reachable
- pnpm and yarn not installed. npm is used for now.

## Decision
1. Architecture: modular monolith. One deployable backend, one frontend bundle, one PostgreSQL database. No microservices.
2. Frontend: React + TypeScript, built with Vite.
3. Backend: Fastify + TypeScript. Modules are split by domain (auth, companies, candidates, jobs, documents, cv, audit).
4. Database access: Kysely (typed SQL query builder). No heavy ORM. SQL stays visible and reviewable, which matters for tenant-isolation review.
5. Migrations: versioned SQL files managed by node-pg-migrate. Every schema change is a committed migration.
6. Database: PostgreSQL 18 (already installed). Application uses a least-privilege role, never the `postgres` superuser.
7. Files: private local storage behind a `StorageProvider` interface. An S3-compatible provider can be added later without changing business code.
8. PDF: deterministic, local HTML-to-PDF generation with a template system. No AI API is required.
9. Package manager: npm with committed `package-lock.json`. pnpm is optional later.
10. Docker: not required for development. Reconsidered for deployment in Phase 7.

## Alternatives considered
- NestJS: strong structure, but heavier and more boilerplate for a 30-day solo build. Rejected for now.
- Prisma: good DX, but its migration and query layer make explicit row-level security and custom SQL harder to review. Rejected.
- Drizzle: viable. Kysely was chosen for its closer-to-SQL API. This can be revisited in an ADR if needed.
- Microservices: rejected. Operational cost is too high for one developer and one server.
- Redis, queues, Kubernetes: rejected for MVP. Background work starts as in-process jobs stored in PostgreSQL.

## Consequences
- Easy to run and debug on one PC.
- Tenant isolation is enforced in application code and tested, with PostgreSQL Row-Level Security added as defence in depth (decision in ADR-0003).
- Scaling beyond one server would need a separate ADR (for example, moving files to object storage and splitting the worker process).

## Cost model
- One-time: no software license cost. The stack is open source. Developer time is the main cost.
- Recurring (estimates only, prices not verified): domain name, one small Linux VPS, off-site backup storage, transactional email (only if needed). Prices must be checked against current region-specific pricing before any budget is promised.
- Optional and not required: paid AI API for CV writing assistance.