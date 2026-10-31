# Operations runbook (plan)

Status: PLANNED. Steps marked TEST are valid only after the related test is logged.

## Processes that must run
| Process | Purpose | Development | Production |
|---|---|---|---|
| `php artisan serve` or PHP-FPM | Serves the API | Terminal | PHP-FPM service |
| `npm run dev` or static files | Serves the React app | Terminal | Reverse proxy |
| `php artisan queue:work` | Runs queued jobs | Terminal (Phase 4+) | systemd service |
| `php artisan schedule:run` | Scheduled tasks | Task Scheduler, every minute | cron, every minute |

## Daily
- Check that the last database backup completed. (TEST: backup log, Phase 6)
- Check that the queue worker is running and the failed-jobs count is zero or explained.

## Weekly
- Review login-failure and 403/404 spikes in the log.
- Check disk space for the database and the private file directory.

## Monthly
- Run `composer audit` and `npm audit`. Fix high and critical items or record the reason.
- Confirm off-site backup copies can be read (checksum check, not only a file count).

## Incidents
| Situation | First action |
|---|---|
| Suspected data leak between companies | Suspend the affected company or user. Keep logs and `audit_logs`. Do not delete any data. Investigate from the audit log. |
| Leaked password or token | Revoke sessions, force a password reset for affected users. |
| `APP_KEY` exposed | See the rotation procedure below. Plan it; it logs everyone out. |
| Database corruption | Stop PHP-FPM and the queue. Restore from the latest verified backup. |
| Disk full | Stop uploads. Free space only after a verified backup. Never delete audit data. |

## Secret rotation
1. Database password: change it in PostgreSQL with `ALTER ROLE`, update `backend/.env`, restart PHP-FPM and the queue worker, record the date.
2. `APP_KEY`: rotation makes existing encrypted values and sessions unreadable. Schedule a maintenance window, announce it, generate a new key, restart, and record the date in CHANGELOG.md without the value.
3. Never paste the value into chat, a ticket, or a commit message.

## Access
- Only the owner has server access, through the owner's own account.
- No shared logins. No support access to company data (SECURITY.md).

## Logs
- Application logs are structured. Redaction rules are in SECURITY.md.
- Log retention: planned 30 days, confirmed in Phase 6.