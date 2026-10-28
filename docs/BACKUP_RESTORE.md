# Backup and restore (plan, not yet verified)

Status: NOT WORKING YET. Nothing in this document is claimed as working until a restore test passes and its log is recorded below.

## What is backed up
1. PostgreSQL logical dump of `smartthink_dev` (dev) or `smartthink_prod` (server), using `pg_dump --format=custom`.
2. Private file storage directory (`FILE_STORAGE_PATH`), archived with checksums.
3. Configuration template only (`.env.example`). Real `.env` is backed up separately by the owner in a password manager or secure vault, never in Git.

## Schedule (planned)
- Daily database dump, kept 14 days.
- Weekly full copy (database and files), kept 8 weeks.
- Off-site copy: encrypted copy to a second location (for example a different drive or a provider chosen in Phase 7). Provider cost to be verified.

## Encryption
Backup files are encrypted before leaving the server (method chosen in Phase 6, recorded in ADR). Encryption keys are not stored next to the backups.

## Restore procedure (to be written and tested in Phase 6)
1. Stop the application.
2. Create an empty database `smartthink_restore_test`.
3. Restore with `pg_restore --clean --if-exists --dbname=smartthink_restore_test`.
4. Restore the file directory to a test path and compare checksums with the manifest.
5. Start the application against the test copy and run the smoke test.
6. Record date, backup file name, row counts, checksum result, and pass/fail below.

## Restore test log
| Date | Backup | Result | Notes |
|---|---|---|---|
| (not yet run) | | | Required before Phase 6 is marked done |