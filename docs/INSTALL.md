# Installing Jagger on Ubuntu 26.04 LTS / Debian 13.6.0

This replaces the old root `INSTALL.md`, which targeted Ubuntu 22.04/Debian 12 and PHP 8.1/8.2,
required manually downloading a CodeIgniter 3 tarball to `/opt/codeigniter`, and pointed at a
now-dead Java `xmlsectool` metadata-signing flow. See `docs/AUDIT.md` for the full list of what
changed and why.

There are two ways to install; both end up in the same state (same dedicated `jagger` user, same
`/etc/jagger` config, same systemd units) because both call the same
`packaging/scripts/provision.sh`.

## Table of contents

1. [Requirements](#requirements)
2. [Option A: the `.deb` package (recommended)](#option-a-the-deb-package-recommended)
3. [Option B: `install.sh`](#option-b-installsh)
4. [Configure Jagger](#configure-jagger)
5. [Database](#database)
6. [Apache virtual host + TLS](#apache-virtual-host--tls)
7. [Background workers (optional)](#background-workers-optional)
8. [First-run setup](#first-run-setup)

## Requirements

- **OS**: Ubuntu 26.04 LTS or Debian 13.x (see `install.sh` for the version check; future point
  releases of both are expected to keep working since nothing here pins an exact point release).
- **Hardware**: 2 CPU cores (64-bit), 4 GB RAM, 10 GB disk — unchanged from the old requirements.
- **A TLS certificate and private key** for the Jagger hostname.
- **A logo** (PNG, transparent background, 64×350 / 64×146) if you want your own branding.

## Option A: the `.deb` package (recommended)

Build it (see `debian/README.source` — requires network access for `composer install` during the
build) or download a pre-built one from your release process, then:

```sh
sudo apt install ./jagger_1.0.0-1_all.deb
# or, without network access to resolve Depends: automatically:
sudo dpkg -i jagger_1.0.0-1_all.deb && sudo apt -f install -y
```

`postinst` runs `packaging/scripts/provision.sh` for you and prints the remaining manual steps
(same as [Configure Jagger](#configure-jagger) onward below).

## Option B: `install.sh`

```sh
git clone https://github.com/Edugate/Jagger /opt/jagger
cd /opt/jagger
sudo ./install.sh
```

Detects Ubuntu vs Debian, installs the right package set, runs `composer install` for both
`application/` and `app4/`, and provisions the `jagger` user/config exactly like the `.deb` does.
Safe to re-run.

Optional packages **not** installed automatically — only pull these in if you turn on the
matching `config_rr.php` setting:

| Package | Needed for |
|---|---|
| `gearman-job-server`, `php-gearman` | `$config['gearman'] = TRUE;` |
| `rabbitmq-server` | `$config['rabbitmq']['enabled'] = TRUE;` |

## Configure Jagger

Both install paths generate `/etc/jagger/{config.php,config_rr.php,database.php,email.php,memcached.php}`
from the `application/config/*-default.php` templates, with `encryption_key` and `syncpass`
already filled in randomly (`docs/AUDIT.md` §4) — **you do not need to (and shouldn't) hand-edit
those two values**. What's left as `CHANGEME` and does need your input:

- `/etc/jagger/database.php`: `hostname`, `username`, `password`, `database`, and the matching
  `dsn` string.
- `/etc/jagger/config_rr.php`: `rabbitmq.user`/`rabbitmq.password`, only if you're using RabbitMQ
  (leave `rabbitmq.enabled = false` otherwise, which is the default).
- `/etc/jagger/config.php`: `base_url` (e.g. `https://jagger.example.org`).

## Database

```sh
mysql -u root
CREATE DATABASE jagger CHARACTER SET utf8 COLLATE utf8_general_ci;
CREATE USER 'jagger'@'localhost' IDENTIFIED BY '<a real password, matching database.php>';
GRANT ALL PRIVILEGES ON jagger.* TO 'jagger'@'localhost';
FLUSH PRIVILEGES;
```

Then, first install only:

```sh
cd /opt/jagger/application
sudo -u jagger ./doctrine orm:schema-tool:create
```

(On an upgrade, use `orm:schema-tool:update` instead — see `docs/UPGRADE.md`.)

## Apache virtual host + TLS

```sh
a2enmod ssl rewrite headers proxy_fcgi setenvif alias
cp packaging/php-fpm/jagger.pool.conf /etc/php/8.4/fpm/pool.d/jagger.conf
systemctl enable --now php8.4-fpm
```

Copy `packaging/apache/jagger.conf.template` to
`/etc/apache2/sites-available/<your-fqdn>.conf`, replace `{{JAGGER_FQDN}}` and `{{ADMIN_EMAIL}}`,
install your certificate/key at the paths it references, then:

```sh
a2ensite <your-fqdn>
a2dissite 000-default default-ssl
systemctl reload apache2
```

Unlike the old vhost example, this one talks to PHP-FPM (running as the `jagger` user) via
`mod_proxy_fcgi` instead of `mod_php`, and only allows TLS 1.2/1.3 with a modern cipher suite —
no more RC4 in the allowed list (`docs/AUDIT.md` §5).

## Background workers (optional)

Only relevant if you enabled Gearman and/or RabbitMQ above:

```sh
sudo cp packaging/systemd/jagger-*.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now jagger-gearman-worker.service   # if using gearman
sudo systemctl enable --now jagger-mdq-worker.service       # if using rabbitmq
```

(The `.deb` already installs these unit files via `dh_installsystemd`; `install.sh` does not
copy them automatically since installing `packaging/systemd/*.service` to `/etc/systemd/system/`
outside package management needs the same conscious opt-in either way.)

## First-run setup

1. Visit `https://<your-fqdn>/setup` and create the admin user.
2. Edit `/etc/jagger/config_rr.php` and set `$config['rr_setup_allowed'] = FALSE;` — the setup
   route stays open otherwise.

For what to do next time you upgrade the package, see `docs/UPGRADE.md`. If something doesn't
come up, see `docs/TROUBLESHOOTING.md`. To remove Jagger, see `docs/UNINSTALL.md`.
