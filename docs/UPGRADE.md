# Upgrading Jagger

Replaces the old 6-line root `UPGRADE.txt`.

## `.deb` installs

```sh
sudo apt install ./jagger_<new-version>_all.deb
```

`postinst` re-runs `packaging/scripts/provision.sh`, which is idempotent: it will **not**
overwrite `/etc/jagger/*.php` (your config and generated secrets are untouched) and will **not**
recreate the `jagger` user if it already exists. The FPM pool conf
(`/etc/php/8.4/fpm/pool.d/jagger.conf`) is a dpkg conffile — if you customized it, `dpkg` will
prompt you to keep your version, take the new one, or diff them, same as any other conffile.

After the package upgrade, update the database schema (non-destructive — this is `:update`, not
`:create`):

```sh
cd /opt/jagger/application
sudo -u jagger ./doctrine orm:schema-tool:update --force
```

Then restart the services:

```sh
sudo systemctl restart php8.4-fpm
sudo systemctl restart jagger-gearman-worker.service jagger-mdq-worker.service 2>/dev/null || true
```

## `install.sh` installs

```sh
cd /opt/jagger
git pull
sudo ./install.sh
```

Same idempotency guarantees as the `.deb` path (both call
`packaging/scripts/provision.sh`), then the same `orm:schema-tool:update --force` and service
restart steps as above.

## CodeIgniter 4 migration status

If this upgrade includes newly-migrated controllers (see `docs/CI4_MIGRATION.md`), no separate
action is needed — `app4_routes.php` and `app4/app/Config/Routes.php` ship as part of the
upgrade and take effect on the next `php8.4-fpm` restart.

## Rolling back

`.deb`: `sudo apt install ./jagger_<previous-version>_all.deb`. Your `/etc/jagger/*.php` is still
untouched. If the newer version ran a schema-tool `:update` that isn't backward-compatible with
the older code, you'll need your own database backup/restore — Jagger does not ship automatic
schema rollback (this was also true before this modernization; not a new limitation).
