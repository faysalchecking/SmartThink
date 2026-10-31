# ADR-0002: Database access and migrations

Status: Accepted (follows ADR-0001). Supersedes the Kysely and node-pg-migrate decision.
Date: 2026-10-29

## Decision
1. Queries use Eloquent models for single records and relationships, and the query builder for lists, reports, and exports. No string-built SQL.
2. Schema changes are Laravel migration classes in `backend/database/migrations/`. Each migration has a working `down()` method, tested in Phase 1.
3. PostgreSQL-specific statements (Row-Level Security policies, `pg_trgm` extension, partial and composite indexes, check constraints not expressible in Laravel) are written as SQL strings inside a migration, using `DB::statement()`. Every such statement is a fixed string. Values are never interpolated.
4. Two database connections are defined in `config/database.php`:
   - `pgsql`: runtime connection, user `smartthink_app`. Used by the web application and queue worker.
   - `pgsql_migrator`: user `smartthink_migrator`. Used only by `php artisan migrate --database=pgsql_migrator`.
5. The runtime user cannot create or alter tables. Migrations must succeed under the migrator user only.
6. Primary keys are UUIDs (Laravel `uuid()` columns, generated in PHP with `Str::uuid7()` or equivalent, verified in Phase 1).
7. Timestamps are `timestamptz`. Enums are `text` with check constraints, not PostgreSQL ENUM types.
8. Destructive migrations on a table with data require a documented backup step before running.

## Reasons
- Laravel's migration history, rollback, and model layer are mature and widely understood.
- Keeping PostgreSQL-specific security (RLS) in explicit SQL makes it reviewable.
- A separate migrator role means the running application cannot weaken its own protection.

## Rejected
- Plain `.sql` files run by a custom script: loses Laravel migration history and rollback tracking.
- Running migrations as `smartthink_app`: the runtime role would need DDL rights, which weakens isolation.
- Raw PDO everywhere: duplicates Eloquent's safety features with no benefit.

## Verification (Phase 1)
- `php artisan migrate --database=pgsql_migrator` on `smartthink_test` succeeds.
- `php artisan migrate:rollback --database=pgsql_migrator` removes the proof table.
- A test confirms `smartthink_app` cannot run `CREATE TABLE`.