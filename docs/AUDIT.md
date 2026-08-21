# Jagger Federation — Repository Audit

Date: 2026-08-21. Scope: everything needed to run Jagger in production on **Ubuntu 26.04 LTS**
and **Debian 13.6.0** (and future point releases of both). This is the reference document the
rest of the modernization work (`docs/CI4_MIGRATION.md`, systemd units, `.deb` packaging, CI,
install docs) links back to.

## 1. What Jagger is

CodeIgniter 3 web app (`edugate/resourceregistry`) that manages SAML federation metadata /
attribute-release policy, with a Doctrine ORM-backed MySQL database, optional Gearman-backed
background workers, and a Shibboleth-SP-fronted login flow. No previous CI, Docker, systemd
units, or packaging exist in the repo.

| Layer | Count |
|---|---|
| Controllers (`application/controllers`) | 67 |
| Doctrine models (`application/models`) | 39 |
| Libraries (`application/libraries`) | 37 |
| Views (`application/views`) | 131 |

## 2. Runtime & language

- **PHP**: `INSTALL.md` targets 8.1 (Ubuntu 22.04) / 8.2 (Debian 12). PHP 8.1 reached EOL
  2025-12-31. Debian 13 "trixie" ships **PHP 8.4** as the default `php` metapackage; Ubuntu
  26.04 is expected to track a similarly current 8.3/8.4 line. → target **PHP 8.4**, code
  audited for 8.3/8.4 deprecations (dynamic properties, implicit-nullable params, etc.).
- **Framework**: CodeIgniter **3.1.13**, installed today by manually downloading a GitHub tag
  tarball outside Composer (`INSTALL.md` §"Install CodeIgniter") — not tracked as a real
  dependency at all. CI3 is officially maintenance-only (security patches only, no features).
  → migrated to Composer-installed CodeIgniter 4 incrementally; see `docs/CI4_MIGRATION.md`.
- **Web server**: Apache 2.4 with `mod_php` (implied by current docs — no PHP-FPM pool, no
  systemd unit of its own; PHP always executes as `www-data`).
- **Database**: MySQL/MariaDB (`default-mysql-server`), schema created via
  `./doctrine orm:schema-tool:create` — no migrations, so upgrades have no defined path today.
  Charset is `utf8`/`utf8_general_ci` (legacy 3-byte MySQL "utf8", not `utf8mb4` — cannot store
  4-byte UTF-8 such as most emoji; not a blocker for this project's SAML metadata content, so
  flagged but not changed here to avoid an unrequested schema migration).
- **Queue/workers**: Gearman (`gearman-job-server`, `php-gearman`), invoked from
  `application/libraries/Gearmanw.php`, `Jqueue.php`, `Mq.php`, `Gworkertemplates.php` and 3
  more files. No systemd unit exists — nothing currently supervises the worker process.
  `gearman-job-server` is confirmed present in the Debian 13 "trixie" archive; Ubuntu mirrors
  Debian for this package (final confirmation belongs in CI, see M7).
- **Cache**: APCu and Memcached, both optional per `config_rr.php`/`memcached-default.php`.

## 3. Composer dependencies (`application/composer.json`)

| Package | Current constraint | Status | Action |
|---|---|---|---|
| `doctrine/orm` | `2.4.* \|\| 2.8.4` | 2.4 predates PHP 7; 2.8.4 pinned exact | Bump to latest 2.x (2.19/2.20 line — last series supporting PHP 8.3/8.4 without the ORM 3.x breaking API change) |
| `laminas/laminas-permissions-acl` | `2.7.2 \|\| 2.9.0` | fine, just old patch levels | Bump to latest 2.x |
| `mtdowling/cron-expression` | `1.1.*` | **Packagist-abandoned**, `INSTALL.md` §"Install required third parties libraries" already tells the installer to hand-edit this to `dragonmantank/cron-expression` before `composer install` will even resolve | Fix in the repo's `composer.json` directly — stop relying on every installer remembering a manual edit |
| `phpseclib/phpseclib` | `2.0.*` | 2.x in security-fix-only mode | Bump to 3.x (namespace/API changes — audit call sites) |
| `lcobucci/jwt` | `4.1.5` | superseded | Bump to latest 5.x (breaking API — audit call sites, used for OIDC auth) |
| `php-amqplib/php-amqplib` | `2.6.*` | old | Bump to 3.x (connection API changed — audit call sites) |
| `robrichards/xmlseclibs` | `3.1.5` | fine, current major | Bump to latest 3.x patch |

