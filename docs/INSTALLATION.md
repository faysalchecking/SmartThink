# Installation (developer machine, Windows 11)

Status: PLANNED. Phase 1 turns these steps into `scripts\setup.ps1` and verifies them on a clean clone. Until then, each step is untested.

## Verified so far (owner's PC, 2026-10-28)
- Windows 11 Home, 64-bit
- Node.js 24.21.0, npm 11.19.0
- Git 2.55.0
- PostgreSQL 18.6, service `postgresql-x64-18`, running, port 5432 reachable
- `smartthink_dev`, `smartthink_test` databases; `smartthink_app` and `smartthink_migrator` roles (neither superuser nor BYPASSRLS)

## Not yet verified
- PHP version and extensions (audit commands in ADR-0001)
- Composer version
- Laravel version and its PHP requirement

## Step 1: Audit (read-only)
```powershell
php -v
composer -V
php -m
php -r "var_dump(defined('PASSWORD_ARGON2ID'));"
```
Stop if any command is missing. Send the output. Installing PHP or Composer requires the owner's approval, and the method will be proposed then (official Windows builds or a package manager, checked against the official source).

## Step 2: PHP settings (after approval)
In the PHP `php.ini`, enable these extensions: `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `curl`. Restart the terminal and repeat `php -m`.

## Step 3: Clone
```powershell
git clone https://github.com/faysalchecking/SmartThink.git D:\SmartThink
```
If the folder already exists with the repository, use `git pull` instead.

## Step 4: Backend
```powershell
cd D:\SmartThink\backend
copy .env.example .env
composer install
php artisan key:generate
```
Edit `backend\.env` in VS Code and set the database values (`DB_USERNAME`, `DB_PASSWORD`). Do not paste the file's contents anywhere.

Migrations (uses the migrator connection):
```powershell
php artisan migrate --database=pgsql_migrator
```

## Step 5: Frontend
```powershell
cd D:\SmartThink\frontend
copy .env.example .env
npm ci
```

## Step 6: Run in development
Two terminals:
```powershell
cd D:\SmartThink\backend
php artisan serve --host=127.0.0.1 --port=8000
```
```powershell
cd D:\SmartThink\frontend
npm run dev
```
Open `http://localhost:5173`. The Vite dev server forwards `/api` and `/sanctum` to port 8000.

Queue worker (when jobs exist, from Phase 4):
```powershell
php artisan queue:work --tries=3
```

## Step 7: Checks
```powershell
cd D:\SmartThink
scripts\check.ps1
```
This must pass on a clean clone before any phase is marked complete.

## Known limitations
- Git may show CRLF/LF warnings. `.gitattributes` normalises text files.
- Node.js 24 and PHP are the tested targets. Other versions are not supported until tested.
- Docker is not required.