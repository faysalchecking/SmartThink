# Operations runbook (plan)

Status: PLANNED. Procedures are written here so they can be tested in Phase 6 and 7. Steps marked TEST are not valid until the test is logged.

## Daily
- Check that the last database backup completed (TEST: backup job log, from Phase 6).
- Check the API service is running (TEST: systemd status, from Phase 7).

## Weekly
- Review security logs for repeated login failures or 403 spikes.
- Check disk space for the database and the file storage directory.

## Monthly
- Run `npm audit` and review the results. Fix high and critical items or record the reason.
- Confirm off-site backup copies are readable (a checksum check, not just a file count).

## Incidents
| Situation | First action |
|---|---|
| Suspected data leak between companies | Suspend the affected company or the user. Preserve logs. Do not delete audit data. Investigate using audit_logs. |
| Leaked password or token | Revoke the session or rotate the secret. Force a password reset for affected users. |
| `SESSION_SECRET` exposed | Generate a new secret, restart the service, which invalidates existing sessions. Record in the log. |
| Database corruption | Stop the API. Restore from the latest verified backup (BACKUP_RESTORE.md). |
| Disk full | Stop uploads. Free space only after a backup is verified. Never delete audit data to free space. |

## Secret rotation
1. Generate a new value locally.
2. Update the server's environment file with restricted permissions.
3. Restart the service.
4. Record the date in CHANGELOG.md without the value.

## Access to production
- Only the owner has server access.
- Use the owner's own admin account. No shared login.
- Any support access to company data is out of scope. There is no hidden or master access (SECURITY.md).

## Logs
- Application logs are structured JSON, with secrets redacted.
- Retention for logs: planned 30 days (confirmed in Phase 6).