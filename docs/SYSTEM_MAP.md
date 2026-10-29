# System map

## Actors
- Platform Super Admin: operates the installation, creates and activates companies.
- Company Admin: manages one company (users, roles, settings, stages).
- Employee / Sub-admin: works on candidates, jobs, documents, and CVs as permitted.
- Anonymous visitor: can only reach the login page, invitation acceptance, and static assets.

## Logical components
[Browser]
| HTTPS (in production), session cookie
[Static web app: React build] ---- served by the API process
| /api/v1
[Fastify API]
|-- auth login, sessions, rate limit
|-- platform companies, Super Admin actions
|-- tenancy memberships, roles, permissions, invitations
|-- recruitment clients, jobs, candidates, pipeline, interviews
|-- documents upload, validation, review, download
|-- cv templates, drafts, review gate, PDF render
|-- exports CSV, permission and audit
|-- reports pipeline counts
|-- audit append-only log
|-- scheduler in-process jobs stored in PostgreSQL
|
|--> PostgreSQL 18 (smartthink_dev / smartthink_prod, role smartthink_app)
|--> Private file storage (FILE_STORAGE_PATH, outside the web root)
|--> Local PDF renderer (no external network call)

## Trust boundaries
| Boundary | Trusted side | Untrusted side | Enforced by |
|---|---|---|---|
| Browser to API | API validates everything | Browser input, URLs, cookies | zod validation, session check, permission check |
| API to database | Repositories with companyId | Any request-supplied ID | Company-scoped queries, RLS (ADR-0003) |
| API to file storage | Storage provider with random keys | File names and content from uploads | Type check by magic bytes, random storage key, no path from user input |
| Company A to Company B | Nothing shared | Everything else | Membership check and tests TI-001 to TI-016 |
| Owner to server (deployment) | Owner's secrets | Internet | Firewall, HTTPS, restricted `.env` permissions |

## Data ownership
- Platform data (users, sessions, companies, audit): owned by the platform.
- Tenant data (memberships, clients, jobs, candidates, documents, CV drafts): owned by one company, identified by `company_id`.
- Audit logs: belong to the company where the action happened (nullable for platform actions). Never edited.

## External dependencies
- Required: none at runtime beyond PostgreSQL and the local file system.
- Optional and off by default: an AI writing service (not in MVP).
- Email for invitations: planned for Phase 2. If no SMTP is configured, the invitation link is shown only to the Company Admin who created it, and this is recorded as a limitation.