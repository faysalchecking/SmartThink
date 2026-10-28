# Testing strategy

## Levels
| Level | Tool (planned, versions pinned in Phase 1) | What it covers |
|---|---|---|
| Static | TypeScript strict, ESLint, Prettier | Types, lint rules, formatting |
| Unit | Vitest | Pure logic: validators, permission maps, CV layout functions |
| Integration | Vitest + real PostgreSQL test database | Repositories, migrations, transactions, RLS |
| Tenant isolation | Vitest integration suite (TI-001 to TI-016 in TENANT_ISOLATION.md) | Cross-company access |
| API | Supertest-style tests against Fastify inject | Routes, status codes, validation, auth |
| End-to-end | Playwright | Login, candidate flow, CV export |
| Accessibility | axe-core in Playwright | Critical violations block release |
| PDF | Fixture CVs, programmatic checks (page count, text presence, no overflow), visual snapshot review | CV-05 to CV-08 |
| Security | npm audit, secret scan, route registry test | SECURITY.md checks |
| Backup | Restore test script | BACKUP_RESTORE.md |

## Rules
1. A test is never deleted, skipped, or weakened to get green. If a test is wrong, the fix is recorded in CHANGELOG with the reason.
2. Test database is separate from `smartthink_dev`: `smartthink_test`.
3. Each phase records real results in PHASE_STATUS.md: command, date, passed/failed counts.
4. Anything that cannot run on the owner's PC is listed as "not run" with the reason.

## Commands (target, verified in Phase 1)
- `npm run lint`
- `npm run typecheck`
- `npm run test:unit`
- `npm run test:integration`
- `npm run test:e2e`
- `npm run build`
- `npm run check` runs all of the above in order.

## Coverage
No percentage target is promised now. Tenant-isolation and auth paths must have explicit tests regardless of coverage percentage.