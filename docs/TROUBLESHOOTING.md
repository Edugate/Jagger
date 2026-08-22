# Troubleshooting

## After an OS upgrade bumps the default PHP version past 8.4

Every path in this project that mentions PHP 8.4 (`debian/rules`'s pool-conf install path,
`install.sh`'s printed instructions, this doc, `packaging/README.md`) is hardcoded to `8.4`
because that's the actual default on Ubuntu 26.04/Debian 13.6.0 today, not auto-detected. If a
later point release (or the next OS version) defaults to a newer PHP, the `.deb`'s pool conf
would still land in `/etc/php/8.4/fpm/pool.d/`, which a system now running e.g. `php8.5-fpm`
won't read -- symptom: `php8.5-fpm` runs fine, but `/run/php/jagger-fpm.sock` never appears and
Apache 502s. Fix: `cp /etc/php/8.4/fpm/pool.d/jagger.conf /etc/php/<new-version>/fpm/pool.d/` and
update the version in the paths above for next time.

## 502 Bad Gateway from Apache

Almost always PHP-FPM either isn't running or Apache can't reach its socket.

```sh
systemctl status php8.4-fpm
ls -l /run/php/jagger-fpm.sock   # should exist, owned www-data:www-data, mode 0660
journalctl -u php8.4-fpm -n 50
```

If the pool failed to start, check `php-fpm -t` and `/var/log/jagger/php-fpm-error.log`.

## "Your system folder path ... does not appear to be set correctly"

`composer install --working-dir=application` was never run (or failed), so
`application/vendor/codeigniter/framework/system` doesn't exist. Re-run it as the `jagger` user
(or root, if you then re-run `packaging/scripts/provision.sh` to fix ownership afterward).

## Database connection errors

- Check `/etc/jagger/database.php` doesn't still have `CHANGEME` placeholders.
- `mysql -u <user> -p -h <hostname> <database>` with the same credentials, from the same host, as
  a sanity check independent of PHP.
- `open_basedir` in `packaging/php-fpm/jagger.pool.conf` includes `/etc/jagger`, so PHP-FPM can
  read `database.php` there — if you moved `/etc/jagger` elsewhere, update `open_basedir` too.

## Setup route (`/setup`) returns "Setup is disabled"

`$config['rr_setup_allowed']` in `/etc/jagger/config_rr.php` is `FALSE`. Set it to `TRUE`,
restart `php8.4-fpm`, and remember to set it back to `FALSE` once you've created the admin user —
it's an unauthenticated account-creation route while left `TRUE`.

## Gearman / RabbitMQ worker won't stay running

```sh
systemctl status jagger-gearman-worker.service
systemctl status jagger-mdq-worker.service
journalctl -u jagger-gearman-worker.service -n 50
```

These only work if the corresponding backend is actually installed and enabled:

- `jagger-gearman-worker`: needs `gearman-job-server` running and `$config['gearman'] = TRUE;`.
- `jagger-mdq-worker`: needs `rabbitmq-server` running and
  `$config['rabbitmq']['enabled'] = TRUE;`, plus real (non-`CHANGEME`) credentials in
  `config_rr.php` that match a RabbitMQ user you actually created.

If neither feature is something you use, it's fine to leave both units stopped/disabled —
metadata signing still works via the on-demand action in the UI (`Msigner.php`).

## TLS handshake failures after the vhost update

The new `packaging/apache/jagger.conf.template` only allows TLS 1.2/1.3 (`docs/AUDIT.md` §5) —
an old client that only speaks TLS 1.0/1.1 will fail to connect. This is intentional; don't
re-add older protocols/RC4 to work around it. Verify your config with:

```sh
openssl s_client -connect <your-fqdn>:443 -tls1_2
```

## CI4-migrated route (`/dashboard`, `/auth/logout`) behaves differently than expected

Check `app4_routes.php` (repo root) and `app4/app/Config/Routes.php` agree, and see
`docs/CI4_MIGRATION.md` for what's actually migrated vs. still legacy CodeIgniter 3 — a
CI3-vs-CI4 behavior mismatch on a listed route is a real bug, not something to route around.

## Still stuck

Check `journalctl -u php8.4-fpm -u jagger-gearman-worker -u jagger-mdq-worker -u apache2` for the
relevant time window, and `/var/log/jagger/` / `/var/log/apache2/` for application-level errors.
