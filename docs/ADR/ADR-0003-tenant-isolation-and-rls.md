# ADR-0003: Tenant isolation and PostgreSQL Row-Level Security

Status: Proposed. Final decision after the Phase 2 spike.
Date: 2026-10-28

## Decision
1. Application-level authorisation and company-scoped repositories are the primary control.
2. PostgreSQL RLS is the second layer on every tenant table.
3. The runtime role `smartthink_app` is not the table owner and does not have BYPASSRLS.
4. Each request transaction sets `app.company_id` with `set_config(..., true)` and `app.user_id` likewise.

## Spike to run in Phase 2 (must pass before RLS is kept)
- Connection pool reuse does not leak `app.company_id` between requests (test with two interleaved requests).
- Transactions that forget to set the context return zero rows, not all rows.
- Migration role and runtime role are separate.

## Fallback
If the spike fails, RLS is removed from the plan, application scoping stays, and the test suite is expanded. This ADR is then updated to Superseded or Accepted-without-RLS, with the reason.

## Trade-off
RLS adds debugging cost. It protects against one forgotten WHERE clause, which is the most common cause of cross-tenant leaks.