No dependency-vulnerability scan result is included here yet — `composer audit` should gate CI
(M7) once the bumped `composer.json` lands, since this sandbox has no PHP/Composer to run it
locally (§6).

## 4. Hard-coded secrets / weak defaults found

These are template files (`*-default.php`, gitignored once copied to the real filename per
`.gitignore`), so they aren't "live" secrets — but they are exactly the kind of default an
installer can forget to change, and two of them are real, non-obviously-placeholder values:

- `application/config/config-default.php:227` — `$config['encryption_key'] =
  'jhiufhi34hfhewhfsdfhsd';` — a real (if low-entropy) key shipped in the template, not an
  obvious `CHANGEME`.
- `application/config/config_rr-default.php:10` — `$config['syncpass'] = 'verystrongpasss';`
- `application/config/config_rr-default.php:208` — AMQP `'password' => 'guest'` (the RabbitMQ
  default credential).
- By contrast `application/config/database-default.php` already uses `CHANGEME` placeholders —
  the pattern to replicate everywhere else.
- → the new installer (`install.sh` replacement) generates all of these randomly on first
  install instead of shipping any live-looking default; templates switch to `CHANGEME`.

## 5. TLS/Apache configuration

`INSTALL.md`'s example vhost cipher suite includes `RC4` and allows `TLSv1`/`TLSv1.1`:

```
SSLProtocol All -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
SSLCipherSuite "... EECDH+aRSA+RC4 ... RC4 ..."
```
(the `-TLSv1 -TLSv1.1` disables them, but `RC4` is still explicitly allowed as a cipher, and the
suite is written for the OpenSSL 1.0.x/pre-TLS1.3 era). → rewritten to TLS 1.2/1.3-only with a
modern ECDHE+AESGCM/CHACHA20 suite as part of M4.

## 6. Environment constraints for this engagement

This sandbox has **no `php`, `composer`, or `mysql` executable available** (verified directly).
Nothing here can be run end-to-end against a live database/webserver. Verification for the rest
of this work is: `php -l` syntax checks, `composer validate`, and a GitHub Actions CI matrix
against Ubuntu and `debian:13` containers (M7) — that CI is the actual install/build/start/
restart/upgrade/DB-connectivity test harness, not manual click-through here.

## 7. Vestigial / dead dependencies to drop

- `default-jdk` and `python-pip` are listed in `INSTALL.md`'s package-install step for both
  Ubuntu and Debian, but nothing in `application/` calls `java`, `python`, or `pip`
  (`grep -rn "java\b\|python\|pip\b" application --include=*.php` → no live code hits, only an
  unrelated `.jar` MIME-type entry and a `hotjava` user-agent string). The only place Java is
  actually referenced is the old, superseded `INSTALL.txt`, which documents an external
  `xmlsectool` (Java) cron script for signing metadata. That flow is dead: metadata signing is
  already implemented in pure PHP in `application/libraries/Mdqsigner.php` via the
  already-required `robrichards/xmlseclibs` Composer package. → drop `default-jdk`/`python-pip`
  from the install docs and packaging entirely; replace the old cron+xmlsectool step with a
  systemd timer that runs `Mdqsigner` directly (M4).

## 8. Frontend build tooling (explicitly out of scope here)

`builder/` uses Bower (deprecated/unmaintained since 2017), Grunt 1.0.1, a `gulpjs/gulp#4.0`
GitHub-branch reference (never a tagged release), and `node-sass` (deprecated, native-binary
version-locked to old Node ABIs). None of this runs at deploy time — the already-built/minified
output in `builder/build/minified` is what Apache actually serves — so it does not block
production deployment on Ubuntu 26.04/Debian 13 and is **not** modernized in this engagement.
Flagged as a known limitation (a Bower→npm, node-sass→Dart Sass, Grunt/Gulp→Vite rebuild is its
own project) so it isn't mistaken for an oversight.

## 9. Everything downstream of this audit

- Dependency/PHP 8.4 compatibility fixes: see composer.json + code changes in this branch.
- CodeIgniter 4 migration plan and tracking: `docs/CI4_MIGRATION.md`.
- systemd units, dedicated `jagger` user, secrets-out-of-repo: `packaging/systemd/`,
  `packaging/etc/jagger/`.
- Debian packaging: `debian/`.
- OS-aware idempotent installer: `install.sh`.
- CI: `.github/workflows/`.
- Install/upgrade/troubleshoot/uninstall docs: `docs/INSTALL.md` (replaces the old
  `INSTALL.md`), `docs/UPGRADE.md`, `docs/TROUBLESHOOTING.md`, `docs/UNINSTALL.md`.
