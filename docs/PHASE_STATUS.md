# Phase status

Last updated: 2026-10-29

## Phase 0: Audit, scope, documentation, architecture, roadmap
Status: RE-OPENED. Stack changed to Laravel + React + PostgreSQL + Markdown (D-11). Documentation re-issued. Owner's written confirmation required.

### Completed with real evidence
- Windows audit (2026-10-28): Windows 11 Home 64-bit, Node.js 24.21.0, npm 11.19.0, Git 2.55.0, PostgreSQL 18.6, service running, port 5432 reachable.
- Local Git repository on branch `main`. Remote `origin` set to `faysalchecking/SmartThink`. Push works (verified `git ls-remote`).
- `.gitignore` and `.env` handling verified (`git check-ignore` matched line 9 of `.gitignore`).
- Database and roles: `smartthink_dev` and `smartthink_test` exist. `smartthink_app` and `smartthink_migrator` are neither superuser nor BYPASSRLS (verified). `smartthink_app` cannot create tables (verified).
- `pg_hba.conf`: all `host` lines for 127.0.0.1 and ::1 use `scram-sha-256` (verified, 2026-10-29).
- Version release-age check run for the Node prototype (2026-10-28). Results are not reused for the Laravel stack; PHP packages are checked at install time.

### Written as documentation (not implemented, not tested)
All files listed in the README index. ADR-0001 to ADR-0005.

### Not done
- PHP and Composer audit: not run yet.
- Laravel install: not started (needs approval).
- Archiving the Node prototype: not done (needs approval).
- Owner confirmation of the re-issued documentation.

### Owner action required
1. Confirm the re-issued documentation in writing.
2. Approve or refuse archiving the Node prototype (D-16).
3. Run the PHP audit commands in INSTALLATION.md and send the output.
4. Confirm the GitHub token from earlier chat is revoked (R-06).

## Phase 1: Foundation
Status: NOT STARTED. Starts only after Phase 0 is confirmed.

## Commit message suggestion (Phase 0 re-issue)
`docs: re-issue phase 0 documentation for Laravel + React + PostgreSQL stack (ADR-0001 to 0005)`

## Known limitations
- Nothing in the application is built, installed, or tested. The Laravel and React versions are not yet pinned.
- Hosting and backup costs are categories only. No price is verified.
- The 30-day target for the full scope is not promised. Estimate: about 6 to 8 weeks, re-measured each phase.
## Phase 0: Audit, scope, documentation, architecture, roadmap
Status: APPROVED BY OWNER for Laravel + React + PostgreSQL stack (2026-10-29)
Commits: 85ea332 (latest push to origin/main)
Known limitations: no application code built or tested. Backup restore not verified. Hosting costs not verified.

## Phase 1: Foundation
Status: COMPLETE, PENDING OWNER APPROVAL

