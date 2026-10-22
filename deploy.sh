#!/usr/bin/env bash
#
# deploy.sh - Automated installer for Jagger (Debian 13 / Ubuntu 24.04+ LTS)
#
# This script performs, end-to-end, everything described in the manual
# installation guide in README.md: hostname/mirror setup, MySQL, Apache,
# Let's Encrypt, PyFF, XMLSecTool, the Jagger application itself, its
# database, its config files, the PHP 8.4 compatibility fixes, the Doctrine
# schema, and the final HTTPS virtual host.
#
# Usage:
#   sudo ./deploy.sh                # interactive
#   sudo ./deploy.sh -y             # non-interactive, reads *_ vars below
#   sudo ./deploy.sh --help
#
# Non-interactive / default-override environment variables:
#   JAGGER_FQDN            e.g. jagger.example.org            (required for -y)
#   JAGGER_ADMIN_EMAIL     e.g. admin@example.org             (required for -y)
#   JAGGER_HOSTNAME        short hostname (default: first label of the FQDN)
#   JAGGER_DEPLOY_MODE     "testing" (self-signed cert, default) or "production"
#                          (real Let's Encrypt cert - needs a public domain
#                          that already resolves to this server)
#   JAGGER_DEPLOY_KIND     "fresh" (default when no existing install is found)
#                          or "migrate" (found automatically when one is -
#                          only meaningful for -y) - migrate backs up the
#                          existing database, redeploys fresh code, then
#                          restores the database into it
#   JAGGER_MIGRATE_CONFIG  "existing" (default - reuse the detected FQDN,
#                          admin email, DB settings, and logo) or "new"
#                          (answer the usual setup questions fresh); only
#                          used when migrating
#   JAGGER_DB_NAME         (default: rr3)
#   JAGGER_DB_USER         (default: rr3user)
#   JAGGER_DB_PASS         (default: randomly generated)
#   JAGGER_MYSQL_ROOT_PASS (default: randomly generated)
#   JAGGER_LOGO_PATH       path to a PNG logo to install (optional)
#   JAGGER_USE_MIRROR      "1" to enable the alternate APT mirror (default: 0)
#
# Must be run as root on a fresh Debian 13 / Ubuntu 24.04+ LTS host.
#
set -Eeuo pipefail

# ----------------------------------------------------------------------------
# Globals
# ----------------------------------------------------------------------------
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
LOG_FILE="/var/log/jagger-deploy-$(date +%Y%m%d-%H%M%S).log"
STEP_CURRENT=0
STEP_TIMES=()
# STEP_TOTAL/STEP_WEIGHTS are set for real just before the run_step sequence
# starts (Main, below), once DEPLOY_KIND is known - migrate mode inserts two
# extra steps (a backup and a restore) that fresh mode doesn't have. See the
# comment there for what the weight values mean.
STEP_TOTAL=0
STEP_WEIGHTS=()
ASSUME_YES=0
SECONDS=0

# ----------------------------------------------------------------------------
# Colors (disabled automatically for non-tty / dumb terminals)
# ----------------------------------------------------------------------------
if [[ -t 1 ]] && command -v tput >/dev/null 2>&1 && [[ "$(tput colors 2>/dev/null || echo 0)" -ge 8 ]]; then
    BOLD=$(tput bold); DIM=$(tput dim); RESET=$(tput sgr0)
    RED=$(tput setaf 1); GREEN=$(tput setaf 2); YELLOW=$(tput setaf 3)
    BLUE=$(tput setaf 4); CYAN=$(tput setaf 6); WHITE=$(tput setaf 7)
else
    BOLD=""; DIM=""; RESET=""; RED=""; GREEN=""; YELLOW=""; BLUE=""; CYAN=""; WHITE=""
fi

# ----------------------------------------------------------------------------
# Glyphs - the bare Linux VT console (TERM=linux, e.g. a hypervisor's console
# tab with no GUI terminal emulator attached) generally lacks the Unicode
# block-drawing/braille glyphs, and silently collapses several different
# characters onto the same fallback glyph (which is what made the progress
# bar look permanently "full"). Detect that and fall back to plain ASCII.
# ----------------------------------------------------------------------------
if [[ "${TERM:-}" != "linux" ]] && locale charmap 2>/dev/null | grep -qi 'utf-*8'; then
    CHECK="✔"; CROSS="✘"; ARROW="➤"; BAR_FULL="█"; BAR_EMPTY="░"
    SPIN_FRAMES='⠋⠙⠹⠸⠼⠴⠦⠧⠇⠏'
    HR_CHAR="─"
else
    CHECK="OK"; CROSS="X"; ARROW=">"; BAR_FULL="#"; BAR_EMPTY="-"
    SPIN_FRAMES='|/-\'
    HR_CHAR="-"
fi

# ----------------------------------------------------------------------------
# Small helpers
# ----------------------------------------------------------------------------
banner() {
    printf "%s\n" "${CYAN}${BOLD}"
    if [[ "$BAR_FULL" == "█" ]]; then
        cat <<'EOF'
     ██╗ █████╗  ██████╗  ██████╗ ███████╗██████╗
     ██║██╔══██╗██╔════╝ ██╔════╝ ██╔════╝██╔══██╗
     ██║███████║██║  ███╗██║  ███╗█████╗  ██████╔╝
██   ██║██╔══██║██║   ██║██║   ██║██╔══╝  ██╔══██╗
╚█████╔╝██║  ██║╚██████╔╝╚██████╔╝███████╗██║  ██║
 ╚════╝ ╚═╝  ╚═╝ ╚═════╝  ╚═════╝ ╚══════╝╚═╝  ╚═╝
EOF
    else
        cat <<'EOF'
     _   _   ____   ____ _____ ____
    | | / \ / ___| / ___| ____|  _ \
 _  | |/ _ \| |  _| |  _|  _| | |_) |
| |_| / ___ \ |_| | |_| | |___|  _ <
 \___/_/   \_\____|\____|_____|_| \_\
EOF
    fi
    printf "%s\n" "${RESET}${DIM}          Automated deployment - Debian 13 / Ubuntu 24.04+${RESET}"
    echo
}

hr() {
    local line
    printf -v line '%*s' 68 ''
    printf "%s\n" "${DIM}${line// /$HR_CHAR}${RESET}"
}

log_only() { echo "$(date '+%H:%M:%S') $*" >>"$LOG_FILE"; }

die() {
    echo
    echo "${RED}${BOLD}${CROSS:-x} $*${RESET}" >&2
    exit 1
}

require_root() {
    [[ "${EUID}" -eq 0 ]] || die "This script must be run as root. Try: sudo ./deploy.sh"
}

format_hms() {
    local t=$1
    local h=$((t / 3600)) m=$(((t % 3600) / 60)) s=$((t % 60))
    if ((h > 0)); then
        printf "%d:%02d:%02d" "$h" "$m" "$s"
    else
        printf "%02d:%02d" "$m" "$s"
    fi
}

