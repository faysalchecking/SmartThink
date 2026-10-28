# Requirements

Priority: M = Must (MVP), S = Should (MVP if time allows), C = Could (after MVP).
Each requirement has an ID used in tests, PHASE_STATUS, and the CHANGELOG.

## 1. Platform administration
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| PA-01 | Super Admin can create a company record | M | Company appears in list with status "pending" |
| PA-02 | Super Admin can activate or suspend a company | M | Suspended company's users get 403 on all company routes |
| PA-03 | Super Admin actions are audited | M | Each action writes an audit row with actor, time, target |
| PA-04 | Super Admin cannot view candidate data unless a company member | M | Platform routes do not expose candidate tables (tested) |

## 2. Authentication and sessions
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| AU-01 | Login with email and password | M | Correct credentials create a session; wrong ones fail with generic message |
| AU-02 | Passwords hashed with Argon2id | M | Database stores hash only; unit test verifies algorithm prefix |
| AU-03 | Server-side sessions, HttpOnly cookie | M | Session token not readable by JavaScript; logout invalidates it server-side |
| AU-04 | Login rate limiting | M | After repeated failures, further attempts return 429 for a set window |
| AU-05 | Password reset by email token | S | Token is single-use, expires, and is stored hashed |
| AU-06 | Invitation acceptance sets password | M | Invitation token is single-use and expires |

## 3. Companies, users, roles
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| CO-01 | Company Admin edits company settings | M | Change is audited |
| CO-02 | Company Admin invites users by email | M | Invitation creates a pending membership only |
| CO-03 | Company Admin deactivates a member | M | Deactivated member loses access on next request |
| CO-04 | Roles: Company Admin, Employee/Sub-admin with permission set | M | Permission checks enforced on server (see TENANT_ISOLATION.md) |
| CO-05 | A user can belong to several companies with different roles | M | Switching company changes all data scope |

## 4. Recruitment core
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| CA-01 | Create, view, edit, archive candidates | M | Archived candidates hidden by default, not deleted |
| CA-02 | Candidate personal and contact fields | M | Validation on email, phone, date formats |
| CL-01 | Create and manage clients (employers) | M | Clients scoped to company |
| JO-01 | Create job orders linked to a client | M | Job requires client and title |
| JO-02 | Job status: draft, open, on hold, closed | M | Status transitions validated server-side |
| PL-01 | Candidate attached to a job with a pipeline stage | M | Stage change recorded in history |
| PL-02 | Company-configurable stages (including overseas stages) | M | Stage list per company; stages can be enabled/disabled, not hard-deleted if used |
| IN-01 | Schedule and record interviews | S | Interview linked to candidate and job |
| AC-01 | Activity history per candidate | M | Shows stage changes, document uploads, and edits |
| SE-01 | Search, filter, and paginate candidates | M | Server-side pagination, max page size enforced |
| DA-01 | Dashboard counts per company | S | Counts computed only from the caller's company |

## 5. Documents
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| DO-01 | Upload documents to a candidate | M | Size and type checked; rejected files not stored |
| DO-02 | Document types, including passport and medical records | M | Type from configurable list |
| DO-03 | Document review status (pending, verified, rejected) | M | Status change audited |
| DO-04 | Download only through authorised API | M | No public URLs; cross-company download returns 404 |
| DO-05 | Delete is soft delete with audit | M | File bytes retained per retention rule until purge |

## 6. CV Builder
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| CV-01 | Build CV from verified candidate fields | M | Unverified fields are shown as missing, not filled in |
| CV-02 | Edit CV content and reorder sections | M | Edits do not change the candidate master record unless chosen |
| CV-03 | Optional sections can be hidden | M | Hidden sections absent from preview and PDF |
| CV-04 | Switch template without losing data | M | Same data renders in each template |
| CV-05 | At least 3 professional templates | M | Each passes visual check on A4 and Letter |
| CV-06 | Long text, missing fields, special characters handled | M | Fixture tests pass; no clipped text |
| CV-07 | Review step before final PDF export | M | Export blocked until reviewer confirms |
| CV-08 | PDF export with page-break control | M | No heading orphaned at page bottom (checked in test) |
| CV-09 | Template system documented for later customisation | S | docs/CV_TEMPLATES.md describes the contract |
| CV-10 | Optional AI writing assistance | C | Disabled by default; never required |

## 7. Export and reports
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| EX-01 | Export candidate list as CSV | M | Only permitted fields; exported rows scoped to company |
| EX-02 | Export requires explicit permission and is audited | M | Export without permission returns 403 and no file |
| RE-01 | Basic pipeline report | S | Counts by stage and job |

## 8. Audit and activity
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| AL-01 | Audit log for auth, role change, company change, export, document access | M | Append-only in application; tested |
| AL-02 | Audit log viewable by Company Admin (own company) and Super Admin (platform) | S | Company Admin cannot see another company's log |

## 9. Non-functional
| ID | Requirement | Priority | Acceptance criterion |
|---|---|---|---|
| NF-01 | Responsive UI, keyboard accessible forms, labelled inputs | M | Axe check in E2E test with no critical violations |
| NF-02 | Loading, empty, and error states on every data screen | M | Checked in component tests |
| NF-03 | Confirmation before destructive action | M | Delete and deactivate require confirm dialog |
| NF-04 | English UI with i18n-ready strings | M | No hard-coded UI text outside the translation file |
| NF-05 | Reproducible setup on Windows 11 | M | Clean install script passes on a fresh clone |
| NF-06 | Automated backup and tested restore | M | Restore test log recorded in BACKUP_RESTORE.md |

## Out of scope for MVP
Listed in PRODUCT_VISION.md. Anything not in this file is not promised.