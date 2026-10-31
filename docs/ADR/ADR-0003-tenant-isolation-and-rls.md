# ADR-0003: Tenant isolation and PostgreSQL Row-Level Security

Status: Proposed. Final decision after the Phase 2 spike.
Date: 2026-10-29

## Decision
Three layers protect tenant data. The first two are required. The third is decided by the Phase 2 spike.

1. Application authorisation (required): every company route passes through a middleware that resolves the company from an active membership of the signed-in user. Every Eloquent model that holds tenant data uses a `BelongsToCompany` trait. The trait adds a global scope on `company_id` and sets `company_id` on create from the resolved company, never from request input. Policies decide what the member may do.
2. Query discipline (required): `withoutGlobalScope` and `withoutGlobalScopes` are forbidden outside an allowlist in `backend/tests/Architecture/`. An architecture test scans the code and fails on any use outside the allowlist.
3. PostgreSQL RLS (defence in depth, spike required): each tenant table has `ENABLE ROW LEVEL SECURITY` and `FORCE ROW LEVEL SECURITY`, with a policy:
   `USING (company_id = current_setting('app.company_id', true)::uuid)`.
   - `FORCE` makes the policy apply even to the table owner.
   - The `true` flag makes a missing setting return NULL, so the policy returns zero rows instead of an error.
   - The middleware sets the value with `SELECT set_config('app.company_id', ?, false)` at the start of the request. Because `is_local = false`, the value lasts for the connection, so `terminate()` resets it to an empty string.
   - Platform-level code that must see all companies uses no RLS bypass. It runs only in platform routes, and every such route is listed in SECURITY.md and tested.

## Why the connection setting is reset
PHP-FPM normally closes the database connection after each request, so a leaked setting is unlikely. But a persistent connection (`DB_PERSISTENT` or pgBouncer in transaction mode) could carry the value into the next request. Resetting in `terminate()` removes that risk. A test runs two requests from different companies on one connection and checks that the second sees only its own rows.

## Spike to run in Phase 2 (must pass before RLS is kept)
- Two companies, two requests in sequence on one connection: second company sees only its rows.
- A request with no company context returns zero rows from every tenant table.
- `smartthink_app` cannot disable RLS or change policies.
- `smartthink_app` has no `BYPASSRLS`.

## Fallback
If the spike fails, RLS is removed from the plan. Layers 1 and 2 stay, the test suite grows, and this ADR is updated to say RLS was rejected with the reason.

## Trade-off
RLS adds migration and debugging work. It protects against one forgotten `where('company_id', ...)`, which is the most common cause of cross-tenant data leaks.