# ----------------------------------------------------------------------------
# Prompt helpers
# ----------------------------------------------------------------------------
ask() {
    # ask "Prompt text" "default" resultvar ["validation_regex"]
    local prompt="$1" default="${2:-}" __var="$3" re="${4:-}"
    local input

    if [[ "$ASSUME_YES" -eq 1 ]]; then
        [[ -n "$default" ]] || die "Missing required value for: ${prompt}. Set the matching JAGGER_* env var or run interactively."
        printf -v "$__var" '%s' "$default"
        return
    fi

    while true; do
        if [[ -n "$default" ]]; then
            printf "%s${DIM} [%s]${RESET}: " "$prompt" "$default"
        else
            printf "%s: " "$prompt"
        fi
        read -r input
        input="${input:-$default}"
        if [[ -z "$input" ]]; then
            echo "  ${YELLOW}This value is required.${RESET}"
            continue
        fi
        if [[ -n "$re" && ! "$input" =~ $re ]]; then
            echo "  ${YELLOW}That doesn't look right, please try again.${RESET}"
            continue
        fi
        printf -v "$__var" '%s' "$input"
        break
    done
}

gen_password() { openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c 24; }

ask_secret() {
    # ask_secret "Prompt text" resultvar default_env_value
    local prompt="$1" __var="$2" default="${3:-}"
    local p1 p2

    if [[ "$ASSUME_YES" -eq 1 ]]; then
        printf -v "$__var" '%s' "${default:-$(gen_password)}"
        return
    fi

    echo "${prompt} ${DIM}(leave blank to auto-generate a strong password)${RESET}"
    read -rs -p "  > " p1; echo
    if [[ -z "$p1" ]]; then
        p1="$(gen_password)"
        echo "  ${GREEN}Generated:${RESET} ${p1}"
    else
        read -rs -p "  confirm > " p2; echo
        if [[ "$p1" != "$p2" ]]; then
            echo "  ${RED}Passwords did not match - generating a random one instead.${RESET}"
            p1="$(gen_password)"
            echo "  ${GREEN}Generated:${RESET} ${p1}"
        fi
    fi
    printf -v "$__var" '%s' "$p1"
}

ask_optional() {
    # ask_optional "Prompt text" resultvar   (blank input is accepted)
    local prompt="$1" __var="$2" input
    if [[ "$ASSUME_YES" -eq 1 ]]; then
        printf -v "$__var" '%s' ""
        return
    fi
    printf "%s${DIM} [blank to skip]${RESET}: " "$prompt"
    read -r input
    printf -v "$__var" '%s' "$input"
}

confirm() {
    # confirm "Prompt" default(Y|N) -> returns 0 for yes
    local prompt="$1" default="${2:-N}" reply hint="y/N"
    [[ "$default" =~ ^[Yy]$ ]] && hint="Y/n"
    if [[ "$ASSUME_YES" -eq 1 ]]; then
        [[ "$default" =~ ^[Yy]$ ]]
        return
    fi
    read -r -p "$(printf '%s [%s]: ' "$prompt" "$hint")" reply
    reply="${reply:-$default}"
    [[ "$reply" =~ ^[Yy] ]]
}

print_kv() { printf "  ${DIM}%-24s${RESET} %s\n" "$1" "$2"; }

# ----------------------------------------------------------------------------
# Progress bar / spinner / step runner
# ----------------------------------------------------------------------------
draw_progress() {
    local current=$1 total=$2 width=30
    local filled=$(( current * width / total ))
    local empty=$(( width - filled ))
    local bar=""
    [[ $filled -gt 0 ]] && bar+=$(printf '%*s' "$filled" '' | tr ' ' "$BAR_FULL")
    [[ $empty -gt 0 ]] && bar+=$(printf '%*s' "$empty" '' | tr ' ' "$BAR_EMPTY")
    local pct=$(( current * 100 / total ))
    printf "${CYAN}[%s]${RESET} %3d%% ${DIM}step %d/%d${RESET}" "$bar" "$pct" "$current" "$total"
}

spin_wait() {
    local pid=$1 label=$2
    local i=0 start=$SECONDS
    while kill -0 "$pid" 2>/dev/null; do
        local frame="${SPIN_FRAMES:i%${#SPIN_FRAMES}:1}"
        i=$((i + 1))
        local elapsed=$(( SECONDS - start ))
        printf "\r  ${CYAN}%s${RESET} %s ${DIM}(%s)${RESET}%*s" "$frame" "$label" "$(format_hms "$elapsed")" 10 ""
        sleep 0.1
    done
}

run_step() {
    # run_step "Human description" function_name
    local desc="$1" fn="$2"
    local completed=$STEP_CURRENT
    STEP_CURRENT=$((STEP_CURRENT + 1))
    echo
    draw_progress "$STEP_CURRENT" "$STEP_TOTAL"
    if [[ $completed -gt 0 && $SECONDS -gt 0 ]]; then
        local weight_done=0 weight_left=0 i
        for ((i = 0; i < completed; i++)); do weight_done=$(( weight_done + STEP_WEIGHTS[i] )); done
        for ((i = completed; i < STEP_TOTAL; i++)); do weight_left=$(( weight_left + STEP_WEIGHTS[i] )); done
        if [[ $weight_done -gt 0 ]]; then
            local remaining=$(( SECONDS * weight_left / weight_done ))
            printf " ${DIM}- ETA ~%s${RESET}" "$(format_hms "$remaining")"
        fi
    fi
    echo
    echo "${BOLD}${WHITE}${ARROW} ${desc}${RESET}"
    log_only "==== STEP ${STEP_CURRENT}/${STEP_TOTAL}: ${desc} ===="

    local t0=$SECONDS rc=0
    ( "$fn" ) >>"$LOG_FILE" 2>&1 &
    local pid=$!
    spin_wait "$pid" "$desc"
    wait "$pid" || rc=$?
    local dt=$(( SECONDS - t0 ))

    if [[ $rc -eq 0 ]]; then
        printf "\r  ${GREEN}%s${RESET} %s ${DIM}done in %s${RESET}%*s\n" "$CHECK" "$desc" "$(format_hms "$dt")" 15 ""
        STEP_TIMES+=("${desc}|${dt}")
    else
        printf "\r  ${RED}%s${RESET} %s ${DIM}failed after %s${RESET}%*s\n" "$CROSS" "$desc" "$(format_hms "$dt")" 15 ""
        echo
        echo "${RED}${BOLD}${OP_NAME:-Deployment} stopped at step ${STEP_CURRENT}/${STEP_TOTAL}: ${desc}${RESET}"
        echo "${DIM}Last 25 lines of ${LOG_FILE}:${RESET}"
        hr
        tail -n 25 "$LOG_FILE" || true
        hr
        # A sourcing script (e.g. update.sh) can define on_step_failure to
        # print its own extra context here (backup location, etc) before
        # this exits.
        declare -f on_step_failure >/dev/null && on_step_failure
        echo "Fix the issue above and re-run ${OP_SCRIPT:-./deploy.sh} - earlier steps are safe to repeat."
        exit "$rc"
    fi
}