Evidence (owner's machine, 2026-10-29):
- scripts\check.ps1: ALL CHECKS PASSED
- Backend: Pint PASS (29 files), Larastan level 6 no errors, PHPUnit 3 passed on smartthink_test
- Migration schema_health: up, down, up verified on smartthink_test
- smartthink_app cannot CREATE TABLE (permission denied, verified)
- Frontend: lint, format, typecheck, build pass. npm audit --omit=dev: 0 vulnerabilities
- Health endpoint GET /api/v1/health returns ok

Versions: Laravel 13.37.0, Larastan 3.13.0, React 19.3.1, Vite 8.3.4, TypeScript 6.0.3, Vitest 5.0.4, PHP 8.5.11, PostgreSQL 18.6

Known limitations:
- Frontend has no test files yet (npm test uses passWithNoTests). Real tests start in Phase 2.
- Full npm audit (including dev tools) shows 11 moderate issues, all in development tooling. Recorded in RISKS_AND_DECISIONS.md, not fixed with npm audit fix --force.
- Tenant isolation, authentication, and RLS are not started.
- Backup and restore are not tested.

## Phase 2
Status: BATCH A APPROVED BY OWNER. BATCH B IN PROGRESS.
Batch A evidence (owner machine): scripts\check.ps1 ALL CHECKS PASSED.
Batch A covers: Sanctum SPA login/logout/me, login rate limit (5 per 15 min), company create/show/update, platform status change, membership resolution, audit log with append-only trigger and REVOKE, error envelope.
Not yet covered: CSRF tests (currently disabled in TestCase), RLS spike, invitations, membership management, full TI-001 to TI-018 suite.

## Phase 2: Authentication, companies, membership, tenant isolation
Status: COMPLETE, PENDING OWNER APPROVAL (2026-10-29)

Evidence (owner machine): scripts\check.ps1 ALL CHECKS PASSED. Full suite: 49 tests passing, Pint PASS, Larastan level 6 no errors.

Implemented:
- Sanctum SPA login, logout, me. Argon2id hashing. Generic credential error.
- Login rate limit: 5 attempts per 15 minutes per email and IP.
- Companies: platform create (pending), activate/suspend with transition rules, company show and rename (admin only).
- Membership resolution (ResolveCompany): unknown, foreign, malformed, deactivated return 404. Suspended company returns 403.
- Invitations: token shown once, only SHA-256 stored, single use, 7-day expiry, bound to one email. Accept with existing or new account.
- Member list (admin only, company scoped), role and status change, last-admin protection under row lock.
- Audit log: append-only via model events, database trigger, and REVOKE UPDATE, DELETE, TRUNCATE from runtime role. Sensitive metadata redacted.
- Row-level security on invitations (ENABLE, not FORCE), with narrow SECURITY DEFINER lookup for token accept. TI-016 and TI-017 pass.
- CSRF enforced on state-changing routes (real VerifyCsrfToken check in CsrfTest).
- Disabled account loses an existing session on its next request.
- Architecture test: no withoutGlobalScope, withoutGlobalScopes, or DB::unprepared in app code.

Open (not done in Phase 2):
- Password change with revocation of other sessions: [choose (a) built in B3 / (b) deferred to Phase 3]
- RLS on memberships and audit_logs: intentionally not applied (D-26). Protected by ResolveCompany, grants, triggers.
- TI-003, TI-005 to TI-008, TI-013, TI-014: depend on documents, candidates, export, dashboard. Covered in those phases.
- Local test database only. No production-like deployment tested.

Known limitations:
- Frontend has no UI for auth or companies yet. Phase 2 is API-only. Login screens belong to Phase 3 with the first real screens.
- Invitations have no email delivery (D-22). Token is shown to the admin once.
- Password change (B3): current password check, other sessions deleted, session ID rotated, audited. Tests: PasswordChangeTest (6 tests).
## Phase 3A: Clients and job orders
Status: COMPLETE, OWNER APPROVED (2026-10-29)
Evidence: ClientJobTest 7 tests passing, full suite passing, Pint and Larastan clean, scripts\check.ps1 ALL CHECKS PASSED.
Implemented: clients, job orders, composite foreign key (job to client of same company), RLS on both, BelongsToCompany trait, route parameters read by name.
## Phase 3B: Candidates, pipeline stages, applications, stage history
Status: IN PROGRESS. Code written, owner verification pending.
Scope: candidates (create, edit, archive, search), default and configurable pipeline stages with overseas kinds, applications with first-stage start, stage moves with history, append-only stage history, RLS on all new tables, composite foreign keys for same-company references.
Not in 3B: interviews, dashboard, pagination review (3C).
## Phase 3B: Candidates, pipeline stages, applications, stage history
Status: COMPLETE, PENDING OWNER APPROVAL
Evidence: scripts\check.ps1 ALL CHECKS PASSED. CandidatePipelineTest 17 tests passing. Full suite 79+ tests passing.
Implemented: candidates (create, edit, archive, search with escaped LIKE), passport encrypted at rest and masked in API, eight default pipeline stages with overseas kind, admin-only stage configuration, applications starting at first enabled stage, stage moves with append-only history, terminal stage closes application, RLS and composite foreign keys on all new tables.
Open: interviews, dashboard, pagination review (Phase 3C).
## Phase 3C: Interviews, dashboard, pagination review
Status: CODE COMPLETE, OWNER VERIFICATION PENDING
Implemented: interviews (schedule, record result, notes, reschedule), interview list filtered by application, dashboard counts (active candidates, active and placed applications, open jobs, upcoming interviews, active applications per stage), tests for scope and status rules.
Pagination: all list endpoints cap per_page at 100 (verified by existing code, not yet load-tested).
Not in 3C: React screens (separate UI batch), performance test on large seed data (Phase 6).
## Phase 3C: Interviews, dashboard, pagination review
Status: COMPLETE, OWNER VERIFIED (2026-10-29). InterviewDashboardTest 9 tests passing, scripts\check.ps1 ALL CHECKS PASSED.
## Phase 4A: Documents
Status: CODE COMPLETE, OWNER VERIFICATION PENDING
Implemented: upload (PDF, JPEG, PNG, max 10 MB, magic-byte check), random storage key, SHA-256 recorded, download as attachment with nosniff and audit, review (verified or rejected with reason), soft archive that keeps the file, company scope on every route, RLS and composite foreign key on documents.
Not in 4A: CV builder (4B), PDF export (4C).