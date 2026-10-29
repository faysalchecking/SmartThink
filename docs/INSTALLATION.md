# Installation (developer machine, Windows 11)

Status: PLANNED. These steps are verified in Phase 1 on a clean clone. Until then, treat each step as untested.

## Verified in Phase 0 (owner's machine, 2026-10-28)
- Windows 11 Home, 64-bit
- Node.js v24.21.0, npm 11.19.0
- Git 2.55.0
- PostgreSQL 18.6, service `postgresql-x64-18` running, port 5432 reachable
- Database `smartthink_dev` and role `smartthink_app` (no superuser, no BYPASSRLS) created
- `smartthink_app` cannot create tables in schema `public`. Migrations are run by the owner role (decision D-10).

## Steps (Phase 1 will turn these into one setup script)
1. Clone the repository:
   `git clone https://github.com/faysalchecking/SmartThink.git D:\SmartThink`
2. Create `.env` from `.env.example`. Never commit it. Fill in:
   - `DATABASE_URL` with the `smartthink_app` password
   - `SESSION_SECRET` with a freshly generated 64-character hex value
   - `FILE_STORAGE_PATH` (default `./storage`)
3. Install dependencies with the committed lockfile: `npm ci`
4. Run migrations: `npm run migrate:up` (Phase 1, owner role)
5. Start the API and web app: `npm run dev`
6. Open `http://localhost:5173`.

## Checks
- `npm run check` must pass on a clean clone before any phase is marked complete.
- `git check-ignore -v .env` must show a match.

## Known limitations
- Windows line endings: Git warnings about CRLF/LF are expected. `.gitattributes` normalises text files.
- Node 24 is the target. Other versions are not supported until tested.
- Docker is not required for development.