# ----------------------------------------------------------------------------
# Detection
# ----------------------------------------------------------------------------
http_get() {
    local url=$1
    if command -v curl >/dev/null 2>&1; then
        curl -fsS --max-time 3 "$url" 2>/dev/null
    elif command -v wget >/dev/null 2>&1; then
        wget -qO- --timeout=3 "$url" 2>/dev/null
    else
        return 1
    fi
}

detect_private_ip() {
    local ip
    ip=$(ip -4 route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if ($i=="src") print $(i+1)}')
    [[ -z "$ip" ]] && ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    echo "${ip:-unknown}"
}

detect_public_ip() {
    local ip
    ip=$(http_get "https://ifconfig.me" || http_get "https://api.ipify.org" || http_get "https://icanhazip.com" || true)
    ip=$(echo "$ip" | tr -d '[:space:]')
    echo "${ip:-unavailable}"
}

# ----------------------------------------------------------------------------
# Deployment step implementations
# ----------------------------------------------------------------------------
step_hostname() {
    grep -qF "$FQDN" /etc/hosts || echo "${PRIVATE_IP} ${FQDN} ${SHORT_HOSTNAME}" >>/etc/hosts
    hostnamectl set-hostname "$SHORT_HOSTNAME"
}

step_apt_mirror() {
    if [[ "$USE_MIRROR" -ne 1 ]]; then
        echo "Alternate mirror not requested - skipping."
        return 0
    fi
    if [[ "$OS_ID" == "debian" ]]; then
        cat >/etc/apt/sources.list.d/deploy-mirror.sources <<EOF
Types: deb deb-src
URIs: ${MIRROR_URL}/debian/
Suites: ${OS_CODENAME} ${OS_CODENAME}-updates ${OS_CODENAME}-backports
Components: main contrib non-free non-free-firmware
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg

Types: deb deb-src
URIs: ${MIRROR_URL}/debian-security/
Suites: ${OS_CODENAME}-security
Components: main contrib non-free non-free-firmware
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg
EOF
    else
        cat >/etc/apt/sources.list.d/deploy-mirror.sources <<EOF
Types: deb deb-src
URIs: ${MIRROR_URL}/ubuntu/
Suites: ${OS_CODENAME} ${OS_CODENAME}-updates ${OS_CODENAME}-backports
Components: main restricted universe multiverse
Signed-By: /usr/share/keyrings/ubuntu-archive-keyring.gpg

Types: deb deb-src
URIs: ${MIRROR_URL}/ubuntu/
Suites: ${OS_CODENAME}-security
Components: main restricted universe multiverse
Signed-By: /usr/share/keyrings/ubuntu-archive-keyring.gpg
EOF
    fi
}

step_apt_upgrade() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get update
    apt-get -y upgrade --no-install-recommends
}

step_base_deps() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y fail2ban nano wget curl ca-certificates openssl ntpsec git unzip rsync --no-install-recommends
}

step_mysql() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y default-mysql-server --no-install-recommends
    systemctl enable --now mysql 2>/dev/null || systemctl enable --now mariadb

    # A previous (possibly partial) run may have already switched root from
    # socket auth to a password. Detect whichever still works before we
    # overwrite the credentials file, so re-running this script is safe.
    local auth_cmd="mysql -u root"
    if ! mysql -u root -e "SELECT 1;" >/dev/null 2>&1; then
        if [[ -f "$MYSQL_ROOT_CNF" ]] && mysql --defaults-extra-file="$MYSQL_ROOT_CNF" -e "SELECT 1;" >/dev/null 2>&1; then
            auth_cmd="mysql --defaults-extra-file=$MYSQL_ROOT_CNF"
        else
            echo "Cannot authenticate to MySQL as root (neither socket auth nor ${MYSQL_ROOT_CNF} works)."
            echo "If root already has a password from an earlier run, put it in ${MYSQL_ROOT_CNF} under [client] password= and re-run."
            return 1
        fi
    fi

    install -m 600 /dev/null "$MYSQL_ROOT_CNF"
    cat >"$MYSQL_ROOT_CNF" <<EOF
[client]
user=root
password=${MYSQL_ROOT_PASS}
EOF

    $auth_cmd <<SQL
ALTER USER 'root'@'localhost' IDENTIFIED BY '${MYSQL_ROOT_PASS}';
DELETE FROM mysql.user WHERE User='';
DELETE FROM mysql.db WHERE Db='test' OR Db LIKE 'test\\_%';
DROP DATABASE IF EXISTS test;
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost','127.0.0.1','::1');
FLUSH PRIVILEGES;
SQL
}

step_apache_http() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y apache2

    mkdir -p "/var/www/html/${FQDN}"
    chown -R www-data:www-data "/var/www/html/${FQDN}"
    echo "<h1>It Works! Ready for Let's Encrypt.</h1>" >"/var/www/html/${FQDN}/index.html"

    cat >"/etc/apache2/sites-available/${FQDN}.conf" <<EOF
<VirtualHost *:80>
    ServerName ${FQDN}
    ServerAdmin ${ADMIN_EMAIL}
    DocumentRoot /var/www/html/${FQDN}
</VirtualHost>
EOF

    a2enmod ssl rewrite headers alias include negotiation socache_shmcb >/dev/null
    a2ensite "${FQDN}.conf" >/dev/null || true
    systemctl restart apache2
}

step_tls() {
    if [[ "$DEPLOY_MODE" == "production" ]]; then
        export DEBIAN_FRONTEND=noninteractive
        apt-get install -y certbot python3-certbot-apache
        certbot certonly --apache -d "$FQDN" -m "$ADMIN_EMAIL" --agree-tos --non-interactive
        certbot renew --dry-run
    else
        mkdir -p "$(dirname "$CERT_FILE")"
        openssl req -x509 -nodes -days 825 -newkey rsa:2048 \
            -keyout "$CERT_KEY" -out "$CERT_FILE" \
            -subj "/CN=${FQDN}" \
            -addext "subjectAltName=DNS:${FQDN}"
    fi
}

step_pyff() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y python3-venv python3-pip libxml2-dev libxslt1-dev pkg-config
    mkdir -p /opt/pyff
    chown root:root /opt/pyff
    [[ -x /opt/pyff/venv/bin/python ]] || python3 -m venv /opt/pyff/venv
    /opt/pyff/venv/bin/pip install --upgrade pip
    /opt/pyff/venv/bin/pip install pyff
    ln -sf /opt/pyff/venv/bin/pyff /usr/local/bin/pyff
    ln -sf /opt/pyff/venv/bin/pyffd /usr/local/bin/pyffd
}

