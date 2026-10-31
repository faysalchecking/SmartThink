# Security

Status: design. Nothing here is implemented yet. Each control has a test that must pass before the owning phase is marked done.

## Threat model
| Threat | Example | Primary control |
|---|---|---|
| Cross-company access (IDOR) | Company A user changes an ID in the URL | Membership check, company-scoped models, RLS, TI tests |
| Credential guessing | Repeated login attempts | Argon2id hashing, rate limiting, generic error messages |
| Session theft | Stolen cookie | HttpOnly, Secure in production, SameSite=Lax, Sanctum expiry, logout invalidation |
| CSRF | Malicious site triggers a POST | Sanctum CSRF protection, Origin check on state-changing routes |
| XSS | Malicious text in candidate notes | React escaping, no `dangerouslySetInnerHTML`, Content-Security-Policy header |
| SQL injection | Crafted search text | Eloquent and query-builder bindings; raw SQL only as fixed strings |
| Mass assignment | Request sets `company_id` or `role` | `$fillable` lists, no `$guarded = []`, company set by the server |
| Malicious upload | Executable disguised as PDF | Content-based validation, size limit, random names, no execution, no public path |
| Document leak | Public file URL | Private disk outside `public/`, authorised download controller only |
| Privilege escalation | Employee grants self Admin | Policy checks, role changes restricted to Company Admin, audited |
| Secret leakage | `.env` or `APP_KEY` in Git or logs | `.gitignore`, `.env.example` without values, log redaction, pre-commit secret scan |
| Dependency vulnerability | Known CVE in a Composer or npm package | `composer audit`, `npm audit` in Phase 6, lockfiles committed |
| Insider misuse | Staff export all candidates | Export permission, row limit, export audit |

## Authentication
- Sanctum SPA mode. The browser holds an HttpOnly session cookie. No bearer tokens are stored in the browser.
- Password hashing: Argon2id through `HASH_DRIVER=argon2id`. Confirmed in Phase 1 with `password_get_info()`.
- Minimum password length 12, using Laravel's `Password::min(12)` rule. No composition rules.
- Login failures return the same message for an unknown email and a wrong password.
- Login throttling through a named `RateLimiter`: 5 attempts per email and per IP per 15 minutes (values confirmed in Phase 2).
- Session regenerated on login. Session invalidated on logout.
- Changing a password, deactivating a membership, or suspending a company invalidates the affected sessions.
- `SESSION_DRIVER=database` so sessions can be listed and revoked.

## Authorisation
- Every route declares its middleware and policy. A Phase 2 test lists all routes (`php artisan route:list --json`) and fails if a route without a policy or an explicit public marker exists.
- Platform Super Admin is a flag on the user, not a company role. Platform actions are audited.
- There is no master password, no hidden support login, and no unrestricted support view of company data. Any future support access will be a separate, audited, time-limited feature with its own ADR.

## Input validation
- Every request goes through a Form Request. No controller reads `$request->all()` into a model.
- Maximum length on every text field. Pagination `per_page` capped at 100.
- Output uses API Resources, which list fields explicitly.

## HTTP hardening
- Middleware adds `Content-Security-Policy`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: DENY`, and `Strict-Transport-Security` in production.
- CORS is limited to `APP_URL` and the frontend origin in development.
- `APP_DEBUG=false` in production. Error responses use fixed codes and messages. Stack traces go to the log only.
- Request body size limited in the web server and by PHP `post_max_size`.

## Uploads and files
- Allowed types: PDF, JPEG, PNG. Checked by content (Laravel `mimes` rule), not by extension or browser type.
- Maximum 10 MB per file (configurable). Per-candidate limit set in Phase 4.
- Stored under a random UUID name on the private disk.
- Served only through the authorised controller, with `attachment` disposition and `nosniff`.
- Malware scanning: not in the MVP. Risk accepted and recorded (R-08). Decision on an optional scanner in Phase 6.

## Logging and audit
- Logs are structured. Redacted keys: `password`, `password_confirmation`, `cookie`, `authorization`, `token`, `session`, `set-cookie`, `APP_KEY`, `DB_PASSWORD`.
- `audit_logs` is append-only in the database: `smartthink_app` has `INSERT` and `SELECT` on it, and no `UPDATE` or `DELETE`. A migration test confirms this grant.
- Audit fields: actor user ID, company ID (nullable for platform), action, target type, target ID, result, IP address, timestamp, request ID.

## Rate limiting
- Login: as above.
- Invitation and password reset: per email, limits set in Phase 2.
- General API: per authenticated user, generous enough for normal use.

## Secrets
- `APP_KEY` generated with `php artisan key:generate` into `backend/.env`. Never printed in chat or logs.
- `DB_PASSWORD` for `smartthink_app` and `smartthink_migrator` are different and generated. Not reused from the superuser.
- Owner keeps passwords in a password manager.
- Rotating `APP_KEY` invalidates encrypted values and sessions. The procedure is in OPERATIONS.md.

## Checks before release
1. Tenant isolation suite (TENANT_ISOLATION.md) passes.
2. `composer audit` and `npm audit` reviewed. High and critical items fixed or recorded with reason.
3. Secret scan of the repository and of `git log`.
4. Route list reviewed: every route has a policy or is listed as public with a reason.
5. Upload test set rejects disguised and oversized files.
6. Production configuration check: `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, HTTPS only.

## Known limitations
- No third-party penetration test. Recommended before real client data is stored.
- No malware scanning in the MVP.
- Single server: no high availability.
- No results are claimed here. Real results are recorded in TESTING.md and PHASE_STATUS.md.