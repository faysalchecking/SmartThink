# Tenant isolation

## Goal
A user of Company A must never read, create, update, delete, enumerate, search, export, or download any record belonging to Company B. This includes IDs, counts, file names, and error messages that could reveal another company's data.

## Rules
1. Every tenant-owned table has a `company_id` column, not null, with a foreign key to `companies`.
2. `company_id` is never accepted as authority from the client body. If a company is in the URL, it is used only to look up a membership. The membership decides access.
3. The server resolves the company context from the active membership of the session user. The client cannot choose a company that the user is not an active member of.
4. Every repository function takes `companyId` as a required argument. Queries on tenant tables always filter by it. A lint rule flags repository queries without a company filter, with an explicit allowlist for platform tables.
5. Cross-company lookups return 404 (not 403), so the existence of another company's record is not revealed.
6. Background jobs, exports, reports, and file downloads run with an explicit company context and re-check it.
7. Platform tables (for example `companies` list, platform users) are accessed only by platform routes, which require the Super Admin flag.

## Defence in depth: PostgreSQL Row-Level Security
Decision: RLS is enabled on tenant tables as a second layer (ADR-0003).
- The application sets `app.company_id` per transaction (`set_config('app.company_id', $1, true)`).
- Policies allow rows where `company_id = current_setting('app.company_id')::uuid`.
- The owner of tables does not bypass RLS. The application role does not have `BYPASSRLS`.
- Platform operations use a separate, explicit code path that sets a platform flag and is tested.
- RLS is enabled in Phase 2 after the first tenant tables exist, and the test suite runs both with and without the policy to confirm it is effective.

Trade-off: RLS adds complexity to migrations and debugging. The benefit is that a missed WHERE clause in one query does not leak data. The decision is final only after the Phase 2 spike confirms it works with the connection pool. If the spike fails, the fallback is application-level scoping with stronger tests and the decision is recorded in ADR-0003.

## Test plan (automated, required before Phase 2 is done)
Two companies (A and B) are seeded, each with users of every role. For every tenant route and every table:

| Test | Expected |
|---|---|
| A user reads B's candidate by ID | 404 |
| A user updates B's job | 404, no change in database |
| A user deletes B's document | 404, file not removed |
| A user lists candidates | Only A's rows |
| A user searches with B's candidate name | No B rows |
| A user requests paginated page beyond A's data | Empty, not B's data |
| A user downloads B's document by guessed file ID | 404 |
| A user exports candidates | Only A's rows, audited |
| A user changes URL company ID to B | 404 or 403, no data returned |
| A user sends `company_id` of B in JSON body | Ignored or rejected; no effect |
| A user uses a session after being removed from A | 403/404 on all A routes |
| A deactivated member of B cannot access B | 403 |
| Background export job for A runs with A context only | Contains only A rows |
| Report counts for A exclude B | Counts match A's seed data exactly |
| Error messages do not reveal B's IDs or names | Checked by snapshot |
| RLS on: direct SQL as app role without context | 0 rows returned |

Each test is named with its ID (for example `TI-001`) and recorded in TESTING.md when run.

## Verification evidence
Results are added to PHASE_STATUS.md in Phase 2 with the exact command, date, and pass count. A failing test is never skipped to get a green build.