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
| D-25 | RLS is ENABLE, not FORCE. The owner role is the migration role, which never serves requests. Runtime role has no BYPASSRLS and is not the owner | FORCE would also restrict the invitation lookup function, which must run before a company is known | Active |
| D-26 | RLS covers invitations only for now. memberships (keyed by user, read before company is known) and audit_logs (includes platform rows) are protected by ResolveCompany, grants, and triggers instead | Keeps the spike small. Candidates and later tenant tables get RLS from Phase 3 | Active |
| D-27 | Public invitation accept uses SECURITY DEFINER function invitation_company_for_token, which returns only a company ID | Only way to find the company for a token before any company context exists | Active |
| D-31 | Candidate passport number is stored with Laravel's encrypted cast, and the API returns only the last three characters | Protects the most sensitive identifier at rest. Depends on APP_KEY, see R-18 | Active |
| D-32 | Every company gets eight default stages, including overseas stages. Companies may disable overseas stages when no active application uses them | Overseas workflow is available, but not forced on domestic agencies | Active |
| D-33 | stage_history is append-only: trigger, REVOKE UPDATE, DELETE, TRUNCATE for the runtime role, and model events | Application history must be trustworthy for audits | Active |
| R-20 | Full passport number is not yet returned by any endpoint. A permission-controlled reveal comes with documents in Phase 4 | Least exposure until a permission model exists | Open |
| D-34 | Any active company member can schedule interviews and record results. Interviewer must be an active member of the same company | Same first-batch permission model as D-28 | Active |
| D-35 | Dashboard counts run inside the company context, so RLS and the application scope both apply | Prevents cross-company totals (TI-014) | Active |
| D-36 | Documents use the Laravel `local` disk, whose root is storage/app/private, outside public/. This replaces the disk name "private" in ADR-0004 | Laravel's default private disk is already outside the web root, so no config change is needed | Active |
| D-37 | File type is checked in three places: the mimes rule, the magic-byte signature, and the size limit. Extension and browser type are never trusted | Blocks disguised files | Active |
| D-38 | Any active company member can upload, download, and review documents in this batch. Finer document permissions come with the permission model | Same first-batch model as D-28 | Active |
| R-21 | No malware scanning on uploads | Accepted risk until Phase 6 decision | Open |
| D-39 | A CV draft is a copy of the candidate fields at creation time. Editing a draft never changes the candidate record | Keeps the verified master data clean; the CV can be tailored per job | Active |
| D-40 | Review stores a SHA-256 fingerprint of content, section order, visibility and template. Any later change clears the review. The 4C export must check the fingerprint before rendering | Stops a PDF being produced from content that was not reviewed | Active |
| D-41 | Template keys are fixed in code (classic, modern, compact). Rendering is added in 4C | Keeps templates deterministic and testable | Active |
| D-42 | Passport number is never copied into a CV | Least exposure of the most sensitive identifier | Active |
| D-47 | Export permission is a flag on the membership. Company admins always have it; employees need it granted by an admin. Changes are audited | Explicit permission (EX-02) without a full role editor yet | Active |
| D-48 | CSV cells beginning with = + - @ or a tab or carriage return are prefixed with an apostrophe. The file starts with a UTF-8 BOM | Blocks CSV formula injection; BOM keeps Bangla names readable in Excel | Active |
| D-49 | Exports are capped at 5,000 rows and never include passport numbers or archived candidates. The audit entry stores the row count only | Limits bulk exfiltration; keeps personal data out of logs | Active |
| D-50 | Pipeline report counts active applications only, per job and stage, within the company scope | Placed, rejected and withdrawn are historical, not pipeline load | Active |
| R-25 | Export has no per-field permissions yet. An admin who grants export gives access to all permitted fields | Acceptable for MVP; revisit with the permission model | Open |