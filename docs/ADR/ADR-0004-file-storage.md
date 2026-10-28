# ADR-0004: Private file storage

Status: Proposed
Date: 2026-10-28

## Decision
- A `StorageProvider` interface with methods: `put(stream, meta) -> storageKey`, `get(storageKey) -> stream`, `delete(storageKey)`, `exists(storageKey)`.
- MVP implementation: `LocalStorageProvider` writing to `FILE_STORAGE_PATH`, which is outside `apps/` and outside any static web root.
- Storage keys are random UUIDs. Original file names are stored only in the database and sent in `Content-Disposition` after sanitising.
- Downloads go through the authorised API. No signed public URLs in MVP.
- A future `S3StorageProvider` replaces only the provider, not business code.

## Reasons
- Zero recurring cost for a single server.
- The interface keeps the S3 migration path open.

## Risks
- Local disk is a single point of failure. Mitigated by backups (BACKUP_RESTORE.md).
- Path traversal: the provider never builds paths from user input. Tested in Phase 4.