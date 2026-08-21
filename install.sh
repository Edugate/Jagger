#!/bin/bash
#
# Jagger installer for Ubuntu 26.04 LTS / Debian 13.x (and compatible future
# point releases of both). Replaces the old install.sh, which only created a
# logos2/ directory and printed manual copy-these-files instructions -- see
# docs/AUDIT.md for what else changed and why.
#
# This is the plain-script counterpart to the .deb package (debian/): both
# call packaging/scripts/provision.sh for the actual user/directory/secret
# provisioning, so there is exactly one place that logic lives. Safe to
# re-run.
set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Run as root (sudo ./install.sh)." >&2
    exit 1
fi

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

detect_os() {
    if [ ! -r /etc/os-release ]; then
        echo "unsupported:0"
        return
    fi
    # shellcheck disable=SC1091
    . /etc/os-release
    echo "${ID}:${VERSION_ID:-0}"
}

OS_ID_VERSION="$(detect_os)"
OS_ID="${OS_ID_VERSION%%:*}"
OS_VERSION="${OS_ID_VERSION##*:}"

echo "Detected: ${OS_ID} ${OS_VERSION}"

case "$OS_ID" in
    ubuntu)
        if ! printf '%s\n' "$OS_VERSION" | grep -Eq '^(2[6-9]|[3-9][0-9])\.'; then
            echo "Warning: tested on Ubuntu 26.04+; you're on ${OS_VERSION}. Continuing anyway." >&2
        fi
        ;;
    debian)
        if ! printf '%s\n' "$OS_VERSION" | grep -Eq '^(1[3-9]|[2-9][0-9])'; then
            echo "Warning: tested on Debian 13+; you're on ${OS_VERSION}. Continuing anyway." >&2
        fi
        ;;
    *)
        echo "Unsupported OS ('${OS_ID}'). This installer targets Ubuntu 26.04+ and Debian 13+." >&2
        exit 1
        ;;
esac

# Both distros currently default their unversioned "php" metapackage to
# PHP 8.4, so unlike the old INSTALL.md (which hard-coded php8.1-opcache vs
# php8.2-opcache per distro), a single package list works for both -- one
# fewer thing to keep in sync per OS release. If a future release changes
# the default PHP major version this list still resolves correctly since
# it uses the versionless "php" meta-packages throughout.
PACKAGES=(
    curl ca-certificates openssl git
    php php-fpm php-cli php-common php-opcache
    php-gd php-curl php-mysql php-intl php-xml php-mbstring
    php-soap php-bcmath php-zip php-apcu php-memcached
    apache2 default-mysql-server
    composer
)

echo "Installing packages: ${PACKAGES[*]}"
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends "${PACKAGES[@]}"

echo "Optional packages (gearman-job-server, php-gearman, rabbitmq-server)"
echo "are not installed automatically -- only needed if you enable"
echo "\$config['gearman'] or \$config['rabbitmq']['enabled'] in config_rr.php."

echo "Installing Composer dependencies (application/, app4/)..."
composer install --no-dev --optimize-autoloader --no-interaction --working-dir="$REPO_ROOT/application"
composer install --no-dev --optimize-autoloader --no-interaction --working-dir="$REPO_ROOT/app4"

echo "Provisioning jagger user, directories, and /etc/jagger config..."
JAGGER_HOME="$REPO_ROOT" "$REPO_ROOT/packaging/scripts/provision.sh"

cat <<'EOF'

Install script finished. Remaining manual steps:
  1. Edit /etc/jagger/database.php with your MySQL host/username/password/database.
  2. Create the database (see docs/INSTALL.md).
  3. cd application && sudo -u jagger ./doctrine orm:schema-tool:create
  4. Copy packaging/apache/jagger.conf.template to
     /etc/apache2/sites-available/<your-fqdn>.conf and fill in the
     placeholders, then:
       a2enmod ssl rewrite headers proxy_fcgi setenvif alias
       cp packaging/php-fpm/jagger.pool.conf /etc/php/*/fpm/pool.d/jagger.conf
       systemctl enable --now php*-fpm
       a2ensite <your-fqdn> && systemctl reload apache2
  5. Install your TLS certificate/key per docs/INSTALL.md.
  6. Visit https://<your-fqdn>/setup to create the admin user, then set
     $config['rr_setup_allowed'] = FALSE; in /etc/jagger/config_rr.php.
  7. Optional: enable the systemd units in packaging/systemd/ if you use
     Gearman and/or RabbitMQ (see packaging/README.md).

See docs/INSTALL.md for the full walkthrough.
EOF
