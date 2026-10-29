# API specification (planned)

Status: PLANNED. No endpoint exists yet. Each endpoint becomes "implemented" only after its tests pass, and PHASE_STATUS.md records it.

Base path: `/api/v1`. JSON only (except file upload and download). Errors use one shape:
`{ "error": { "code": "NOT_FOUND", "message": "Resource not found" } }`

## Conventions
- Company-scoped routes use `/api/v1/companies/:companyId/...`. The `companyId` in the path only selects a membership. The server checks membership and permission before any data is read (see TENANT_ISOLATION.md).
- Cross-company access returns 404, not 403.
- Lists use `?page=1&pageSize=25` (pageSize max 100) and `?q=` for search.
- IDs are UUIDs. Dates are ISO 8601 UTC.
- Every state-changing request needs a valid session cookie and passes the Origin check (CSRF control).

## Platform (Super Admin only)
| Method | Path | Permission | Phase |
|---|---|---|---|
| POST | /auth/login | public, rate-limited | 2 |
| POST | /auth/logout | session | 2 |
| GET | /auth/me | session | 2 |
| POST | /platform/companies | platform:admin | 2 |
| GET | /platform/companies | platform:admin | 2 |
| PATCH | /platform/companies/:id/status | platform:admin | 2 |

## Company administration
| Method | Path | Permission | Phase |
|---|---|---|---|
| GET | /companies/:companyId | member | 2 |
| PATCH | /companies/:companyId | company:settings | 2 |
| GET | /companies/:companyId/members | members:read | 2 |
| POST | /companies/:companyId/invitations | members:invite | 2 |
| POST | /invitations/accept | public, token | 2 |
| PATCH | /companies/:companyId/members/:membershipId | members:manage | 2 |
| GET | /companies/:companyId/roles | roles:read | 2 |
| PUT | /companies/:companyId/roles/:roleId/permissions | roles:manage | 2 |
| GET | /companies/:companyId/audit-logs | audit:read | 2 |

## Recruitment
| Method | Path | Permission | Phase |
|---|---|---|---|
| GET/POST | /companies/:companyId/clients | clients:read / clients:write | 3 |
| GET/PATCH | /companies/:companyId/clients/:id | clients:read / clients:write | 3 |
| GET/POST | /companies/:companyId/jobs | jobs:read / jobs:write | 3 |
| PATCH | /companies/:companyId/jobs/:id/status | jobs:write | 3 |
| GET/POST | /companies/:companyId/candidates | candidates:read / candidates:write | 3 |
| GET/PATCH | /companies/:companyId/candidates/:id | candidates:read / candidates:write | 3 |
| POST | /companies/:companyId/candidates/:id/archive | candidates:write | 3 |
| GET/POST | /companies/:companyId/pipeline-stages | pipeline:manage (write) | 3 |
| POST | /companies/:companyId/applications | candidates:write | 3 |
| PATCH | /companies/:companyId/applications/:id/stage | pipeline:move | 3 |
| GET | /companies/:companyId/applications/:id/history | candidates:read | 3 |
| GET/POST | /companies/:companyId/interviews | interviews:* | 3 |
| GET | /companies/:companyId/dashboard | dashboard:read | 3 |

## Documents and CV
| Method | Path | Permission | Phase |
|---|---|---|---|
| POST | /companies/:companyId/candidates/:id/documents | documents:upload | 4 |
| GET | /companies/:companyId/candidates/:id/documents | documents:read | 4 |
| GET | /companies/:companyId/documents/:docId/download | documents:download | 4 |
| PATCH | /companies/:companyId/documents/:docId/review | documents:review | 4 |
| DELETE | /companies/:companyId/documents/:docId | documents:delete (soft) | 4 |
| GET/POST | /companies/:companyId/candidates/:id/cv-drafts | cv:write / cv:read | 4 |
| PATCH | /companies/:companyId/cv-drafts/:id | cv:write | 4 |
| POST | /companies/:companyId/cv-drafts/:id/review | cv:review | 4 |
| POST | /companies/:companyId/cv-drafts/:id/export | cv:export (requires review) | 4 |
| GET | /companies/:companyId/cv-exports/:id/download | cv:export | 4 |

## Export and reports
| Method | Path | Permission | Phase |
|---|---|---|---|
| POST | /companies/:companyId/exports/candidates.csv | export:candidates | 5 |
| GET | /companies/:companyId/reports/pipeline | reports:read | 5 |

## Rules for every endpoint (checked by a route-registry test)
1. Declares a permission, or is listed as public with a reason.
2. Validates params, query, and body with zod before the service layer.
3. Returns DTOs without password hashes, token hashes, storage keys, or internal IDs.
4. Writes an audit row for privileged or data-changing actions.