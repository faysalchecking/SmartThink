# Testing

## Levels and tools
| Level | Tool | Covers |
|---|---|---|
| PHP style | Laravel Pint (`pint --test`) | Formatting |
| PHP static analysis | Larastan (PHPStan), level set in Phase 1 and not lowered | Types, undefined methods, dead code |
| PHP unit | PHPUnit (`php artisan test --testsuite=Unit`) | Services, rules, value objects |
| PHP feature | PHPUnit with HTTP requests (`--testsuite=Feature`) | Routes, validation, policies, responses |
| Tenant isolation | PHPUnit Feature suite `Tenancy` (TI-001 to TI-018) | Cross-company access |
| Architecture | PHPUnit `tests/Architecture` | Forbidden patterns (raw SQL, scope bypass) |
| Frontend types | TypeScript (`tsc --noEmit`) | React code |
| Frontend lint | ESLint with typescript-eslint | React and TypeScript rules |
| Frontend format | Prettier | Formatting |
| Frontend unit | Vitest with React Testing Library | Components, hooks, states (loading, empty, error) |
| End-to-end | Playwright (TypeScript) in `tests/e2e/` | Login, candidate flow, CV export |
| Accessibility | axe-core inside Playwright | No critical violations |
| PDF | PHP tests using fixture CVs; text-position checks | CV layout, page breaks |
| Backup | PowerShell restore script with checksum comparison | BACKUP_RESTORE.md |

## Databases for tests
- `smartthink_test` is used only by tests.
- Migrations for the test database run once through the migrator connection, by `scripts\setup-test-db.ps1`. Tests do not run migrations themselves.
- Test data is cleared between tests with `DatabaseTruncation`, not `RefreshDatabase`, because `RefreshDatabase` would run migrations as the runtime user.
- The runtime connection used by tests is `smartthink_app`, so tests exercise the same permissions as production.

## Commands (Phase 1 will verify each)
- `scripts\check.ps1`: runs everything in this order: backend Pint, Larastan, PHPUnit; frontend ESLint, Prettier check, TypeScript, Vitest, build.
- Backend only: `cd backend; composer run check`
- Frontend only: `cd frontend; npm run check`
- End-to-end: `cd tests\e2e; npm run test`

## Rules
1. A test is never deleted, skipped, or weakened to get a green result. If a test is wrong, the change and the reason go in CHANGELOG.md.
2. Lint rules are not disabled to pass the check. A rule change needs a written reason in RISKS_AND_DECISIONS.md.
3. Each phase records real output in PHASE_STATUS.md: command, date, pass and fail counts.
4. Anything that cannot run on the owner's PC is recorded as "not run", with the reason.
5. Tests that involve time, random data, or uploads use fixed seeds or fixtures.

## Coverage
No percentage is promised. Tenant isolation, authentication, validation, and the CV review gate have explicit tests regardless of coverage.