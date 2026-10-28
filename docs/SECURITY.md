# Security

Status: design. Not yet implemented. Each control below has a test ID that must pass before the phase that owns it is marked done.

## Threat model (summary)
| Threat | Example | Primary control |
|---|---|---|
| Cross-company data access (IDOR) | Company A user changes an ID in the URL | Membership check + company-scoped queries + RLS + tests (TENANT_ISOLATION.md) |
| Credential theft | Brute-force login | Argon2id hashing, rate limiting, generic error messages |
| Session hijacking | Stolen cookie | HttpOnly, Secure (in production), SameSite=Lax, server-side expiry, logout invalidation |
| CSRF | Malicious site triggers a POST | SameSite cookie + Origin header check on state-changing routes + CSRF token for forms |
| XSS | Malicious text in candidate notes | React escaping, no raw HTML rendering, strict Content-Security-Policy, input validation |
| SQL injection | Crafted search text | Parameterised queries only (Kysely); no string-built SQL; lint rule against raw concatenation |
| Malicious upload | Disguised executable as CV | Extension and MIME allowlist, magic-byte check, size limit, random storage name, no execution |
| Document leakage | Public file URL shared | No public static serving of storage directory; authorised download endpoint only |
| Privilege escalation | Employee grants self Admin | Server-side permission check; role changes restricted to Company Admin; audited |
| Secret leakage | Token in Git or logs | .gitignore, .env.example, log redaction of auth headers and cookies, secret scan before commit |
| Dependency vulnerability | Known CVE in a package | `npm audit` in Phase 6, lockfile committed, upgrade policy |
| Insider misuse | Staff exports all candidates | Export permission, export audit, row limits |

## Authentication
- Passwords: Argon2id with parameters chosen in Phase 2 and recorded in ADR. Minimum length 12 characters. No composition rules that reduce strength.
- Login failures return the same message for unknown email and wrong password.
- Sessions: random 256-bit token, only its SHA-256 hash stored in the database. Idle timeout 8 hours, absolute timeout 7 days (defaults, configurable).
- Cookie: `HttpOnly`, `SameSite=Lax`, `Path=/`, `Secure` when `NODE_ENV=production`.
- Password change or deactivation invalidates all sessions of that user.

## Authorisation
- Every protected route declares a required permission. Routes without a declaration fail the build (route registry test).
- Platform Super Admin is a separate flag on the user, not a company role. Platform actions are audited.
- There is no master password, no hidden support login, and no unrestricted support view of company data. If support is ever needed, it will be a separate, audited, time-limited feature and its own ADR.

## Input validation
- Every request body, query, and path parameter is validated with zod before the service layer.
- Maximum lengths on every text field. Pagination `limit` capped at 100.
- Output DTOs exclude internal fields such as password hashes and token hashes.

## HTTP hardening
- `@fastify/helmet` for security headers, including Content-Security-Policy, X-Content-Type-Options, Referrer-Policy, and frame-ancestors none.
- CORS disabled for other origins. The web app is served from the same origin in production.
- Request body size limit set explicitly (for example 1 MB for JSON, separate limit for uploads).
- Error responses use fixed codes and messages. Stack traces are logged server-side only.

## Uploads and files
- Allowed: PDF, JPG, PNG for documents in MVP. DOCX is accepted only for CV source if Phase 4 confirms it can be validated. Others rejected.
- Maximum 10 MB per file (configurable), total per candidate limit set in Phase 4.
- Type checked by file signature (magic bytes), not only extension or the browser MIME type.
- Stored under a random UUID file name, outside any web-served directory.
- Served only through the authorised download endpoint with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
- Malware scanning: not included in MVP. Risk accepted and recorded (R-08). Decision on an optional local scanner is made in Phase 6.

## Logging and audit
- Logs are structured JSON. Redacted fields: `password`, `cookie`, `authorization`, `token`, `session`, `set-cookie`.
- Audit log is append-only at the application level: no update or delete endpoint exists. Database role `smartthink_app` has no UPDATE or DELETE grant on audit table (checked in migration test).
- Audit row fields: actor user ID, company ID (nullable for platform), action, target type, target ID, result, IP address, timestamp, request ID.

## Rate limiting
- Login: 5 failures per 15 minutes per email and per IP (values confirmed in Phase 2).
- Password reset and invitation: limited per email.
- General API: per-user limit, generous enough for normal use.

## Secrets
- Real values only in a local `.env` (never committed) or the server's environment file with restricted permissions.
- `SESSION_SECRET` generated locally. Rotation procedure in OPERATIONS.md.
- Database password for `smartthink_app` is generated, not reused from the superuser.

## Checks that must pass before release
1. Tenant isolation suite (TENANT_ISOLATION.md) green.
2. `npm audit` reviewed. High and critical items fixed or documented with reason.
3. Secret scan on the repository and on the `git log` history.
4. Manual review of the route registry: every route has a permission or is marked public with a reason.
5. Upload test set rejects disguised files and oversized files.

## Known limitations (to be updated)
- No penetration test by a third party. Planned as a recommendation before real client data is stored.
- No malware scanning in MVP.
- Single server, so no high availability.
- Phase 0 document. Actual results are recorded in TESTING.md and PHASE_STATUS.md in later phases.