step_xmlsectool() {
    local ver="4.0.0"
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y default-jdk

    mkdir -p /opt/xmlsectool
    cd /opt/xmlsectool

    if [[ ! -d "xmlsectool-${ver}" ]]; then
        local tries=0
        until wget -c "https://shibboleth.net/downloads/tools/xmlsectool/${ver}/xmlsectool-${ver}-bin.zip"; do
            tries=$((tries + 1))
            [[ $tries -ge 5 ]] && { echo "Download failed after 5 attempts."; return 1; }
            echo "Retrying download (attempt ${tries})..."
            sleep 5
        done
        unzip -o "xmlsectool-${ver}-bin.zip"
        rm -f "xmlsectool-${ver}-bin.zip"
        chown -R root:root /opt/xmlsectool
    fi

    # The 4.0.0 binary release has no standalone xmlsectool.jar - it only
    # ships lib/*.jar plus its own xmlsectool.sh, which builds a classpath
    # from that lib/ directory and invokes the main class directly. We do
    # the same via java's wildcard classpath instead of relying on
    # xmlsectool.sh, which additionally requires $JAVA_HOME to be set.
    cat >/usr/local/bin/xmlsectool <<EOF
#!/bin/bash
exec java -cp '/opt/xmlsectool/xmlsectool-${ver}/lib/*' -Dnet.shibboleth.tool.xmlsectool.XMLSecTool.home='/opt/xmlsectool/xmlsectool-${ver}' net.shibboleth.tool.xmlsectool.XMLSecTool "\$@"
EOF
    chmod +x /usr/local/bin/xmlsectool

    # There's no --help/-help option in this tool (it uses JCommander with
    # only functional options like --inFile/--xsd/--verifySignature), so
    # invoking it to "verify" would just exit non-zero on a missing-argument
    # error that looks identical to a real installation failure. Check the
    # files directly instead: that the real tool jar landed where the
    # wrapper expects it, and that the wrapper itself is on PATH.
    [[ -f "/opt/xmlsectool/xmlsectool-${ver}/lib/xmlsectool-${ver}.jar" ]] || {
        echo "xmlsectool-${ver}.jar not found under lib/ after extraction."
        return 1
    }
    command -v xmlsectool >/dev/null || {
        echo "/usr/local/bin/xmlsectool was not created correctly."
        return 1
    }
}

step_install_jagger() {
    export DEBIAN_FRONTEND=noninteractive
    apt-get install -y curl php php-common php-gd php-curl php-mysql php-intl php-xml \
        php-mbstring php-soap php-bcmath php-cli php-zip php-gearman php-apcu php-memcached \
        default-jdk gearman-job-server --no-install-recommends

    if ! command -v composer >/dev/null 2>&1; then
        curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
        php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
        rm -f /tmp/composer-setup.php
    fi

    local ci_ver="3.1.13"
    rm -rf /opt/codeigniter "/opt/codeigniter-${ci_ver}.tar.gz"
    wget -O "/opt/codeigniter-${ci_ver}.tar.gz" "https://github.com/bcit-ci/CodeIgniter/archive/refs/tags/${ci_ver}.tar.gz"
    mkdir -p /opt/codeigniter
    tar -xzf "/opt/codeigniter-${ci_ver}.tar.gz" -C /opt/codeigniter --strip-components=1
    grep -q "CI_VERSION" /opt/codeigniter/system/core/CodeIgniter.php

    rm -rf /opt/rr3
    if [[ "$USE_LOCAL_CHECKOUT" -eq 1 ]]; then
        mkdir -p /opt/rr3
        rsync -a --exclude='.git' "${SCRIPT_DIR}/" /opt/rr3/
    else
        git clone https://github.com/Edugate/Jagger /opt/rr3
    fi

    sed -i 's#"mtdowling/cron-expression": "1.1.\*",#"dragonmantank/cron-expression": "^3.0",#' /opt/rr3/application/composer.json
    sed -i 's#"laminas/laminas-permissions-acl": "2.7.2 || 2.9.0",#"laminas/laminas-permissions-acl": "^2.18.0",#' /opt/rr3/application/composer.json

    (cd /opt/rr3/application && composer install --no-interaction)

    cp /opt/codeigniter/index.php /opt/rr3/index.php
    # NOTE: index.php reassigns $system_path a second time inside its own
    # realpath() normalization block (e.g. "$system_path = $_temp.DIRECTORY_SEPARATOR;").
    # Match only the literal stock default ('system') so that line isn't
    # clobbered too - if it is, CI3's own trailing-separator gets stripped
    # and BASEPATH ends up missing a "/" before "core/CodeIgniter.php".
    sed -i "s#^\(\s*\)\\\$system_path\s*=\s*'system';#\1\\\$system_path = '/opt/codeigniter/system';#" /opt/rr3/index.php
}

step_jagger_db() {
    # In migrate mode the target database may still have all its old tables
    # (nothing has dropped them yet - step_migrate_backup only read from it).
    # orm:schema-tool:create needs a genuinely empty schema or it errors on
    # "table already exists", so drop first here; step_migrate_backup's dump
    # already has everything, and step_migrate_restore reloads it afterward.
    if [[ "$DEPLOY_KIND" == "migrate" ]]; then
        mysql --defaults-extra-file="$MYSQL_ROOT_CNF" -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;"
    fi
    mysql --defaults-extra-file="$MYSQL_ROOT_CNF" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
}

step_configure_jagger() {
    mkdir -p /var/log/rr3
    chown -R www-data:www-data /var/log/rr3
    mkdir -p /opt/rr3/application/models/Proxies
    chown -R www-data:www-data /opt/rr3/application

    cd /opt/rr3/application/config
    cp config-default.php config.php
    cp config_rr-default.php config_rr.php
    cp database-default.php database.php
    cp email-default.php email.php
    cp memcached-default.php memcached.php

    local encryption_key syncpass
    encryption_key=$(openssl rand -base64 128 | tr -dc 'A-Za-z0-9' | head -c 64)
    syncpass=$(openssl rand -base64 128 | tr -dc 'A-Za-z0-9' | head -c 64)

    sed -i "s#^\\\$config\['base_url'\]\s*=.*#\\\$config['base_url'] = 'https://${FQDN}/rr3';#" config.php
    sed -i "s#^\\\$config\['index_page'\]\s*=.*#\\\$config['index_page'] = '';#" config.php
    sed -i "s#^\\\$config\['log_threshold'\]\s*=.*#\\\$config['log_threshold'] = 1;#" config.php
    sed -i "s#^\\\$config\['log_path'\]\s*=.*#\\\$config['log_path'] = '/var/log/rr3/';#" config.php
    sed -i "s#^\\\$config\['encryption_key'\]\s*=.*#\\\$config['encryption_key'] = '${encryption_key}';#" config.php

    sed -i "s#^\\\$config\['rr_setup_allowed'\]\s*=.*#\\\$config['rr_setup_allowed'] = TRUE;#" config_rr.php
    sed -i "s#^\\\$config\['syncpass'\]\s*=.*#\\\$config['syncpass'] = '${syncpass}';#" config_rr.php
    sed -i "s#^\\\$config\['gearman'\]\s*=.*#\\\$config['gearman'] = TRUE;#" config_rr.php
    sed -i "/^\\\$config\['nameids'\] = array(\$/,/^\s*);\s*\$/d" config_rr.php

    if [[ -n "$LOGO_PATH" ]]; then
        cp "$LOGO_PATH" "/opt/rr3/images/$(basename "$LOGO_PATH")"
        sed -i "s#^\\\$config\['site_logo'\]\s*=.*#\\\$config['site_logo'] = '$(basename "$LOGO_PATH")';#" config_rr.php
    fi

    sed -i "s#^\\\$db\['default'\]\['username'\]\s*=.*#\\\$db['default']['username'] = '${DB_USER}';#" database.php
    sed -i "s#^\\\$db\['default'\]\['password'\]\s*=.*#\\\$db['default']['password'] = '${DB_PASS}';#" database.php
    sed -i "s#^\\\$db\['default'\]\['database'\]\s*=.*#\\\$db['default']['database'] = '${DB_NAME}';#" database.php
    sed -i "s#^\\\$db\['default'\]\['dsn'\]\s*=.*#\\\$db['default']['dsn']      = 'mysql:host=127.0.0.1;port=3306;dbname=${DB_NAME}';#" database.php

    chown www-data:www-data config.php config_rr.php database.php email.php memcached.php
}

