# SmartThink

SmartThink is a multi-tenant Applicant Tracking System (ATS) for recruitment and overseas manpower companies. One installation serves many companies. Each company's data is isolated and visible only to its authorised members.

Status: Phase 0 (documentation re-issued for the Laravel stack, awaiting owner confirmation). No product feature is implemented or tested yet. See `docs/PHASE_STATUS.md`.

## Stack (final)
- Backend: Laravel (PHP), PostgreSQL 18
- Frontend: React + TypeScript, built with Vite
- Documentation: Markdown

## Repository layout (planned)
# Roadmap (full scope, Laravel + React stack)

Status: Full scope accepted by owner (D-08). Stack changed to Laravel and React on 2026-10-29 (D-11). No feature is cut.

## Timing
The owner's 30-day target is a goal, not a promise. Estimate for the full scope at about 6 to 8 focused hours per day: roughly 6 to 8 weeks. Re-measured at the end of each phase.

## Phases
| Phase | Scope | Acceptance |
|---|---|---|
| 0 | Audit, documentation, ADRs, roadmap (re-issued for Laravel) | Owner's written confirmation |
| 1 | Foundation: Laravel app, React app, PostgreSQL connections, migrator role, health endpoint, Pint, Larastan, PHPUnit, ESLint, Prettier, Vitest, `scripts\check.ps1`, setup script | `scripts\check.ps1` passes on a clean clone; migration up and down pass; health endpoint returns ok |
| 2 | Authentication (Sanctum), companies, memberships, roles, invitations, audit log, RLS spike, tenant isolation suite | All AU, CO, PA, AL items; TI-001 to TI-018 pass |
| 3 | Clients, jobs, candidates, configurable pipeline and overseas stages, interviews, history, search, pagination, dashboard | All CA, CL, JO, PL, IN, AC, SE, DA items |
| 4 | Documents (secure upload and download, review), CV builder (3+ templates, edit, reorder, review gate), PDF export (ADR-0005), PDF tests | All DO and CV items; PDF fixture review recorded |
| 5 | CSV export with permission and audit, pipeline report, notifications, optional features | All EX, RE, and remaining S and C items |
| 6 | Security hardening, `composer audit` and `npm audit`, performance on seeded data, backup and restore test | Restore test passes and is logged |
| 7 | Deployment guide, server setup, user guide, operations runbook, clean install on a second machine | Clean install succeeds and is logged |

## Dependencies
Phase 2 needs 1. Phase 3 needs 2. Phase 4 needs 3. Phase 5 needs 3 and 4. Phase 6 needs everything before it. Phase 7 needs 6.

## Re-planning rule
At the end of each phase: actual days, remaining estimate, and the owner decides whether to keep the pace or add time. No security or test step is removed to save time.