# Product vision

## Problem
Small and mid-size recruitment agencies, including overseas manpower agencies, track candidates in spreadsheets, WhatsApp, and paper files. This causes lost documents, slow CV preparation, unclear status for each candidate, and no audit trail when staff change.

## Product
SmartThink is a multi-tenant web application. One deployment serves many recruitment companies. Each company has its own isolated data, users, roles, and settings.

## Target users
1. Platform Super Admin: operates the SmartThink installation, creates and activates companies. Does not read company candidate data by default.
2. Company Admin: manages one company's users, roles, settings, and pipeline stages.
3. Company Employee / Sub-admin: works on candidates, jobs, documents, and CVs within permissions granted by the Company Admin.

## Core value (MVP)
- Keep every candidate, job order, interview, and document in one secure place.
- Move candidates through a configurable recruitment pipeline.
- Generate a professional, print-ready CV from verified data, after human review.
- Know who changed what and when.

## Principles
1. Security and tenant isolation come before features.
2. Verified data only. The system never invents education, experience, skills, or references.
3. Configurable process, not one fixed process. Overseas stages (medical, visa, embassy, deployment) are optional per company.
4. Deterministic core. AI is optional, off by default, and never required.
5. Low running cost. Prefer one server and open-source components.

## Out of scope for MVP
- Native mobile apps
- Payment and subscription billing
- Public job board or candidate self-service portal
- AI CV writing or CV parsing (optional, later)
- Bangla UI (architecture ready, translation later)
- Email/SMS integrations beyond a simple optional notification (decided in Phase 5)

## Success criteria for the 30-day MVP
- Two test companies can be created and used simultaneously, with zero cross-company access in automated tests.
- A candidate can be created, attached to a job, moved through stages, given a document, and exported as a CV PDF.
- Every privileged action appears in the audit log.
- Backup and restore are tested end to end, not assumed.