# ADR-0004: Private file storage

Status: Proposed, to be accepted in Phase 4.
Date: 2026-10-29

## Decision
- Candidate files are stored on a Laravel filesystem disk named `private`, rooted at `backend/storage/app/private`. This directory is outside `backend/public/`, so the web server never serves it.
- Each file gets a random UUID name. The original file name is kept only in the database and is sanitised before it is used in any `Content-Disposition` header.
- Files are downloaded only through a controller that checks the document's company, the membership, and the `documents:download` permission, then streams the file with `Content-Disposition: attachment` and `X-Content-Type-Options: nosniff`.
- No signed public URLs in the MVP.
- A future S3-compatible disk is added as another named disk in `config/filesystems.php`. Business code uses only the disk name.

## Upload validation
- Size limit from configuration, default 10 MB.
- Allowed types: PDF, JPEG, PNG. Laravel's `mimes` rule checks the file content, not only the extension. The Phase 4 test set also includes disguised files.
- The stored file's SHA-256 is saved so later corruption can be detected.

## Risks
- Local disk is a single point of failure. Mitigated by backups (BACKUP_RESTORE.md).
- Path traversal: the application never builds a path from user input. Covered by a Phase 4 test with `../` names.