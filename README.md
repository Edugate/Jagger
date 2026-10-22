# Jagger Federation Registry

[Jagger](http://jagger.heanet.ie) is developed by HEAnet to manage the Edugate multiparty SAML federation. Other organisations use Jagger to manage their federations, but it can also be used to manage the web-of-trust for a single entity. Additionally, it can be used as a GUI for the Shibboleth SAML Identity Provider ([Shibboleth](https://www.shibboleth.net)).

> [!NOTE]
> **This is not the official installation guide.** HEAnet's official documentation
> (see [Documentation](#documentation)) targets older, EOL package and OS versions.
> [`deploy.sh`](deploy.sh) below targets current releases instead — Debian 13 /
> Ubuntu 24.04+, PHP 8.4/8.5, Let's Encrypt or a self-signed cert, `utf8mb4` —
> see [Requirements](#requirements) below for the full current baseline.

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
  - [Quick Start (Automated)](#quick-start-automated)
  - [Setup Jagger Registry](#setup-jagger-registry)
- [Documentation](#documentation)
- [Authors & Thanks](#authors--thanks)

## Features

1. Synchronise SAML metadata from another federation.
2. Create and manage a federation.
3. Create a single circle of trust containing metadata of all entities that your organisation participates in via multiple federations.
4. GUI to manage the attribute policy of identity providers based on the Shibboleth SAML implementation.
5. Filter the `RequestedAttribute`s of a SAML service provider to allow an IdP to release attributes to such providers based on a policy set in the Jagger GUI.
6. Create and edit metadata of individual entities.
7. Notification subsystem with subscription options.

---

## Requirements

### Hardware
- **CPU**: 4 Core (64-bit)
- **RAM**: 8 GB
- **HDD**: 50 GB
- **OS**: Debian 13.* or Ubuntu 24.04 LTS / 26.04 LTS

### Software
- **Apache Web Server**: 2.4
- **OpenSSL**: 3.5
- **PHP**: 8.4 / 8.5
- **Java**: Default JDK (Required for XMLSecTool)
- **Python**: 3.x with `venv` and `pip` (Required for PyFF)
- **Shibboleth Service Provider**: 5 *(Optional)*

### Others
- **Domain**: A Fully Qualified Domain Name (FQDN) with public DNS resolution pointing to this server.
- **Logo**: 
  - Size: 350px wide × 64px high (or 146px wide × 64px high)
  - Format: PNG
  - Style: Transparent background

---

## Installation

Make sure you meet the [Requirements](#requirements) above before starting.

### Quick Start (Automated)

[`deploy.sh`](deploy.sh) installs and configures Jagger end-to-end on a fresh
Debian 13 / Ubuntu 24.04+ server. On the target server, as root:

```bash
git clone https://github.com/talha50819/Jagger.git
cd Jagger
chmod +x deploy.sh
sudo ./deploy.sh
```

It auto-detects the server's IP/OS, asks you for the handful of values it
can't guess (FQDN, admin email, DB credentials — or press Enter to
auto-generate a strong password), then runs the full deployment with a
progress bar and per-step timing. It also asks whether this is a **testing**
deployment (default — a self-signed certificate, works with any hostname,
e.g. inside a VM) or a **production** one (a real Let's Encrypt certificate,
which needs a public domain that already resolves to this server).

If it finds an existing install at `/opt/rr3`, it asks whether to do a
**fresh** install (wipe it) or **migrate** the existing environment: back up
its database, redeploy fresh code, then restore the database into it. When
migrating you also choose whether to reuse the detected FQDN/admin
email/DB settings/logo as-is, or answer the setup questions fresh — either
way the encryption key and sync password are carried over rather than
regenerated, since a fresh key would break decrypting the restored data.

> [!TIP]
> For a non-interactive/scripted run, pass `-y` and set the `JAGGER_*`
> environment variables described at the top of [`deploy.sh`](deploy.sh)
> (e.g. `JAGGER_FQDN`, `JAGGER_ADMIN_EMAIL`).

Once it finishes, continue with [Setup Jagger Registry](#setup-jagger-registry)
below.

---

### Setup Jagger Registry

1. Navigate to: `https://<your-fqdn>/rr3/setup` (the FQDN you gave `deploy.sh`).
2. Create the initial Admin user.
3. **Crucial Security Step**: Edit `/opt/rr3/application/config/config_rr.php` and change:
   ```php
   $config['rr_setup_allowed'] = FALSE;
   ```

---

## Documentation

Official HEAnet administration documentation (application usage, not this
install guide) is available at:  
[https://jagger.heanet.ie/jaggerdocadmin/index.html](https://jagger.heanet.ie/jaggerdocadmin/index.html)

---

## Authors & Thanks

- **Install Guide Author**: Muhammad Talha Siddiqui — updated and maintained independently of HEAnet, to track current OS/package versions.
- **Project Repository**: [Edugate/Jagger](https://github.com/Edugate/Jagger)
- **Special Thanks**: [@janul](https://github.com/janul) and the HEAnet/Edugate community for their ongoing support and development.