step_php_compat() {
    sed -i "/^\s*E_STRICT\s*=>/d" /opt/codeigniter/system/core/Exceptions.php
    sed -i "s#define('ENVIRONMENT', .*);#define('ENVIRONMENT', isset(\$_SERVER['CI_ENV']) ? \$_SERVER['CI_ENV'] : 'production');#" /opt/rr3/index.php

    local ini
    for ini in /etc/php/*/apache2/php.ini /etc/php/*/cli/php.ini; do
        [[ -f "$ini" ]] || continue
        if grep -q "^pcre.jit" "$ini"; then
            sed -i "s/^pcre.jit\s*=.*/pcre.jit=0/" "$ini"
        elif grep -q "^;pcre.jit" "$ini"; then
            sed -i "s/^;pcre.jit\s*=.*/pcre.jit=0/" "$ini"
        else
            echo "pcre.jit=0" >>"$ini"
        fi
    done
    systemctl restart apache2
}

step_doctrine() {
    cd /opt/rr3/application
    chmod +x doctrine
    ./doctrine
    ./doctrine orm:schema-tool:create
    ./doctrine orm:generate-proxies
    chown -R www-data:www-data /opt/rr3/application/models/Proxies
}

step_finalize_vhost() {
    local conf="/etc/apache2/sites-available/${FQDN}.conf"
    cat >"$conf" <<EOF
<VirtualHost *:80>
    ServerName ${FQDN}
    ServerAdmin ${ADMIN_EMAIL}
    DocumentRoot /var/www/html/${FQDN}
    Redirect permanent "/" "https://${FQDN}/"
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerName ${FQDN}
    ServerAdmin ${ADMIN_EMAIL}

    CustomLog \${APACHE_LOG_DIR}/${FQDN}-access.log combined
    ErrorLog \${APACHE_LOG_DIR}/${FQDN}-error.log

    DocumentRoot /var/www/html/${FQDN}

    # ${DEPLOY_MODE^} certificate
    SSLCertificateFile ${CERT_FILE}
    SSLCertificateKeyFile ${CERT_KEY}
${SSL_EXTRA_CONF}

    # Jagger Specific Security Headers
    <IfModule headers_module>
       Header set X-Frame-Options DENY
       Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    </IfModule>

    # Jagger Application Routing
    Alias /rr3 /opt/rr3
    <Directory /opt/rr3>
       Require all granted
       RewriteEngine On
       RewriteBase /rr3
       RewriteCond \$1 !^(Shibboleth\.sso|index\.php|logos|signedmetadata|flags|images|app|schemas|fonts|styles|js|robots\.txt|pub|includes)
       RewriteRule ^(.*)\$ /rr3/index.php?/\$1 [L]
    </Directory>

    # Protect application directory
    <Directory /opt/rr3/application>
       Require all denied
    </Directory>
</VirtualHost>
</IfModule>
EOF
    a2ensite "${FQDN}.conf" >/dev/null || true
    a2dissite 000-default.conf >/dev/null 2>&1 || true
    apache2ctl configtest
    systemctl restart apache2
}

step_verify() {
    sleep 2
    local code
    code=$(curl -sk -o /dev/null -w '%{http_code}' "https://${FQDN}/rr3/" || echo "000")
    echo "HTTP status for https://${FQDN}/rr3/ => ${code}"
    # "000" means curl couldn't even connect; a 5xx means Apache/PHP is up
    # but Jagger itself is fatally broken (e.g. a bad CodeIgniter bootstrap
    # path) - both are real failures, not just "some response came back".
    if [[ "$code" == "000" || "$code" -ge 500 ]]; then
        echo "Jagger did not respond correctly. Check the Apache error log" \
             "(/var/log/apache2/${FQDN}-error.log) and ${LOG_FILE}."
        return 1
    fi
}

step_migrate_backup() {
    mkdir -p "$BACKUP_DIR"

    local dbcnf
    dbcnf=$(mktemp)
    chmod 600 "$dbcnf"
    cat >"$dbcnf" <<EOF
[client]
user=${OLD_DB_USER}
password=${OLD_DB_PASS}
host=127.0.0.1
EOF
    # --add-drop-table makes the restore self-sufficient: it can be loaded
    # straight over whatever schema-tool:create just built without a manual
    # DROP first, regardless of whether the schema changed in between.
    mysqldump --defaults-extra-file="$dbcnf" --add-drop-table --routines --triggers "${OLD_DB_NAME}" \
        | gzip >"$BACKUP_DIR/db.sql.gz"

    mysql --defaults-extra-file="$dbcnf" -N -B \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${OLD_DB_NAME}';" \
        >"$BACKUP_DIR/.table_count" 2>/dev/null || echo 0 >"$BACKUP_DIR/.table_count"
    rm -f "$dbcnf"

    # Preserve a custom logo, since /opt/rr3/images is about to be wiped.
    # step_migrate_restore picks this back up later regardless of which
    # config mode was chosen.
    if [[ -n "$OLD_LOGO_NAME" && "$OLD_LOGO_NAME" != "logo-default.png" && -f "/opt/rr3/images/${OLD_LOGO_NAME}" ]]; then
        cp "/opt/rr3/images/${OLD_LOGO_NAME}" "$BACKUP_DIR/${OLD_LOGO_NAME}"
        echo "$OLD_LOGO_NAME" >"$BACKUP_DIR/.logo_name"
    fi

    echo "Backed up ${OLD_DB_NAME} ($(cat "$BACKUP_DIR/.table_count") tables) to ${BACKUP_DIR}/db.sql.gz"
}

