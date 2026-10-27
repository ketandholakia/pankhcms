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

## Fixes (post-RC1 review batch)

- **Fresh-install schema corrections.** `media` and `block_placements`
  now match the columns the code actually uses (the old definitions
  500'd every media-library and public page render on new installs).
- **Admin views restored.** `.gitignore` directory rules are anchored to
  the repo root (the bare `media/` rule had swallowed the TinyMCE media
  plugin and would have swallowed `views/admin/media/`); the missing
  media index/picker and admin 404/403 views now exist.
- **Security headers on cached pages.** The pre-bootstrap page cache now
  sends the same site-wide security headers as normal responses
  (`App\Core\SecurityHeaders`).
- **Search plugin rebuilt.** Publicly-visible-only results, portable
  escaped LIKE search (FTS dropped — never populated, not on MySQL),
  correct `site.search` view, capped queries, view-only field
  whitelist.
- **Plugin clean-ups.** The Sitemap plugin (shadowed the core
  `visible()`-based route and leaked scheduled pages) and the
  ContactForm plugin (unresolvable view) were removed; core routes own
  both features. Stale plugin-table rows are skipped gracefully.
- **Timezone + publish dates.** The `timezone` setting is applied via
  `Bootstrap::applyTimezone()` before any date use; `published_at` is
  strictly validated (no more silent 1970) and page create/edit forms
  show validation errors at all.
- **Cache freshness.** Rescheduling a page re-arms
  `published_notified_at`; sliders, SEO, taxonomies, content types and
  media deletes now clear the page cache.
- **Cache-hit analytics.** Cached pages count views through a
  lightweight POST /_v beacon (sendBeacon injected by the public
  layouts, handled pre-bootstrap DB-only in VisitTracker::handleBeacon:
  anonymized IP, bot-free, session-less). The early cache path itself
  never touches the database.
- **client_ip hardening.** Exactly one configured forwarding header is
  trusted (`TRUSTED_PROXY_HEADER`: X-Forwarded-For / X-Real-IP /
  CF-Connecting-IP); mixing headers is no longer spoofable.
- **API token hygiene.** Password change/reset (self-service or admin)
  revokes every API token for the account.
- **Theme slug validation.** `^[a-z0-9_-]+$` plus realpath confinement
  under `themes/`; `../app`-style ACTIVE_THEME values are rejected.
- **Page-cache host allowlist.** Cache keys come from
  `ALLOWED_HOSTS`/`APP_URL`; unknown Host headers neither read nor
  write cache files.
- **Block sanitization.** The page-block text partials sanitize their
  HTML like the page templates do.
- **Throttle flood cap.** The login-throttle directory is bounded
  (cap enforced opportunistically and by the daily prune job).
- **Mailer.** Transactional mail (password reset, contact
  notifications) goes through `App\Core\Mailer`: SMTP via PHPMailer
  when `MAIL_HOST` is set, `mail()` fallback otherwise, failures logged
  to `storage/logs/mailer.log`.
- **Admin performance.** Auth user memoization with eager-loaded
  permissions, 50-per-page media lists, one-query plugin admin.
- **Small clean-ups.** Redundant CSRF header expression removed;
  GlobalBlock 404s instead of 500s; reserved top-level slugs rejected;
  contact messages store the anonymized IP by default
  (`CONTACT_STORE_FULL_IP=true` to keep full addresses); invalid plugin
  manifests are skipped with a log line.

## Starter theme overhaul & content features

- **Self-hosted starter theme.** All CDN dependencies removed (Bulma,
  bulma-carousel, Google Fonts, lucide, Tailwind in the fallback
  layout). A ~15KB custom stylesheet plus a small vanilla JS bundle now
  provide the layout, navbar (keyboard/touch dropdowns), scroll-snap
  hero slider with autoplay/pause/dots, forms, cards, dark mode
  (system + manual toggle persisted to localStorage) and image
  dimensions/lazy-loading.
- **Blog & post rendering fixed.** `site/post` used an undefined
  `$post`; the blog index is a real route (`/blog`, 10 per page,
  visible-only, newest first) with post cards and pagination.
- **Global blocks fixed for both data shapes.** All theme block
  templates render the sanitized `$data` contract (string and JSON
  shapes) instead of raw `$block->content`; render failures are logged;
  fresh-install schema drift in `global_blocks` (missing `type`/`status`
  columns) fixed.
- **Themed search/contact/404.** The starter theme now ships
  search/contact templates (Bulma markup) and a themed 404 with a
  search box and recent pages; the core fallback layout dropped its
  Tailwind CDN and hard-coded title.
- **Subdirectory deployments.** `url()` prefixes the APP_URL base path
  across theme links, `Theme::asset()` and the `/_v` view beacon;
  `public/index.php` strips the base path before matching.
- **Taxonomy archives & RSS.** `/category/@slug` and `/tag/@slug`
  archives (visible-only, paginated), `/feed.xml` RSS 2.0 for the
  latest 20 posts with auto-discovery link, archive URLs in the
  sitemap, og:type=article and JSON-LD (WebSite/Organization/Article)
  for posts.
- **Redirects manager.** Admin CRUD for 301/302 redirects applied
  before the framework boots (and before the page cache), stored in the
  previously unused `redirects` table.
- **New page-builder blocks.** image, CTA, FAQ, columns, video_embed
  (YouTube/Vimeo host allowlist enforced on save), testimonials —
  registered in the admin builder, sanitized server-side.
- **Theme options.** Declare options in `theme.json` (colors, container
  width, font stack, logo, header CTA, footer text); edit them under
  Admin > Themes; values emit as CSS variables. Colors validated as
  hex.
- **Fixes.** Media reference protection (in-use files cannot be
  deleted), page status/type allowlists, SMTP default port 587, HSTS
  made configurable, page-cache host allowlist, and the cache keys/
  beacon working in subdirectories.

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
