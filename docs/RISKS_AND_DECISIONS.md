# Risks and decisions

Last updated: 2026-10-29

## Decisions
| ID | Decision | Reason | Status |
|---|---|---|---|
| D-01 | Modular monolith, not microservices | One developer, one server | Active |
| D-02 | Laravel backend, React + TypeScript frontend, PostgreSQL 18, Markdown docs | Owner's final choice | Active (replaces D-02 of 2026-10-28, Kysely) |
| D-03 | Local private file storage behind a Laravel disk | Lowest cost; S3-compatible later | Active |
| D-04 | English UI first, translation keys from the start | Requirement; Bangla later | Active |
| D-05 | CV generation deterministic and local; AI optional and off | No recurring API cost | Active |
| D-06 | Background work through Laravel queue (database driver) and scheduler | No Redis in MVP | Active |
| D-07 | Owner performs the final `git push` decision | Stated requirement | Active |
| D-08 | Full scope accepted, no feature cut | Owner decision, 2026-10-28 | Active |
| D-09 | Separate `smartthink_dev` and `smartthink_test`; runtime role `smartthink_app` | Owner approval | Active |
| D-10 | Migrations run as `smartthink_migrator` (not superuser) | Replaces earlier plan to run as owner `postgres` | Active |
| D-11 | Stack: Laravel + React (TypeScript) + PostgreSQL + Markdown. Node backend (Fastify) dropped | Owner final decision, 2026-10-29 | Active |
| D-12 | Shared package folder (`packages/shared`) not created. Frontend and backend share no code; API contract is documented | Avoids cross-language sharing; one less build step | Active |
| D-13 | Password hashing Argon2id | Stronger than Laravel default bcrypt; verified in Phase 1 | Active |
| D-14 | Schema changes as Laravel migrations; PostgreSQL-only SQL inside them | Keeps migration history and rollback; SQL where needed | Active |
| D-15 | PDF renderer not chosen yet | Decided in Phase 4 with fixtures (ADR-0005) | Open |
| D-16 | Earlier Node prototype (`apps/`, root npm workspace, `scripts/*.mjs`, `package.json`, `tsconfig.base.json`, lint and prettier configs) is not used | Replaced by D-11. Archived outside the repository after owner approval | Pending approval |
| D-17 | Version pins are checked against the release date at install time (14-day minimum age), and recorded in `composer.lock` and `package-lock.json` | Supply-chain safety | Active |

## Risks
| ID | Risk | Impact | Mitigation | Status |
|---|---|---|---|---|
| R-01 | Full scope takes longer than 30 days | High | Honest estimate, re-measured each phase, owner decides pace. No quality step removed | Open, owner informed |
| R-02 | Claims of "zero bugs" or "perfect security" | High | Report only real test output and known limits | Open |
| R-03 | Cross-company data leak | Critical | Membership middleware, global scope with architecture test, policies, RLS spike, TI tests | Open |
| R-04 | Backups never tested | High | Restore test is a Phase 6 gate | Open |
| R-05 | Secrets committed to Git | Critical | `.gitignore` covers `.env` at every level; pre-commit scan; `.env` never shared | Mitigated (process) |
| R-06 | Passwords and token shared in chat earlier | High | Owner has rotated them. Owner confirms the GitHub token is revoked. Nothing secret is pasted again | Owner confirmation pending |
| R-07 | Windows path, CRLF and LF issues | Medium | `.gitattributes`; test setup on a clean clone | Open |
| R-08 | Malicious uploads | High | Content validation, size limit, random names, no public path | Open |
| R-09 | CV PDF layout varies | Medium | Fixture-based tests; ADR-0005 decision rule | Open |
| R-10 | Hosting and backup costs unknown | Medium | Verify prices before any budget is stated | Open |
| R-11 | Library incompatibility with PHP or Laravel version | Medium | Verify requirements and run `scripts\check.ps1` after each install | Open |
| R-12 | Migrations run as a privileged role | Medium | Migrator role has no superuser or BYPASSRLS; runtime role has no DDL | Mitigated (design) |
| R-13 | PHP and Composer not yet installed or verified | Medium | Audit first, install only with approval | Open |
| R-14 | Eloquent global scope bypassed in code | Critical | Architecture test, code review rule, TI-018 | Open |
| R-15 | Connection setting carried to the next request on a persistent connection | High | Reset in middleware `terminate()`; TI-016 spike | Open |
| R-16 | Laravel's default `RefreshDatabase` runs migrations as the runtime user and fails | Low | Use `DatabaseTruncation`; migrations run by script | Mitigated (design) |
| R-17 | Argon2id not available in the PHP build | Medium | Check `PASSWORD_ARGON2ID` in audit; fall back to bcrypt only with a new ADR | Open |
| R-18 | `APP_KEY` rotation logs everyone out and breaks encrypted data | Medium | Maintenance procedure in OPERATIONS.md | Open |
| R-19 | Scope is larger than the time available | High | See R-01 | Open |

## Open items for the owner
1. Confirm this documentation set in writing ("Phase 0 re-approved for Laravel stack").
2. Approve moving the old Node prototype out of the repository (D-16). Command is in the message accompanying these documents.
3. Approve installing PHP and Composer, after the audit output is reviewed.
4. Confirm that the GitHub token shared in chat is revoked (R-06).
5. Approve `smartthink_test` migrations through `smartthink_migrator`, as in D-10.