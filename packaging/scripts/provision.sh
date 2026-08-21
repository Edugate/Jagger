#!/bin/bash
# Idempotent provisioning shared by install.sh (plain-script install) and
# debian/postinst (.deb install/upgrade) -- see docs/AUDIT.md and the
# modernization plan for why this exists: a dedicated least-privilege
# service user, and config/secrets that live outside the source tree
# instead of being hand-copied-and-forgotten-about *-default.php files.
#
# Safe to re-run: every step checks current state before changing anything.
set -euo pipefail

JAGGER_HOME="${JAGGER_HOME:-/opt/jagger}"
JAGGER_ETC="${JAGGER_ETC:-/etc/jagger}"
JAGGER_LOG="${JAGGER_LOG:-/var/log/jagger}"
JAGGER_USER="${JAGGER_USER:-jagger}"

gen_secret() {
    tr -c -d '0123456789abcdefghijklmnopqrstuvwxyz' </dev/urandom | dd bs=32 count=1 2>/dev/null
}

detect_os() {
    if [ ! -r /etc/os-release ]; then
        echo "unknown"
        return
    fi
    # shellcheck disable=SC1091
    . /etc/os-release
    echo "${ID}:${VERSION_ID:-unknown}"
}

echo "Detected OS: $(detect_os)"

# --- dedicated system user, no login shell, no home directory contents ---
if ! id "$JAGGER_USER" >/dev/null 2>&1; then
    echo "Creating system user '$JAGGER_USER'"
    useradd --system --no-create-home --home-dir "$JAGGER_HOME" --shell /usr/sbin/nologin "$JAGGER_USER"
else
    echo "System user '$JAGGER_USER' already exists, leaving it alone"
fi

# --- directories, least-privilege ownership ---
install -d -o root -g "$JAGGER_USER" -m 0750 "$JAGGER_ETC"
install -d -o "$JAGGER_USER" -g "$JAGGER_USER" -m 0750 "$JAGGER_LOG"
install -d -o "$JAGGER_USER" -g "$JAGGER_USER" -m 0750 \
    "$JAGGER_HOME/signedmetadata" \
    "$JAGGER_HOME/application/cache" \
    "$JAGGER_HOME/application/logs" \
    "$JAGGER_HOME/application/models/Proxies" \
    "$JAGGER_HOME/app4/writable"
# logos/ is served directly by Apache (site logo uploads), so it needs to
# stay readable by www-data as well as writable by jagger.
install -d -o "$JAGGER_USER" -g www-data -m 0750 "$JAGGER_HOME/logos"

# --- config: generated once into /etc/jagger, symlinked into the source
#     tree, never overwritten on upgrade ---
declare -A CONFIG_FILES=(
    [config.php]=config-default.php
    [config_rr.php]=config_rr-default.php
    [database.php]=database-default.php
    [email.php]=email-default.php
    [memcached.php]=memcached-default.php
)

for target in "${!CONFIG_FILES[@]}"; do
    template="${CONFIG_FILES[$target]}"
    dest="$JAGGER_ETC/$target"
    if [ ! -f "$dest" ]; then
        echo "Generating $dest from $template"
        cp "$JAGGER_HOME/application/config/$template" "$dest"
        # Replace only the values that are actually secrets and can be
        # generated unattended. database.php's CHANGEME placeholders are
        # left for the admin -- there's no safe default database name/user
        # to invent -- but a warning is printed below.
        case "$target" in
            config.php)
                sed -i "s/\$config\['encryption_key'\] = 'CHANGEME';/\$config['encryption_key'] = '$(gen_secret)';/" "$dest"
                ;;
            config_rr.php)
                sed -i "s/\$config\['syncpass'\] = 'CHANGEME';/\$config['syncpass'] = '$(gen_secret)';/" "$dest"
                ;;
        esac
        chown root:"$JAGGER_USER" "$dest"
        chmod 0640 "$dest"
    else
        echo "$dest already exists, leaving it alone (upgrade-safe)"
    fi

    link="$JAGGER_HOME/application/config/$target"
    if [ ! -L "$link" ] || [ "$(readlink "$link")" != "$dest" ]; then
        ln -sf "$dest" "$link"
    fi
done

if grep -q "CHANGEME" "$JAGGER_ETC/database.php" 2>/dev/null; then
    echo ""
    echo "!!! $JAGGER_ETC/database.php still has CHANGEME placeholders."
    echo "!!! Edit it with your real database host/username/password/name"
    echo "!!! before starting jagger-php-fpm."
fi
if grep -q "CHANGEME" "$JAGGER_ETC/config_rr.php" 2>/dev/null; then
    echo "!!! $JAGGER_ETC/config_rr.php still has CHANGEME rabbitmq credentials"
    echo "!!! if you use RabbitMQ -- edit them, or leave rabbitmq.enabled=false."
fi

# --- ownership of the application source tree itself: read-only for the
#     service user except the specific writable paths already handled
#     above (application/cache, application/logs, application/models/
#     Proxies, signedmetadata, logos, app4/writable). ---
chown -R root:"$JAGGER_USER" "$JAGGER_HOME"
find "$JAGGER_HOME" -type d -exec chmod 0750 {} +
find "$JAGGER_HOME" -type f -exec chmod 0640 {} +
chown -R "$JAGGER_USER":"$JAGGER_USER" \
    "$JAGGER_HOME/application/cache" \
    "$JAGGER_HOME/application/logs" \
    "$JAGGER_HOME/application/models/Proxies" \
    "$JAGGER_HOME/signedmetadata" \
    "$JAGGER_HOME/app4/writable"
chown -R "$JAGGER_USER":www-data "$JAGGER_HOME/logos"

echo "Provisioning complete."
