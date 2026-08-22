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
  `packaging/scripts/provision.sh` (generates `/etc/jagger/*.php` at install time -- there's no
  static `packaging/etc/jagger/` template directory; the templates it generates from are
  `application/config/*-default.php`, already in the repo).
- Debian packaging: `debian/`.
- OS-aware idempotent installer: `install.sh`.
- CI: `.github/workflows/`.
- Install/upgrade/troubleshoot/uninstall docs: `docs/INSTALL.md` (replaces the old
  `INSTALL.md`), `docs/UPGRADE.md`, `docs/TROUBLESHOOTING.md`, `docs/UNINSTALL.md`.

## 10. QA pass findings (post-initial-implementation)

A full static/manual review pass (no PHP/Composer/MySQL/Docker available here either, and the
GitHub Actions run on this branch's first push failed before any job ran -- the account's Actions
runner was billing-locked, not a code failure) found and fixed the following real bugs introduced
by the initial M1-M8 work:

- **`doctrine/orm` bumped straight to `^2.19` broke entity loading outright.**
  `application/libraries/Doctrine.php` (and its `app4/` counterpart) used
  `Doctrine\Common\ClassLoader` (removed in doctrine/common 3.x, which orm ^2.19 pulls in) and
  `Doctrine\Common\Cache\ArrayCache`/`ApcCache` (removed in doctrine/cache 2.x). Fixed by: replacing
  `ClassLoader` with Composer PSR-4 autoload entries (`models\\`, `Proxies\\`) in both
  `composer.json` files, and explicitly pinning `doctrine/cache: ^1.14` and
  `doctrine/annotations: ^1.14` (the last 1.x line, which still has `SimpleAnnotationReader` --
  needed because all 39 files in `application/models` use bare `@Entity`/`@Column` annotations,
  not `@ORM\Entity`; doctrine/annotations 2.x removed support for that style entirely). This is a
  deliberate, minimal-blast-radius fix -- not a rewrite of 39 model files to attribute-based
  mapping, which was never in scope for a dependency-compatibility pass.
- **`provision.sh`'s blanket permission lockdown stripped the executable bit from `install.sh`
  and `application/doctrine`**, breaking the exact `./doctrine orm:schema-tool:...` command
  documented in `docs/INSTALL.md`/`docs/UPGRADE.md`, and making `install.sh` fail on any re-run
  after its first. Fixed by chmod'ing files with relative permission bits
  (`u+rw,g+r,g-w,o-rwx`) instead of an absolute `0640` that clobbered existing `+x`.
- **The same blanket lockdown reached into `.git`** when `JAGGER_HOME` is a live git checkout
  (the `install.sh` path -- `docs/UPGRADE.md` documents running `git pull` there), which would
  have broken that `git pull` for whichever non-root user is expected to run it. Fixed by
  excluding `.git` from the chown/chmod sweep.
- **Upgrading an existing (pre-packaging) manual install would have silently destroyed its real
  config.** `provision.sh`'s symlink step used `ln -sf`, which overwrites an existing regular
  file -- if `application/config/config.php` etc. already existed with real production values
  (the old, pre-modernization install method), it would be replaced by a symlink to a
  freshly-generated blank template, discarding the real `base_url`/`encryption_key`/Shib mapping
  with no backup. Fixed: an existing regular (non-symlink) config file is now detected and moved
  into `/etc/jagger` first, before the symlink is created.
- **The CI3<->CI4 session bridge never actually started a session.** `app4/app/Controllers/
  BaseController.php` read `$_SESSION` directly without ever calling `session_start()` --
  nothing else in the CI4 request path does either, so `isLoggedIn()` would always have returned
  false for a real, actively-logged-in CI3 user. Fixed by starting the session explicitly with
  the matching cookie name before reading it.
- **`app4/app/Config/Constants.php` and `Config/Events.php` didn't exist.** `system/bootstrap.php`
  requires both directly; the former defines `APP_NAMESPACE`, which `Config/Autoload.php`
  references -- an undefined constant is a fatal error as of PHP 8, not a warning. Both files
  were missing entirely from the M3 scaffold. Added.
- **`Config\App::$sessionTimeToUpdate` was declared as a `bool`; it's an `int`** (seconds between
  session ID regeneration) in CI4. Fixed the type and restored the actual default (300).
- **The Apache rewrite exclusion regex's bare `app` prefix also matched `/app4/...`**
  (`!^/(...|app|...)` has no boundary after the alternation, so `/app4/anything` satisfies the
  same branch as `/app`), letting requests to the CI4 scaffold's own directory skip the
  index.php rewrite -- and with it, the CI3/CI4 dispatch check -- entirely. The `<Directory>`
  `Require all denied` blocks already covered `application/` and `app4/app`+`app4/writable` as a
  second layer, so this wasn't a full bypass, but `app4/public/index.php` itself had no such
  deny and would have been directly reachable. Fixed the regex to require a `/` or end-of-string
  boundary after each alternative, and widened the deny to cover all of `app4/` (previously just
  `app4/app` and `app4/writable`, missing `app4/vendor` and `app4/composer.json`).
- **`open_basedir` on the PHP-FPM pool didn't include wherever PHP's session save path actually
  resolves to.** CI3's session library is autoloaded on every request
  (`application/config/autoload.php`); if `session.save_path` isn't under `open_basedir`, PHP
  can't read or write session files at all -- this would have broken login/sessions for every
  real user, not just produced a log warning. Rather than guess Debian/Ubuntu's exact php.ini
  default, `session.save_path` is now explicitly pinned (FPM pool config, and `-d
  session.save_path=...` on both systemd worker units) to `/var/lib/jagger/sessions`, a
  dedicated directory `provision.sh` creates and owns.
- **`application/composer.json`'s and `app4/composer.json`'s `config.platform.php` override**
  (hardcoded to `"8.3"`) was unnecessary and a maintenance footgun: every install path in this
  project runs `composer install` on the actual target machine right after installing that
  machine's real PHP, so there's no cross-machine version-mismatch scenario for the override to
  protect against, and it would silently stay stuck at "8.3" as OS defaults move forward. Removed.
- **`builder/` (Bower/Grunt/Gulp frontend source, §8 above) was being shipped in the `.deb`**
  despite nothing in `application/views` referencing it -- unnecessary bloat and exposed
  build-tooling source in a production package. Removed from `debian/rules`'s install list; it
  stays in git for frontend development.
- Minor: a dead `cp ... .installed` debug leftover in `.github/workflows/ci.yml` removed; a
  stale `packaging/etc/jagger/` reference (that directory never existed -- config is generated
  dynamically, not templated from a static tree) corrected in two places; `app4/app/Controllers/
  BaseController.php`'s DB connection builder now honors an optional `$db['default']['port']`
  the same way `application/libraries/Doctrine.php` already does, instead of silently ignoring it.

**Still unverified** (same root cause as every other "unverified" item in this document: no
PHP/Composer/MySQL/Docker in this sandbox, and CI hasn't successfully run yet): whether
`doctrine/orm ^2.19` actually accepts `doctrine/cache ^1.14`/`doctrine/annotations ^1.14` as
satisfiable constraints (a real version-conflict here would surface as a `composer install`
failure, not a silent bug -- loud and CI-catchable, not a production landmine), and whether
`Configuration::newDefaultAnnotationDriver()` is still present on `doctrine/orm 2.19` (deprecated
per Doctrine's 2.x compatibility policy, but not, as far as could be confirmed without running
it, removed). Both are flagged in code comments at the exact call sites.
