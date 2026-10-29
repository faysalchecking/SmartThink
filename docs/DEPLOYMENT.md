# Deployment (plan)

Status: PLANNED for Phase 7. Nothing here is deployed or tested yet.

## Target
One Linux server (Ubuntu LTS, version confirmed at Phase 7) running:
- Node.js 24 LTS, the API process, which also serves the built React files
- PostgreSQL 18, on the same server for the MVP
- Reverse proxy with HTTPS (Caddy or Nginx, chosen in Phase 7 with an ADR)
- A private directory for uploaded files, owned by the app user, not readable by others
- systemd service for the API, and a daily backup job (see BACKUP_RESTORE.md)

## Environment separation
- `smartthink_dev`: development on the owner's PC
- `smartthink_test`: automated tests (Phase 1 approval needed)
- `smartthink_prod`: production server only. Never copied to a development PC except through a tested restore

## Security settings for production
- `NODE_ENV=production`, so the session cookie is `Secure`
- Only ports 80/443 open to the internet. PostgreSQL listens on localhost only
- `.env` readable only by the service user (file permissions set during setup)
- Automatic security updates for the operating system enabled
- SSH key login only, password login disabled

## Cost model
One-time costs: developer time. No licence fees for the planned open-source stack.

Recurring costs (categories only. Exact prices are NOT stated because they were not verified for the owner's region and date):
- Domain name (yearly)
- Virtual server (monthly or yearly)
- Off-site backup storage (monthly, by size)
- Transactional email, if invitations need it (monthly, by volume)
- Optional AI service: zero, because it is not part of MVP

Before any budget is stated, the owner should check current prices directly with providers. Self-hosting on one server is cheapest but needs the owner to handle updates and backups. Managed databases and managed object storage cost more but reduce operational work. This choice is recorded in ADR-0005 at Phase 7.

## Release checklist (Phase 7)
1. All phase acceptance criteria are met and logged in PHASE_STATUS.md.
2. Clean install on a second machine or VM succeeds, with the steps recorded.
3. Backup and restore test passes and is logged.
4. Security checklist in SECURITY.md is complete, with results.
5. Release notes written in CHANGELOG.md.