# packaging/

Everything here is consumed by both the plain-script installer (`install.sh`,
repo root) and the `.deb` package (`debian/`) -- neither duplicates this
content, both call into it, so there is exactly one place that defines how
Jagger is provisioned.

| Path | Purpose |
|---|---|
| `scripts/provision.sh` | Creates the `jagger` system user, directories, and `/etc/jagger/*.php` config (generated from the `*-default.php` templates with random secrets), and symlinks `application/config/*.php` to it. Idempotent -- re-running it after an upgrade does not touch existing config or regenerate secrets. |
| `systemd/jagger-gearman-worker.service` | Supervises `php index.php gworkers worker` (`application/controllers/Gworkers.php::worker()`). Only does anything when `$config['gearman'] = TRUE`. |
| `systemd/jagger-mdq-worker.service` | Supervises `php index.php mdqworker worker` (`application/controllers/Mdqworker.php::worker()`), the AMQP consumer that (re)signs MDQ metadata via `Mdqsigner.php`. Only does anything when `$config['rabbitmq']['enabled'] = TRUE`. |
| `php-fpm/jagger.pool.conf` | PHP-FPM pool running as the `jagger` user, `open_basedir`-confined to `/opt/jagger:/etc/jagger:/var/log/jagger:/tmp`. |
| `apache/jagger.conf.template` | Apache vhost: TLS 1.2/1.3-only cipher suite (drops the RC4-permitting suite from the old install docs, see `docs/AUDIT.md` #5), proxies PHP to the FPM pool via `mod_proxy_fcgi` instead of `mod_php`. |

Both systemd units run with the same hardening profile: `NoNewPrivileges`,
`ProtectSystem=strict` + an explicit `ReadWritePaths=` allow-list,
`ProtectHome`, `PrivateTmp`/`PrivateDevices`, capability bounding set
cleared, no new namespaces/personalities, syscall filtering to the native
ABI. If a unit needs a new writable path, add it to `ReadWritePaths=` --
don't loosen `ProtectSystem`.

See `docs/AUDIT.md` §7 for why there is no systemd timer here replacing the
old external `xmlsectool`+JDK cron signing script: nothing in this codebase
implements the "resign everything periodically" job that script drove, and
inventing one wasn't part of this modernization -- signing already happens
via `jagger-mdq-worker` (queue-driven) and the on-demand AJAX sign action
in `Msigner.php`.