step_migrate_restore() {
    local dbcnf
    dbcnf=$(mktemp)
    chmod 600 "$dbcnf"
    cat >"$dbcnf" <<EOF
[client]
user=${DB_USER}
password=${DB_PASS}
host=127.0.0.1
EOF
    gunzip -c "$BACKUP_DIR/db.sql.gz" | mysql --defaults-extra-file="$dbcnf" "${DB_NAME}"

    local post_count pre_count
    post_count=$(mysql --defaults-extra-file="$dbcnf" -N -B \
        -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null)
    rm -f "$dbcnf"
    pre_count=$(cat "$BACKUP_DIR/.table_count" 2>/dev/null || echo 0)
    echo "Restored ${DB_NAME}: ${pre_count} table(s) backed up, ${post_count} present now."
    if [[ -z "$post_count" || "$post_count" -lt "$pre_count" ]]; then
        echo "Table count after restore (${post_count:-0}) is less than before the update (${pre_count}) - something went wrong."
        return 1
    fi

    # Only reapply the old logo when the user chose to reuse the existing
    # config. In "new config" mode they may have just picked a different
    # logo via step_configure_jagger, and this would silently overwrite
    # that choice back to the old one since restore runs after it.
    if [[ "$MIGRATE_CONFIG_MODE" == "existing" && -f "$BACKUP_DIR/.logo_name" ]]; then
        local logo_name
        logo_name=$(cat "$BACKUP_DIR/.logo_name")
        if [[ -f "$BACKUP_DIR/$logo_name" ]]; then
            cp "$BACKUP_DIR/$logo_name" "/opt/rr3/images/$logo_name"
            sed -i "s#^\\\$config\['site_logo'\]\s*=.*#\\\$config['site_logo'] = '${logo_name}';#" /opt/rr3/application/config/config_rr.php
            chown www-data:www-data "/opt/rr3/images/$logo_name"
        fi
    fi

    # step_configure_jagger always generates a fresh encryption_key/syncpass,
    # but the database just restored above may contain data encrypted with
    # the OLD key - a fresh one would silently break decrypting it. Put the
    # originals back, regardless of which config mode was chosen for
    # FQDN/DB/logo.
    [[ -n "${OLD_ENCRYPTION_KEY:-}" ]] && sed -i "s#^\\\$config\['encryption_key'\]\s*=.*#\\\$config['encryption_key'] = '${OLD_ENCRYPTION_KEY}';#" /opt/rr3/application/config/config.php
    [[ -n "${OLD_SYNCPASS:-}" ]] && sed -i "s#^\\\$config\['syncpass'\]\s*=.*#\\\$config['syncpass'] = '${OLD_SYNCPASS}';#" /opt/rr3/application/config/config_rr.php
}

# ----------------------------------------------------------------------------
# Main
# ----------------------------------------------------------------------------
for arg in "$@"; do
    case "$arg" in
        -y|--yes) ASSUME_YES=1 ;;
        -h|--help)
            sed -n '2,39p' "${BASH_SOURCE[0]}"
            exit 0
            ;;
        *) die "Unknown option: ${arg} (use --help)" ;;
    esac
done

trap 'echo; echo "${YELLOW}Interrupted.${RESET}"; exit 130' INT

clear 2>/dev/null || true
banner
require_root

# --- OS detection --------------------------------------------------------
[[ -f /etc/os-release ]] || die "Cannot find /etc/os-release - this doesn't look like Debian or Ubuntu."
# shellcheck disable=SC1091
source /etc/os-release
OS_ID="$ID"
OS_CODENAME="${VERSION_CODENAME:-}"
OS_PRETTY="$PRETTY_NAME"

if [[ "$OS_ID" != "debian" && "$OS_ID" != "ubuntu" ]]; then
    echo "${YELLOW}Warning:${RESET} this script targets Debian 13 / Ubuntu 24.04+. Detected: ${OS_PRETTY}"
    confirm "Continue anyway?" N || exit 1
fi

EXISTING_INSTALL=0
[[ -d /opt/rr3 && -f /opt/rr3/application/config/config.php && -f /opt/rr3/application/config/database.php && -f /opt/rr3/application/config/config_rr.php ]] && EXISTING_INSTALL=1

DEPLOY_KIND="fresh"
if [[ "$EXISTING_INSTALL" -eq 1 ]]; then
    echo "${YELLOW}An existing Jagger install was found at /opt/rr3.${RESET}"
    if [[ "$ASSUME_YES" -eq 1 ]]; then
        DEPLOY_KIND="${JAGGER_DEPLOY_KIND:-migrate}"
    else
        echo "  ${DIM}migrate - back up its database, redeploy fresh code, then restore the data.${RESET}"
        echo "  ${DIM}fresh   - wipe it and start over with no existing data.${RESET}"
        if confirm "Migrate the existing environment instead of a fresh install?" Y; then
            DEPLOY_KIND="migrate"
        else
            confirm "Confirm: wipe the existing install and start completely fresh?" N || exit 1
            DEPLOY_KIND="fresh"
        fi
    fi
elif [[ -d /opt/rr3 ]]; then
    echo "${YELLOW}Warning:${RESET} /opt/rr3 exists but doesn't look like a complete Jagger install (missing config files)."
    confirm "Remove it and start fresh?" N || exit 1
fi

MIGRATE_CONFIG_MODE=""
if [[ "$DEPLOY_KIND" == "migrate" ]]; then
    OLD_CONFIG_DIR="/opt/rr3/application/config"
    OLD_FQDN=$(grep -oP "base_url'\]\s*=\s*'https://\K[^/]+" "$OLD_CONFIG_DIR/config.php" || true)
    OLD_DB_NAME=$(grep -oP "\['database'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/database.php" || true)
    OLD_DB_USER=$(grep -oP "\['username'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/database.php" || true)
    OLD_DB_PASS=$(grep -oP "\['password'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/database.php" || true)
    OLD_ENCRYPTION_KEY=$(grep -oP "encryption_key'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/config.php" || true)
    OLD_SYNCPASS=$(grep -oP "syncpass'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/config_rr.php" || true)
    OLD_LOGO_NAME=$(grep -oP "site_logo'\]\s*=\s*'\K[^']+" "$OLD_CONFIG_DIR/config_rr.php" || true)
    # Scoped to the Jagger vhost specifically (named after its FQDN, same as
    # step_apache_http/step_finalize_vhost write) - grepping the whole
    # sites-available/ directory can instead pick up Ubuntu's own stock
    # "ServerAdmin webmaster@localhost" from 000-default.conf, which isn't
    # a real address and Let's Encrypt will reject at registration time.
    OLD_ADMIN_EMAIL=$(grep -hoP "ServerAdmin\s+\K\S+" "/etc/apache2/sites-available/${OLD_FQDN}.conf" 2>/dev/null | head -1 || true)

    [[ -n "$OLD_FQDN" && -n "$OLD_DB_NAME" && -n "$OLD_DB_USER" ]] \
        || die "Couldn't detect the existing install's FQDN/DB settings from its config files - is /opt/rr3 a deploy.sh-managed install?"

    echo
    echo "${BOLD}Detected existing environment${RESET}"
    hr
    print_kv "FQDN"     "$OLD_FQDN"
    print_kv "Database" "${OLD_DB_NAME} (user: ${OLD_DB_USER})"
    hr
    echo

    if [[ "$ASSUME_YES" -eq 1 ]]; then
        MIGRATE_CONFIG_MODE="${JAGGER_MIGRATE_CONFIG:-existing}"
    else
        echo "${BOLD}Configuration for the migrated environment${RESET}"
        echo "  ${DIM}existing - reuse the detected FQDN, admin email, DB settings, and logo as-is.${RESET}"
        echo "  ${DIM}new      - answer the usual setup questions fresh, even though you're migrating.${RESET}"
        if confirm "Perform NEW configuration instead of reusing the existing one?" N; then
            MIGRATE_CONFIG_MODE="new"
        else
            MIGRATE_CONFIG_MODE="existing"
        fi
    fi
    echo
