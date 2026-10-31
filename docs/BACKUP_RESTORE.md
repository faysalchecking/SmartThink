# Backup and restore (plan, not yet verified)

Status: NOT WORKING YET. Nothing here is claimed to work until a restore test passes and its log is recorded below.

## What is backed up
1. PostgreSQL dump of the production database (`smartthink_prod`), using `pg_dump --format=custom`. The backup uses a dedicated read-only backup role, not `smartthink_app` and not the superuser.
2. Private file directory `backend/storage/app/private`, archived with a SHA-256 manifest.
3. `backend/.env.example` and `frontend/.env.example` (templates only). The real `.env` is kept by the owner in a password manager and never placed in Git or in the backup folder shared with others.

## Schedule (planned)
- Daily database dump, kept 14 days.
- Weekly full copy (database and files), kept 8 weeks.
- Off-site copy: encrypted copy to a second location, chosen in Phase 7. Provider cost to be verified.

## Encryption
Backup files are encrypted before they leave the server. Method chosen in Phase 6 and recorded in an ADR. Keys are not stored next to the backups.

## Restore procedure (to be written and tested in Phase 6)
1. Stop PHP-FPM and the queue worker.
2. Create an empty database `smartthink_restore_test`.
3. Restore with `pg_restore --clean --if-exists --dbname=smartthink_restore_test`.
4. Restore the file directory to a test path and compare checksums with the manifest.
5. Point a test copy of `.env` at the restored database, run `php artisan migrate:status` and the smoke test.
6. Record date, backup file name, row counts, checksum result, and pass or fail below.

## Restore test log
| Date | Backup | Result | Notes |
|---|---|---|---|
| (not yet run) | | | Required before Phase 6 is marked done |