# Roadmap (full scope)

Status: Full scope accepted by owner (decision D-08). No feature is cut from the requirements.

## Timing
The owner's 30-day target is recorded as a goal, not a promise. Estimate for the full scope with one developer at about 6-8 focused hours per day: roughly 6-8 weeks. This is an estimate. It is re-measured at the end of each phase and the owner is told the updated figure.

## Phases and acceptance
| Phase | Scope | Acceptance |
|---|---|---|
| 0 | Audit, docs, architecture, roadmap | Owner review and written confirmation |
| 1 | Foundation: monorepo, lint, typecheck, Fastify, React, Kysely, migrations, setup script, test DB | `npm run check` passes on clean clone; migration up/down works |
| 2 | Auth, sessions, companies, memberships, roles, invitations, audit log, RLS spike, tenant isolation suite | All AU-, CO-, PA-, AL- items; all TI- tests pass |
| 3 | Clients, jobs, candidates, configurable pipeline and overseas stages, interviews, history, search, pagination, dashboard | All CA-, CL-, JO-, PL-, IN-, AC-, SE-, DA- items |
| 4 | Documents (secure upload/download, review), CV builder (3+ templates, edit, reorder, review gate), PDF export and PDF tests | All DO-, CV- items; PDF fixture review recorded |
| 5 | CSV export with permission and audit, pipeline report, notifications, optional features | All EX-, RE-, and remaining S/C items |
| 6 | Security hardening, dependency audit, performance on seeded data, backup and restore test | Restore test passes and is logged; audit findings fixed or recorded |
| 7 | Deployment guide, server script, user guide, operations runbook, clean install test on a second machine or VM | Clean install succeeds and is logged |

## Dependencies
Phase 2 needs 1. Phase 3 needs 2. Phase 4 needs 3. Phase 5 needs 3 and 4. Phase 6 needs everything before it. Phase 7 needs 6.

## Re-planning rule
At the end of each phase: actual days, remaining estimate, and the owner decides whether to continue at the same pace, add time, or add effort. No security or test step is removed to save time.