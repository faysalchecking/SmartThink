# Changelog

## [Unreleased]

### 2026-10-29: Stack change to Laravel and React (Phase 0 re-issued)
- Backend changed from Fastify/Kysely to Laravel (PHP). Frontend stays React + TypeScript. Database PostgreSQL 18. Documentation Markdown.
- ADR-0001 to ADR-0004 rewritten. ADR-0005 (PDF renderer) added as Proposed.
- Architecture, security, tenant isolation, testing, installation, deployment, backup, operations, roadmap, risks, and phase status rewritten for the new stack.
- API_SPEC and DATABASE_SCHEMA: conventions changed; route and table definitions unchanged.
- No code changed in this entry. Node prototype archival pending owner approval (D-16).

### 2026-10-29: Phase 0 approved (for the earlier Node stack; now superseded by the entry above)
- Documentation set committed in several commits, see Git history.
- Repository cleanup: lowercase/uppercase filename normalised for PHASE_STATUS.md.

### 2026-10-28: Phase 0 foundation
- Environment audit, `.gitignore`, `.env.example`, first documentation commits.
- Database `smartthink_dev`, roles `smartthink_app` (runtime) and later `smartthink_migrator`.
- Commits pushed to `origin/main`: `c9b0343`, `205eee5`, `983b4d5`, `4bfd43b`.
### 2026-10-29: Phase 2 (authentication, tenancy, audit)
- Sanctum SPA auth, login rate limit, company and membership model, invitations, member management, audit log with append-only enforcement, RLS on invitations, CSRF enforcement, account-disable session invalidation.
- Decisions D-18 to D-27 recorded in RISKS_AND_DECISIONS.md.
- Test suite: 49 passing on smartthink_test. Pint and Larastan clean.