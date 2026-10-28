# Risks and decisions

Last updated: 2026-10-28

## Decisions
| ID | Decision | Reason |
|---|---|---|
| D-01 | Modular monolith, not microservices | One developer, one server |
| D-02 | Kysely + node-pg-migrate, no heavy ORM | Explicit SQL, easier tenant review |
| D-03 | Local private file storage first, behind an interface | Lowest cost; S3-compatible later |
| D-04 | English UI first, i18n-ready | Requirement; Bangla later |
| D-05 | CV generation deterministic and local; AI optional and off | No recurring API cost |
| D-06 | Background jobs in-process, scheduled through PostgreSQL | No Redis or queue in MVP |
| D-07 | Owner performs the final `git push` decision | Stated requirement (later pushes were done with owner approval) |
| D-08 | Full scope accepted. No feature cut from requirements | Owner decision, 2026-10-28 |
| D-09 | Separate dev database `smartthink_dev` and runtime role `smartthink_app` (no superuser, no BYPASSRLS) | Owner approval, 2026-10-28 |
| D-10 | Migrations run as owner `postgres` on local dev until a migration role is designed in ADR | Simplicity for dev; to be revisited before deployment |

## Risks
| ID | Risk | Impact | Mitigation | Status |
|---|---|---|---|---|
| R-01 | Full scope takes longer than 30 days | High | Honest estimate in ROADMAP, re-measured each phase, owner decides pace. No quality steps removed | Open, owner informed |
| R-02 | Claims of "zero bugs" or "perfect security" | High | Report only real test output and known limitations | Open |
| R-03 | Cross-company data leak | Critical | Server authorisation, scoped queries, RLS spike, TI- tests | Open |
| R-04 | Backups never tested | High | Restore test is a Phase 6 gate | Open |
| R-05 | Secrets committed to Git | Critical | .gitignore first, secret scan before each commit, .env local only | Mitigated (process) |
| R-06 | GitHub token exposed in chat | High | Token must be revoked; owner confirms. Use Credential Manager only | Owner action needed |
| R-07 | Windows path, CRLF/LF issues | Medium | .gitattributes; test setup on clean clone | Open |
| R-08 | Malicious uploads | High | Magic-byte check, size limits, random storage names, no public URLs | Open |
| R-09 | CV PDF layout varies | Medium | Fixture-based PDF tests | Open |
| R-10 | Hosting and backup costs unknown | Medium | Verify current prices before any budget is stated | Open |
| R-11 | Library version incompatibility | Low | Verify with `npm view` and smoke tests in Phase 1 | Open |
| R-12 | Local migrations run as superuser | Medium | Documented in D-10; migration role in ADR before deployment | Open |

## Open items
1. Owner revokes the GitHub token shared in chat (R-06).
2. Owner confirms Git commit identity.
3. Phase 1 will also need a separate test database `smartthink_test`. Owner approval is requested at that time.