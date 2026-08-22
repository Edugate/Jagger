# Uninstalling Jagger

Read this **before** running `apt purge` / deleting the install directory — some of this is
manual on purpose (docs/AUDIT.md's whole point was to stop scripts from doing risky things to
data/secrets unattended).

## 1. Stop and disable the services

```sh
sudo systemctl disable --now php8.4-fpm jagger-gearman-worker.service jagger-mdq-worker.service 2>/dev/null || true
sudo a2dissite <your-fqdn>
sudo systemctl reload apache2
```

## 2. Back up first if you might want this data again

- Database: `mysqldump <database> > jagger-backup.sql`
- `/etc/jagger/*.php` — your config **and generated secrets** (`encryption_key`, `syncpass`).
  Losing `encryption_key` makes existing encrypted session/cookie data unreadable if you ever
  reinstall and expect continuity; it does not affect the SAML metadata/database itself.
- `/opt/jagger/signedmetadata/`, `/opt/jagger/logos/` if you want the signed metadata / uploaded
  logos preserved.

## 3. Remove the package / files

`.deb` install:

```sh
sudo apt remove jagger        # keeps /etc/jagger, /var/log/jagger, the jagger user
sudo apt purge jagger         # same, still keeps all of the above -- see debian/postrm
```

Deliberately, **neither** `remove` nor `purge` deletes `/etc/jagger`, `/var/log/jagger`, or the
`jagger` system user/group — a package uninstall should never silently delete secrets or make it
look like data was cleaned up when a database still has all your federation metadata in it.

`install.sh` install: there's no package manager tracking it, so:

```sh
sudo rm -rf /opt/jagger
```

## 4. Remove what the package/installer intentionally left behind, once you're sure

```sh
sudo rm -rf /etc/jagger /var/log/jagger
sudo userdel jagger
sudo groupdel jagger   # only if nothing else uses the group
sudo rm -f /etc/php/8.4/fpm/pool.d/jagger.conf /etc/apache2/sites-available/<your-fqdn>.conf
sudo rm -f /etc/systemd/system/jagger-*.service
sudo systemctl daemon-reload
```

## 5. Drop the database, if you're sure

```sh
mysql -u root -e "DROP DATABASE jagger; DROP USER 'jagger'@'localhost';"
```

This is the actual data-loss step in the whole process — everything above it is reversible from
a backup, this isn't.
