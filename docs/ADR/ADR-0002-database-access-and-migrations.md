# ADR-0002: Database access and migrations

Status: Proposed
Date: 2026-10-28

## Decision
- Query layer: Kysely with generated TypeScript types from the live schema (kysely-codegen, version pinned in Phase 1).
- Migrations: node-pg-migrate with SQL-based migrations committed to Git.
- All queries are parameterised. Raw string concatenation into SQL is forbidden and checked by an ESLint rule in Phase 1.

## Reasons
- The owner must be able to read every SQL statement during tenant-isolation review.
- A typed builder catches column and type errors at compile time without hiding SQL.
- Migrations as plain SQL are reviewable and easy to restore.

## Rejected
- Prisma: hides query shape, and its migration model makes RLS and custom SQL harder to review.
- TypeORM: decorator-heavy and less predictable with migrations.
- Raw `pg` only: safe, but duplicates typing work across the whole project.

## Consequences
- Each module repository is the only place with SQL.
- A failing migration stops the setup script. No partial manual fixes.

## Verification plan (Phase 1)
- `npm view kysely version time` and `npm view node-pg-migrate version time` before pinning.
- Smoke test: create, migrate up, migrate down, migrate up on `smartthink_dev`.