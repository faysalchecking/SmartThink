# Tenant isolation

## Goal
A user of Company A must never read, create, update, delete, enumerate, search, export, or download any record belonging to Company B. This includes IDs, counts, file names, and error messages that would reveal another company's data.

## Rules
1. Every tenant table has a `company_id uuid not null` column with a foreign key to `companies`.
2. A `company_id` in the URL is used only to look up a membership. It is never trusted as authority.
3. Tenant models use the `BelongsToCompany` trait. Creating a record sets `company_id` from the resolved company, never from request input.
4. Only one code path can disable the company scope: an allowlisted test helper. An architecture test fails if `withoutGlobalScope` appears elsewhere.
5. A company the user cannot access returns 404, not 403.
6. Queue jobs, exports, reports, and file downloads carry an explicit company ID and re-check membership when they run.
7. Platform tables (`companies` list, platform users) are reached only through platform routes that require the Super Admin flag.
8. RLS is applied to every tenant table as defence in depth (ADR-0003).

## Test plan
Two companies (A and B) are seeded. Each has a Company Admin, an Employee, and a deactivated member. Each test is named with its ID and lives in `backend/tests/Feature/Tenancy/`.

| ID | Test | Expected |
|---|---|---|
| TI-001 | A user reads B's candidate by ID | 404 |
| TI-002 | A user updates B's job | 404, no change in database |
| TI-003 | A user deletes B's document | 404, file still on disk |
| TI-004 | A user lists candidates | Only A's rows |
| TI-005 | A user searches with B's candidate name | No B rows |
| TI-006 | A user requests a page beyond A's data | Empty list, no B rows |
| TI-007 | A user downloads B's document by guessed ID | 404 |
| TI-008 | A user exports candidates | Only A's rows, export audited |
| TI-009 | A user puts B's company ID in the URL | 404, no data returned |
| TI-010 | A user sends B's `company_id` in a JSON body | Ignored; the created record belongs to A |
| TI-011 | A user removed from A sends a request with the old session | 404 on all A routes |
| TI-012 | A deactivated member of B | 404 on all B routes |
| TI-013 | Queued export for A runs | Contains only A's rows |
| TI-014 | Dashboard counts for A | Match A's seed data exactly |
| TI-015 | Error messages | Do not contain B's IDs or names |
| TI-016 | Two requests on one database connection, A then B | B's request sees only B's rows (RLS spike) |
| TI-017 | Database role `smartthink_app` with no company context | 0 rows from each tenant table |
| TI-018 | Architecture test: `withoutGlobalScope` outside the allowlist | Test fails if found |

## Evidence
Results are recorded in PHASE_STATUS.md in Phase 2, with the exact command, date, and pass count. A failing test is never skipped to get a green result.