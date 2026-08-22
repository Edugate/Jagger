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
JAGGER_SESSIONS="${JAGGER_SESSIONS:-/var/lib/jagger/sessions}"
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
# PHP session files: explicitly pointed here (php-fpm pool's
# session.save_path, and -d session.save_path=... on both systemd worker
# units) rather than relying on whatever Debian/Ubuntu's stock php.ini
# defaults to -- CI3's session library is autoloaded on every request
# (application/config/autoload.php), web and CLI alike, so this needs to
# exist and be writable by jagger regardless.
install -d -o "$JAGGER_USER" -g "$JAGGER_USER" -m 0750 "$JAGGER_SESSIONS"
install -d -o "$JAGGER_USER" -g "$JAGGER_USER" -m 0750 \
    "$JAGGER_HOME/signedmetadata" \
    "$JAGGER_HOME/application/cache" \
    "$JAGGER_HOME/application/logs" \
    "$JAGGER_HOME/application/models/Proxies" \
    "$JAGGER_HOME/app4/writable/cache" \
    "$JAGGER_HOME/app4/writable/logs" \
    "$JAGGER_HOME/app4/writable/session" \
    "$JAGGER_HOME/app4/writable/debugbar" \
    "$JAGGER_HOME/app4/writable/uploads"
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
    link="$JAGGER_HOME/application/config/$target"

    if [ ! -f "$dest" ]; then
        if [ -f "$link" ] && [ ! -L "$link" ]; then
            # A real config file already exists at the old (pre-packaging)
            # location -- this is an existing production deployment being
            # migrated onto the new layout, not a fresh install. Preserve
            # it (their real base_url/encryption_key/Shib mapping/etc.)
            # instead of clobbering it with a freshly-generated template:
            # `ln -sf` below would otherwise silently overwrite this file
            # with a symlink and discard its contents.
            echo "Found existing $link -- migrating it to $dest instead of generating a fresh template"
            mv "$link" "$dest"
        else
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
        fi
        chown root:"$JAGGER_USER" "$dest"
        chmod 0640 "$dest"
    else
        echo "$dest already exists, leaving it alone (upgrade-safe)"
    fi

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
#
# .git is deliberately excluded from all three of the following: when
# JAGGER_HOME is a live git checkout (the install.sh path -- docs/UPGRADE.md
# runs `git pull` there), locking .git down as root would break that `git
# pull` for whichever non-root user is expected to run it.
#
# Files use a *relative* chmod (u+rw,g+r,g-w,o-rwx) rather than an absolute
# 0640: an absolute chmod would strip the executable bit from install.sh,
# application/doctrine, and this script itself, breaking `./doctrine
# orm:schema-tool:...` (docs/INSTALL.md, docs/UPGRADE.md) and making
# install.sh non-re-runnable after its first execution.
find "$JAGGER_HOME" -mindepth 1 -not -path "$JAGGER_HOME/.git" -not -path "$JAGGER_HOME/.git/*" \
    -exec chown root:"$JAGGER_USER" {} +
find "$JAGGER_HOME" -mindepth 1 -not -path "$JAGGER_HOME/.git" -not -path "$JAGGER_HOME/.git/*" \
    -type d -exec chmod 0750 {} +
find "$JAGGER_HOME" -mindepth 1 -not -path "$JAGGER_HOME/.git" -not -path "$JAGGER_HOME/.git/*" \
    -type f -exec chmod u+rw,g+r,g-w,o-rwx {} +
chown -R "$JAGGER_USER":"$JAGGER_USER" \
    "$JAGGER_HOME/application/cache" \
    "$JAGGER_HOME/application/logs" \
    "$JAGGER_HOME/application/models/Proxies" \
    "$JAGGER_HOME/signedmetadata" \
    "$JAGGER_HOME/app4/writable"
chown -R "$JAGGER_USER":www-data "$JAGGER_HOME/logos"

echo "Provisioning complete."