fi

# --- Auto-detected info ----------------------------------------------------
PRIVATE_IP="$(detect_private_ip)"
PUBLIC_IP="$(detect_public_ip)"

echo "${BOLD}Detected system information${RESET}"
hr
print_kv "Hostname"      "$(hostname -f 2>/dev/null || hostname)"
print_kv "OS"            "$OS_PRETTY"
print_kv "Kernel"        "$(uname -r)"
print_kv "Architecture"  "$(uname -m)"
print_kv "CPU cores"     "$(nproc 2>/dev/null || echo unknown)"
print_kv "Memory"        "$(free -h 2>/dev/null | awk '/^Mem:/{print $2}')"
print_kv "Disk free (/)" "$(df -h / 2>/dev/null | awk 'NR==2{print $4}')"
print_kv "Private IP"    "$PRIVATE_IP"
print_kv "Public IP"     "$PUBLIC_IP"
hr
echo

USE_LOCAL_CHECKOUT=0
[[ -f "${SCRIPT_DIR}/install.sh" && -d "${SCRIPT_DIR}/application/config" ]] && USE_LOCAL_CHECKOUT=1
if [[ "$USE_LOCAL_CHECKOUT" -eq 1 ]]; then
    echo "${DIM}Running from inside a Jagger checkout - this exact copy will be deployed to /opt/rr3 (instead of a fresh git clone).${RESET}"
    echo
fi

# --- Interactive questions --------------------------------------------------
echo "${BOLD}Deployment settings${RESET}"
hr
if [[ "$MIGRATE_CONFIG_MODE" == "existing" ]]; then
    FQDN="$OLD_FQDN"
    SHORT_HOSTNAME="${JAGGER_HOSTNAME:-${FQDN%%.*}}"
    ADMIN_EMAIL="${OLD_ADMIN_EMAIL:-${JAGGER_ADMIN_EMAIL:-admin@${FQDN}}}"
    echo "Reusing existing FQDN, hostname, and admin email:"
    print_kv "FQDN"         "$FQDN"
    print_kv "Hostname"     "$SHORT_HOSTNAME"
    print_kv "Admin email"  "$ADMIN_EMAIL"
else
    ask "Fully Qualified Domain Name for Jagger" "${JAGGER_FQDN:-}" FQDN '^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$'
    ask "Short hostname for this server" "${JAGGER_HOSTNAME:-${FQDN%%.*}}" SHORT_HOSTNAME
    ask "Admin/contact email (Let's Encrypt + Apache)" "${JAGGER_ADMIN_EMAIL:-}" ADMIN_EMAIL '^[^@[:space:]]+@[^@[:space:]]+\.[^@[:space:]]+$'
fi

DEPLOY_MODE="${JAGGER_DEPLOY_MODE:-testing}"
if [[ "$ASSUME_YES" -ne 1 ]]; then
    echo
    echo "${BOLD}Certificate mode${RESET}"
    echo "  ${DIM}testing    - self-signed cert, works instantly with any hostname (VMs, local testing)${RESET}"
    echo "  ${DIM}production - a real, trusted Let's Encrypt cert (needs a public domain already pointing here)${RESET}"
    if confirm "Is this a production deployment with a real public domain?" N; then
        DEPLOY_MODE="production"
    fi
fi
if [[ "$DEPLOY_MODE" == "production" ]]; then
    CERT_FILE="/etc/letsencrypt/live/${FQDN}/fullchain.pem"
    CERT_KEY="/etc/letsencrypt/live/${FQDN}/privkey.pem"
    SSL_EXTRA_CONF="    Include /etc/letsencrypt/options-ssl-apache.conf"
else
    CERT_FILE="/etc/ssl/jagger/${FQDN}/fullchain.pem"
    CERT_KEY="/etc/ssl/jagger/${FQDN}/privkey.pem"
    SSL_EXTRA_CONF=$'    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1\n    SSLCipherSuite HIGH:!aNULL:!MD5'
fi

if [[ "$MIGRATE_CONFIG_MODE" == "existing" ]]; then
    DB_NAME="$OLD_DB_NAME"
    DB_USER="$OLD_DB_USER"
    DB_PASS="$OLD_DB_PASS"
    print_kv "Database" "${DB_NAME} (user: ${DB_USER})"
else
    ask "Jagger database name" "${JAGGER_DB_NAME:-rr3}" DB_NAME
    ask "Jagger database user" "${JAGGER_DB_USER:-rr3user}" DB_USER
    ask_secret "Jagger database password" DB_PASS "${JAGGER_DB_PASS:-}"
fi
ask_secret "New MySQL root password" MYSQL_ROOT_PASS "${JAGGER_MYSQL_ROOT_PASS:-}"

USE_MIRROR=0
[[ "${JAGGER_USE_MIRROR:-0}" == "1" ]] && USE_MIRROR=1
if [[ "$ASSUME_YES" -ne 1 ]] && confirm "Configure an alternate APT mirror?" N; then
    USE_MIRROR=1
fi
if [[ "$USE_MIRROR" -eq 1 ]]; then
    if [[ "$OS_ID" == "debian" ]]; then
        ask "Mirror base URL" "https://debian.mirror.garr.it" MIRROR_URL
    else
        ask "Mirror base URL" "https://ubuntu.mirror.garr.it" MIRROR_URL
    fi
fi

LOGO_PATH="${JAGGER_LOGO_PATH:-}"
if [[ "$MIGRATE_CONFIG_MODE" == "existing" ]]; then
    echo "${DIM}Keeping the existing logo, if any (restored automatically after redeploy).${RESET}"
else
    if [[ "$ASSUME_YES" -ne 1 ]]; then
        ask_optional "Path to a site logo PNG (optional)" LOGO_PATH
    fi
    if [[ -n "$LOGO_PATH" && ! -f "$LOGO_PATH" ]]; then
        echo "${YELLOW}Logo file not found at '${LOGO_PATH}' - skipping.${RESET}"
        LOGO_PATH=""
    fi
fi

MYSQL_ROOT_CNF="/root/.jagger-mysql-root.cnf"
BACKUP_DIR="/var/backups/jagger/$(date +%Y%m%d-%H%M%S)"

