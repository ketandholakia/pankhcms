# PankhCMS Release Notes — v1.0.0-rc1 (release candidate)

This is a release candidate. It has not been tagged or published; the
version label is proposed and may change before the actual tag.

## Highlights

- **Automatic recovery from failed database restores.** Every restore
  takes a verified recovery backup of the live database first. If the
  restore fails, the database is returned to its pre-restore state
  automatically; if recovery itself fails, maintenance mode is forced on
  and an operator-instructions file is written to `storage/backups/`.
- **Hardened backup/restore pipeline.** Allowlist HTML sanitization for
  all page and block content, safe script-context encoding for block
  JSON in the editor, SQLite snapshots via `VACUUM INTO` with a
  `PRAGMA integrity_check` before any file is replaced, ZIP entry
  validation (traversal, absolute paths, duplicates, size caps), and a
  generic 500 page with details only when `APP_DEBUG=true`.
- **Linux CI.** GitHub Actions workflow (ubuntu-latest, PHP 8.2,
  MariaDB 11.8 service) runs the full suite including the MySQL
  recovery failure-path tests. Verified locally in equivalent Linux
  containers: 96 tests / 368 assertions / 0 skipped.

## Security fixes included

- Plugin uninstall path traversal (slug allowlist + realpath containment).
- `eval()` removed from global block rendering (placeholder whitelist +
  sanitization instead of Blade compilation).
- Missing permission checks: `manage_backups`, `manage_messages`, and
  draft preview (`?preview=1` now requires `manage_pages`).
- Sanitization on save for page and global-block HTML; script-context
  XSS fix in the page editor.
- Plugin ZIP upload validation (kept disabled unless
  `ENABLE_PLUGIN_UPLOADS` is set).
- Installer hardening: admin credentials are never written to `.env`,
  password policy enforced, PHP 8.2 requirement, install lock untracked.
- Trusted-proxy client IP resolution (`TRUSTED_PROXIES`, rightmost
  untrusted address) and per-account login throttling that survives IP
  rotation.
- Security headers (nosniff, SAMEORIGIN, Referrer-Policy, admin CSP,
  HSTS on HTTPS) and centralized error logging to `storage/logs/`.
- Sessions bound to the password hash: password changes and resets
  invalidate other sessions. Deleted-user sessions are rejected.
- Password-reset links use `APP_URL` only; CR/LF stripped from mail
  headers.
- Contact form: working math captcha, honeypot, per-IP rate limiting.
- Canonical role seeding (Administrator/Editor/Author/Viewer) shared by
  installer and migrations; lockout guards for the last admin and the
  last `manage_users` role.
- Unsigned foreign keys in the initial schema (fixes `errno 150` on
  MySQL/MariaDB fresh installs).

## Fixes and improvements

- Contact form actually works (captcha token was never generated).
- `POST /admin/pages/@id/delete` route added; dead category URLs
  removed from the sitemap.
- Full-page cache rewritten for Flight v3 with invalidation hooks on
  content changes; anonymous visitors stay session-less.
- BackupManager rewritten: storage exclusions, `VACUUM INTO` snapshots,
  PDO-based MySQL dumps (no credentials on command lines).
- Vendored TinyMCE updated to 8.9.3 (`license_key: 'gpl'`).
- Theme assets copied into `public/themes` on activation; MIME types by
  extension; the PHP route remains a fallback.
- API: pagination (`per_page` capped at 100), optional token expiry,
  throttled `last_used_at` writes.
- Schema consolidated: `database/migrations` is the single source of
  truth; the installer runs `scripts/migrate.php`.
- Added MIT `LICENSE`, `composer test` script, phpunit pinned to ^11.5
  (PHP 8.2 compatible) with `config.platform.php=8.2.0`.
- Removed committed debris (debug scripts, stray files, hardcoded
  paths) and merged `app/helpers.php` into `app/Helpers/functions.php`.

## Compatibility

- **PHP 8.2+** (tested up to 8.4). Required extensions: `pdo_sqlite` or
  `pdo_mysql`, `zip`, `fileinfo`, `mbstring`, `dom`, `openssl`.
- Databases: SQLite (file-backed; `:memory:` for tests) and
  MySQL/MariaDB (verified against MariaDB 11.8).
- FlightPHP 3, Eloquent 11, Blade — see `composer.json`.

## Backup & restore behavior

- Backups are ZIP archives in `storage/backups/`; full backups contain
  the database, `public/uploads/` and `themes/`; `storage/backups`,
  `storage/cache` and `storage/logs` are never included.
- Restore is **database-only**, requires the `manage_backups`
  permission, CSRF token, and an explicit confirmation checkbox.
- A recovery backup is taken automatically before every restore; a
  failed restore automatically reverts the database to its pre-restore
  state. Maintenance mode is enabled during the restore and restored to
  its previous state afterwards.
- There are **no default credentials**; the administrator account is
  created during installation.

## Known limitations

- MySQL restore is not atomic: a dump failing mid-import leaves partial
  state until the automatic recovery completes. If recovery also fails,
  follow `storage/backups/RESTORE-FAILED-*.txt`.
- Backup archives are retained until deleted manually — prune them in
  Admin > Backups.
- Restores cover the database only; uploaded files and themes must be
  restored from full backups manually.
- Only restore archives produced by this system; the import executes
  SQL from the archive by design.
- GitHub-hosted CI results pending the first push (workflow committed;
  job verified in local Linux containers).

## Upgrade instructions

See "Upgrading an Existing Installation" in `README.md`. In short:
back up, replace application files (keep `.env`, `storage/`,
`public/uploads/`, `themes/`, `plugins/`), run `php scripts/migrate.php`,
clear `storage/cache` if needed, and verify the site and admin panel.
