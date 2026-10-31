# Deployment (plan)

Status: PLANNED for Phase 7. Nothing here is deployed or tested.

## Target
One Linux server (Ubuntu LTS, version confirmed in Phase 7) running:
- PHP-FPM with the Laravel application
- PostgreSQL 18 on the same server (MVP)
- Reverse proxy with HTTPS (Caddy or Nginx, chosen in Phase 7 with an ADR)
- Built React files from `frontend/dist`, served by the reverse proxy
- Queue worker as a systemd service
- Scheduler through cron running `php artisan schedule:run` every minute
- Private file directory `backend/storage/app/private`, readable only by the application user

## Build steps (production)
1. `composer install --no-dev --optimize-autoloader`
2. `php artisan config:cache`, `route:cache`, `view:cache`
3. `php artisan migrate --database=pgsql_migrator --force`
4. In `frontend`: `npm ci` then `npm run build`
5. Restart PHP-FPM and the queue worker.

## Security settings
- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`
- Only ports 80 and 443 open to the internet. PostgreSQL listens on localhost only.
- `backend/.env` readable only by the service user.
- SSH key login only. Password login disabled.
- Automatic security updates enabled for the operating system.

## Cost model
One-time: developer time. No licence fees for the chosen open-source stack.

Recurring (categories only; prices are NOT stated because they were not verified for the owner's region and date):
- Domain name (yearly)
- Virtual server (monthly or yearly)
- Off-site backup storage (monthly, by size)
- Transactional email, if invitations need it (monthly, by volume)
- Optional AI service: zero, not part of the MVP

Check current prices directly with providers before any budget is stated. Self-hosting on one server is cheapest but the owner handles updates and backups. Managed PostgreSQL and object storage cost more and reduce operational work. The choice is recorded in an ADR in Phase 7.

## Release checklist (Phase 7)
1. All phase acceptance criteria met and logged in PHASE_STATUS.md.
2. Clean install on a second machine or VM, steps recorded.
3. Backup and restore test passes and is logged.
4. SECURITY.md checks complete, with results.
5. Release notes in CHANGELOG.md.