# 30-day roadmap

Assumed start: Phase 1 begins on the day the owner approves Phase 0 (Day 1 below).
Days are working days of the calendar. Schedule assumes one developer and about 6 focused hours per day. Any slip is handled by cutting Should/Could items, not by cutting security or tests.

| Days | Phase | Deliverables | Acceptance (summary) |
|---|---|---|---|
| 1-2 | Phase 0: docs, architecture, scope | All Phase 0 docs, ADR-0001 to ADR-0004, git history | Owner approves scope and this roadmap |
| 3-6 | Phase 1: foundation | Monorepo layout, lint/format/typecheck, Fastify skeleton, React skeleton, Kysely + migrations, smartthink_app role, setup script, first test run | `npm run check` passes on a clean clone; migration up/down works |
| 7-12 | Phase 2: auth and tenancy | Login, sessions, rate limit, company and membership, roles, invitations, deactivation, audit log, tenant-isolation test suite | All AU-, CO-, PA- items pass; cross-tenant test suite green |
| 13-18 | Phase 3: recruitment core | Clients, jobs, candidates, pipeline stages, configurable overseas stages, interviews (S), activity history, search and pagination, dashboard counts | All CA-, CL-, JO-, PL-, AC-, SE- items pass |
| 19-24 | Phase 4: documents and CV | Secure upload and download, document review, CV builder with 3 templates, PDF export, visual and programmatic PDF tests | All DO-, CV- items pass; PDF fixtures reviewed |
| 25-26 | Phase 5: export and reports | CSV export with permission and audit, basic pipeline report (RE-01). Notifications and extras are cut unless ahead of schedule | EX- items pass |
| 27-28 | Phase 6: hardening | Security headers, dependency audit, CSRF check, rate-limit review, performance check on 10k candidate seed, backup and restore test | Restore test succeeds and is logged; audit findings fixed or recorded |
| 29-30 | Phase 7: deployment and release | Deployment guide, server setup script, user guide, operations runbook, release notes, final verification | Clean install on a second machine or VM succeeds |

## Dependencies
- Phase 2 needs Phase 1 (database and migrations).
- Phase 3 needs Phase 2 (every record needs company scope).
- Phase 4 needs Phase 3 (documents attach to candidates).
- Phase 6 needs everything before it, so buffer days are placed after Phase 4 and 5.

## Buffer and cut rules
- Buffer: about 2 days are spread across Phases 2-4.
- If behind by more than 2 days at the end of Phase 4, Phase 5 is reduced to CSV export only.
- Cut order: C items, then S items, then reporting. Never cut: tenant isolation tests, audit of privileged actions, PDF review gate, restore test.

## Honest risk statement
One month is enough for a secure, tested MVP covering the Must items above. It is not enough for every item in the original brief at production polish. The owner must accept the cut rules in RISKS_AND_DECISIONS.md.