echo
echo "${BOLD}Summary${RESET}"
hr
print_kv "Deployment"      "$([[ $DEPLOY_KIND == migrate ]] && echo "migrate (${MIGRATE_CONFIG_MODE} config)" || echo "fresh")"
print_kv "FQDN"            "$FQDN"
print_kv "Hostname"        "$SHORT_HOSTNAME"
print_kv "Admin email"     "$ADMIN_EMAIL"
print_kv "Certificate"     "${DEPLOY_MODE} $([[ $DEPLOY_MODE == testing ]] && echo '(self-signed)' || echo "(Let's Encrypt)")"
print_kv "Database"        "${DB_NAME} (user: ${DB_USER})"
print_kv "APT mirror"      "$([[ $USE_MIRROR -eq 1 ]] && echo "${MIRROR_URL}" || echo "default")"
print_kv "Logo"            "${LOGO_PATH:-default}"
print_kv "Source"          "$([[ $USE_LOCAL_CHECKOUT -eq 1 ]] && echo "local checkout (${SCRIPT_DIR})" || echo "github.com/Edugate/Jagger")"
[[ "$DEPLOY_KIND" == "migrate" ]] && print_kv "Backup to" "$BACKUP_DIR"
print_kv "Log file"        "$LOG_FILE"
hr
echo

confirm "Start deployment now?" Y || { echo "Aborted."; exit 0; }

# --- Run steps ---------------------------------------------------------
# Reset the clock here, not at script start: everything above this point is
# spent waiting on the user (typing the FQDN, DB creds, this confirmation),
# and that thinking time must not be counted as elapsed deployment time or
# the very first ETA would come out wildly inflated.
SECONDS=0
: >"$LOG_FILE"
chmod 600 "$LOG_FILE"

# Two extra steps bookend the normal sequence when migrating: a backup right
# at the start (before anything is touched) and a restore right after the
# schema is (re)created near the end. STEP_WEIGHTS are rough relative time
# costs (arbitrary units, not seconds) per step, in the same order as the
# run_step calls below - these vary wildly in practice (PyFF's pip install
# alone can take 5+ minutes while several other steps finish in under a
# second), so a flat "average time per completed step so far" estimate badly
# undershoots once a slow step is reached. Weighting lets the ETA anticipate
# that before it gets there, then self-calibrates to this machine's actual
# speed using the ratio of real elapsed time to weight-of-work-done-so-far.
if [[ "$DEPLOY_KIND" == "migrate" ]]; then
    STEP_TOTAL=18
    STEP_WEIGHTS=(20 2 2 60 15 20 15 10 300 60 90 5 5 10 15 20 10 3)
    # Once the backup step below completes, any later step failing should
    # still point straight at it rather than only mentioning it in a
    # successful run's closing summary.
    on_step_failure() {
        echo
        echo "${BOLD}Your pre-migration backup (if the backup step itself completed) is here:${RESET}"
        echo "  ${CYAN}${BACKUP_DIR}${RESET}"
        [[ -f "${BACKUP_DIR}/db.sql.gz" ]] && echo "  Restore DB: gunzip -c ${BACKUP_DIR}/db.sql.gz | mysql --user=${OLD_DB_USER} -p --host=127.0.0.1 ${OLD_DB_NAME}"
    }
    run_step "Back up existing database"             step_migrate_backup
else
    STEP_TOTAL=16
    STEP_WEIGHTS=(2 2 60 15 20 15 10 300 60 90 5 5 10 15 10 3)
fi

run_step "Set hostname & /etc/hosts"                 step_hostname
run_step "Configure APT mirror"                      step_apt_mirror
run_step "Update & upgrade packages"                 step_apt_upgrade
run_step "Install base dependencies"                 step_base_deps
run_step "Install & secure MySQL"                    step_mysql
run_step "Install Apache & configure HTTP vhost"     step_apache_http
if [[ "$DEPLOY_MODE" == "production" ]]; then
    run_step "Obtain Let's Encrypt certificate"      step_tls
else
    run_step "Generate self-signed certificate"      step_tls
fi
run_step "Install PyFF"                              step_pyff
run_step "Install XMLSecTool"                        step_xmlsectool
run_step "Install PHP, Composer, CodeIgniter & Jagger" step_install_jagger
run_step "Create Jagger database & user"             step_jagger_db
run_step "Configure Jagger application files"        step_configure_jagger
run_step "Apply PHP 8.4 compatibility fixes"         step_php_compat
run_step "Populate database schema (Doctrine)"       step_doctrine
if [[ "$DEPLOY_KIND" == "migrate" ]]; then
    run_step "Restore database"                      step_migrate_restore
fi
run_step "Finalize Apache virtual host (SSL)"        step_finalize_vhost
run_step "Verify deployment"                         step_verify

# --- Summary -------------------------------------------------------------
TOTAL_TIME=$SECONDS
echo
hr
echo "${GREEN}${BOLD}${CHECK} Jagger deployment finished in $(format_hms "$TOTAL_TIME")${RESET}"
hr
for entry in "${STEP_TIMES[@]}"; do
    IFS='|' read -r name dur <<<"$entry"
    printf "  ${DIM}%-46s %s${RESET}\n" "$name" "$(format_hms "$dur")"
done
hr
echo
echo "${BOLD}Next steps (manual - cannot be automated):${RESET}"
if [[ "$DEPLOY_KIND" == "migrate" ]]; then
    echo "  1. Your restored data already has its admin user(s) - sign in as usual at:"
    echo "         ${CYAN}https://${FQDN}/rr3${RESET}"
    echo "  2. The redeploy re-enabled setup mode - edit"
    echo "     ${CYAN}/opt/rr3/application/config/config_rr.php${RESET} and set:"
    echo "         \$config['rr_setup_allowed'] = FALSE;"
else
    echo "  1. Visit ${CYAN}https://${FQDN}/rr3/setup${RESET} and create the initial admin user."
    echo "  2. Then edit ${CYAN}/opt/rr3/application/config/config_rr.php${RESET} and set:"
    echo "         \$config['rr_setup_allowed'] = FALSE;"
fi
if [[ "$DEPLOY_MODE" == "production" ]]; then
    echo "  3. Check your SSL grade: https://www.ssllabs.com/ssltest/analyze.html?d=${FQDN}"
else
    echo "  3. ${YELLOW}This is a self-signed testing certificate${RESET} - browsers will warn"
    echo "     'Not Secure' / 'connection is not private'. That's expected; click through"
    echo "     (Advanced -> Proceed) to reach the app. Once you have a real public domain"
    echo "     pointing at this server, re-run ${CYAN}sudo ./deploy.sh${RESET} and answer"
    echo "     'yes' to the production question to get a trusted Let's Encrypt certificate."
fi
echo
echo "${BOLD}Credentials:${RESET}"
echo "  Jagger DB user/password  : stored in /opt/rr3/application/config/database.php"
echo "  MySQL root password      : stored in ${MYSQL_ROOT_CNF} (chmod 600, root-only)"
if [[ "$DEPLOY_KIND" == "migrate" ]]; then
    echo
    echo "${BOLD}Pre-migration database backup:${RESET} ${CYAN}${BACKUP_DIR}${RESET}"
fi
echo
echo "${DIM}Full log: ${LOG_FILE}${RESET}"
