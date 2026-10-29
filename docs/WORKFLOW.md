# Workflows

Each workflow lists its steps, the permission used, and the audit event. These are the acceptance scripts for the end-to-end tests.

## W1: Create a company (Super Admin)
1. Super Admin logs in.
2. Creates a company with name and slug. Status: pending.
3. Invites the first Company Admin by email.
4. Company Admin accepts the invitation and sets a password.
5. Super Admin activates the company. Status: active.
Audit: company.created, company.activated, invitation.created, invitation.accepted.
Rule: A pending or suspended company returns 403 to its members.

## W2: Invite and manage a team member (Company Admin)
1. Company Admin invites an email with a role.
2. Invitation is single-use and expires after a set time (value fixed in Phase 2).
3. Invitee accepts and gets an active membership.
4. Company Admin can change the role or deactivate the member.
5. Deactivation ends all of that member's sessions for this company.
Audit: invitation.created, invitation.accepted, membership.role_changed, membership.deactivated.

## W3: Recruit a candidate for an overseas job
1. Employee creates a candidate (name, contact, nationality, passport number masked in lists).
2. Employee selects a client and creates a job order, status open.
3. Employee attaches the candidate to the job. Stage: first enabled stage (for example Screening).
4. Each move to a new stage is recorded in stage history with the actor and an optional note.
5. Optional stages (Passport check, Medical, Visa, Embassy, Deployment) are shown only if enabled for the company.
6. Candidate can be archived. Archived records are hidden by default and never deleted.
Audit: candidate.created, application.created, application.stage_changed, candidate.archived.

## W4: Upload and review a document
1. Employee uploads a file to a candidate and chooses a document type.
2. Server checks size, extension allowlist, and file signature. Rejected files are not stored.
3. File is saved under a random name. Document status: pending.
4. Reviewer with documents:review marks it verified or rejected, with a reason for rejection.
5. Download is allowed only through the authorised endpoint and only with documents:download.
Audit: document.uploaded, document.reviewed, document.downloaded, document.deleted (soft).

## W5: Build and export a CV
1. Employee opens the candidate's CV builder and chooses a template.
2. System fills the CV only from verified fields. Missing fields are shown as "missing", never invented.
3. Employee reorders sections, hides optional sections, and edits text. Candidate master record is not changed unless the employee chooses to update it.
4. Employee switches template. Same data, new layout.
5. Preview is shown. Export button stays disabled until a reviewer confirms the draft.
6. Export creates a private PDF. Download uses the authorised endpoint.
Audit: cv.draft_created, cv.edited, cv.reviewed, cv.exported.

## W6: Export candidate list (CSV)
1. User with export:candidates requests an export, with filters.
2. Server applies company scope and the permitted field list. Maximum rows per export is enforced.
3. File is generated and downloaded. Passport numbers are not included unless a separate permission is granted (decision in Phase 5).
Audit: export.created with filter summary and row count. No personal data in the audit metadata.

## W7: Platform deactivation of a company
1. Super Admin suspends a company.
2. All company routes return 403 for its members from the next request.
3. Data is kept. Reactivation restores access.
Audit: company.suspended, company.reactivated.

## Rules common to all workflows
- Every step is checked on the server, not only in the UI.
- A failed step leaves no partial data. Multi-step writes use database transactions.
- Confirmation is required before deactivation, deletion, and export.