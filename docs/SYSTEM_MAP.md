# System map

## Actors
- Platform Super Admin: operates the installation, creates and activates companies.
- Company Admin: manages one company's members, roles, settings, and pipeline stages.
- Employee / Sub-admin: works on candidates, jobs, documents, and CVs as permitted.
- Anonymous visitor: can reach only the login page, invitation acceptance, and static files.

## Components and trust boundaries
| Boundary | Trusted side | Untrusted side | Enforced by |
|---|---|---|---|
| Browser to API | Laravel validation, policies | Browser input, URLs, cookies | Sanctum, Form Requests, Policies |
| Application to database | Models with company scope, RLS | Any ID supplied by a request | Membership check, global scope, RLS (ADR-0003) |
| Application to file disk | Random storage names | File names and bytes from uploads | Content validation, no path from user input |
| Company A to Company B | Nothing shared | Everything else | Membership check and tests TI-001 to TI-016 |
| Owner to server | Owner's secrets | Internet | Firewall, HTTPS, restricted `.env` permissions |

## Data ownership
- Platform data (users, sessions, companies, platform audit): owned by the platform.
- Tenant data (memberships, clients, jobs, candidates, documents, CV drafts): owned by one company through `company_id`.
- Audit logs: belong to a company, or to the platform for platform actions. Append-only.

## External dependencies
- Runtime: PostgreSQL, PHP, the private file directory. Nothing else is required.
- Optional, off by default: AI writing service (not in MVP).
- Email for invitations: decided in Phase 2. Without SMTP, the invitation link is shown once to the Company Admin who created it, and this is recorded as a limitation.