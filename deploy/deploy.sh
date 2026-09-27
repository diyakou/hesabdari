#!/usr/bin/env bash
set -Eeuo pipefail

# Production installer/deployer for pnp.securecodehub.ir
# Target: Ubuntu 22.04/24.04/26.04 LTS (run as root).

DOMAIN="${DOMAIN:-pnp.securecodehub.ir}"
APP_DIR="${APP_DIR:-/var/www/pnp.securecodehub.ir}"
APP_USER="${APP_USER:-pnp}"
DB_NAME="${DB_NAME:-pnp_accounting}"
DB_USER="${DB_USER:-pnp_accounting}"
DB_PASSWORD="${DB_PASSWORD:-}"
LE_EMAIL="${LE_EMAIL:-}"
REPOSITORY="${REPOSITORY:-}"
BRANCH="${BRANCH:-main}"
SOURCE_DIR=""
WITH_SSL=1
RUN_SEEDERS=1

usage() {
    cat <<'EOF'
Usage: sudo bash deploy/deploy.sh --email admin@example.com [options]

Options:
  --email ADDRESS       Email used by Let's Encrypt (required unless --no-ssl)
  --repo URL            Clone/update this Git repository instead of local files
  --branch NAME         Git branch to deploy (default: main)
  --source PATH         Local project directory (default: parent of this script)
  --app-dir PATH        Installation directory (default: /var/www/pnp.securecodehub.ir)
  --db-password VALUE   PostgreSQL password (generated when omitted)
  --no-ssl              Install HTTP only; useful before DNS points to this server
  --no-seed             Do not run the production-safe DatabaseSeeder
  -h, --help            Show this help

Environment variables with the uppercase option names are also supported.
EOF
}

log() { printf '\n\033[1;32m==> %s\033[0m\n' "$*"; }
die() { printf '\nERROR: %s\n' "$*" >&2; exit 1; }

while (($#)); do
    case "$1" in
        --email) LE_EMAIL="${2:?Missing email}"; shift 2 ;;
        --repo) REPOSITORY="${2:?Missing repository URL}"; shift 2 ;;
        --branch) BRANCH="${2:?Missing branch}"; shift 2 ;;
        --source) SOURCE_DIR="${2:?Missing source path}"; shift 2 ;;
        --app-dir) APP_DIR="${2:?Missing application path}"; shift 2 ;;
        --db-password) DB_PASSWORD="${2:?Missing database password}"; shift 2 ;;
        --no-ssl) WITH_SSL=0; shift ;;
        --no-seed) RUN_SEEDERS=0; shift ;;
        -h|--help) usage; exit 0 ;;
        *) die "Unknown option: $1" ;;
    esac
done

[[ $EUID -eq 0 ]] || die "Run this script as root (sudo)."
[[ -r /etc/os-release ]] || die "Unsupported operating system. Ubuntu 22.04, 24.04, or 26.04 is required."
# shellcheck disable=SC1091
source /etc/os-release
[[ "${ID:-}" == "ubuntu" && ("${VERSION_ID:-}" == "22.04" || "${VERSION_ID:-}" == "24.04" || "${VERSION_ID:-}" == "26.04") ]] || \
    die "This installer supports Ubuntu 22.04, 24.04, and 26.04 LTS only."
[[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || die "Invalid DOMAIN."
[[ "$DB_NAME" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] || die "Invalid DB_NAME."
[[ "$DB_USER" =~ ^[A-Za-z_][A-Za-z0-9_]*$ ]] || die "Invalid DB_USER."
[[ $WITH_SSL -eq 0 || "$LE_EMAIL" =~ ^[^[:space:]@]+@[^[:space:]@]+\.[^[:space:]@]+$ ]] || \
    die "A valid --email is required for Let's Encrypt, or use --no-ssl."

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_DIR="${SOURCE_DIR:-$(dirname "$SCRIPT_DIR")}" 
# On repeat deployments, keep the current database credential unless explicitly replaced.
if [[ -z "$DB_PASSWORD" && -f "$APP_DIR/.env" ]]; then
    DB_PASSWORD="$(sed -n 's/^DB_PASSWORD=//p' "$APP_DIR/.env" | head -n 1)"
fi

export DEBIAN_FRONTEND=noninteractive

log "Installing system packages"
apt-get update
if [[ "$VERSION_ID" == "22.04" ]]; then
    # Laravel 13 requires PHP 8.3+, while Ubuntu 22.04 ships PHP 8.1.
    apt-get install -y --no-install-recommends software-properties-common ca-certificates
    add-apt-repository -y ppa:ondrej/php
    apt-get update
fi

if [[ "$VERSION_ID" == "26.04" ]]; then
    PHP_PACKAGES=(php-fpm php-cli php-pgsql php-mbstring php-intl php-gd php-curl php-xml php-zip)
else
    PHP_PACKAGES=(php8.3-fpm php8.3-cli php8.3-pgsql php8.3-mbstring php8.3-intl php8.3-gd php8.3-curl php8.3-xml php8.3-zip)
fi

apt-get install -y --no-install-recommends \
    nginx postgresql postgresql-client composer git rsync unzip curl ca-certificates \
    gnupg certbot python3-certbot-nginx "${PHP_PACKAGES[@]}"

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
PHP_MAJOR="${PHP_VERSION%%.*}"
PHP_MINOR="${PHP_VERSION##*.}"
if ((PHP_MAJOR < 8 || (PHP_MAJOR == 8 && PHP_MINOR < 3))); then
    die "PHP 8.3 or newer is required; installed version is $PHP_VERSION."
fi
PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"
PHP_FPM_SOCKET="/run/php/php${PHP_VERSION}-fpm.sock"
PHP_FPM_CONF_DIR="/etc/php/${PHP_VERSION}/fpm/conf.d"
[[ -S "$PHP_FPM_SOCKET" || -d "$PHP_FPM_CONF_DIR" ]] || \
    die "PHP-FPM installation was not detected for PHP $PHP_VERSION."

if ! command -v node >/dev/null 2>&1 || [[ "$(node -p 'Number(process.versions.node.split(".")[0])')" -lt 22 ]]; then
    install -d -m 0755 /etc/apt/keyrings
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key \
        | gpg --dearmor --yes -o /etc/apt/keyrings/nodesource.gpg
    printf 'deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main\n' \
        >/etc/apt/sources.list.d/nodesource.list
    apt-get update
    apt-get install -y nodejs
fi

[[ -n "$DB_PASSWORD" ]] || DB_PASSWORD="$(php -r 'echo bin2hex(random_bytes(18));')"
[[ "$DB_PASSWORD" =~ ^[A-Za-z0-9]+$ ]] || \
    die "DB password must contain only letters and digits (or omit it to generate one)."

id "$APP_USER" >/dev/null 2>&1 || useradd --system --create-home --home-dir "/home/$APP_USER" --shell /bin/bash "$APP_USER"
install -d -o "$APP_USER" -g www-data -m 0750 "$APP_DIR"
systemctl enable --now postgresql

log "Installing application files"
if [[ -n "$REPOSITORY" ]]; then
    if [[ -d "$APP_DIR/.git" ]]; then
        runuser -u "$APP_USER" -- git -C "$APP_DIR" fetch --prune origin
        runuser -u "$APP_USER" -- git -C "$APP_DIR" checkout "$BRANCH"
        runuser -u "$APP_USER" -- git -C "$APP_DIR" pull --ff-only origin "$BRANCH"
    else
        [[ -z "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit)" ]] || \
            die "$APP_DIR is not empty and is not a Git checkout."
        runuser -u "$APP_USER" -- git clone --branch "$BRANCH" --depth 1 "$REPOSITORY" "$APP_DIR"
    fi
else
    [[ -f "$SOURCE_DIR/artisan" ]] || die "Laravel project not found at $SOURCE_DIR."
    if [[ "$(realpath "$SOURCE_DIR")" != "$(realpath "$APP_DIR")" ]]; then
        rsync -a --delete \
            --exclude='.env' --exclude='.git/' --exclude='backups/' --exclude='node_modules/' --exclude='vendor/' \
            --exclude='storage/logs/*' --exclude='storage/framework/cache/*' \
            --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' \
            "$SOURCE_DIR/" "$APP_DIR/"
    fi
    chown -R "$APP_USER:www-data" "$APP_DIR"
fi

log "Configuring PostgreSQL"
runuser -u postgres -- psql --set=ON_ERROR_STOP=1 <<SQL
SELECT 'CREATE ROLE $DB_USER LOGIN PASSWORD ''$DB_PASSWORD'''
WHERE NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '$DB_USER')\gexec
ALTER ROLE $DB_USER WITH LOGIN PASSWORD '$DB_PASSWORD';
SELECT 'CREATE DATABASE $DB_NAME OWNER $DB_USER'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec
SQL

cd "$APP_DIR"
if [[ -f .env ]]; then
    APP_KEY="$(sed -n 's/^APP_KEY=//p' .env | head -n 1)"
else
    APP_KEY=""
fi

cat >.env <<EOF
APP_NAME="مدیریت فروشگاه موبایل"
APP_ENV=production
APP_KEY=$APP_KEY
APP_DEBUG=false
APP_URL=https://$DOMAIN
APP_LOCALE=fa
APP_FALLBACK_LOCALE=fa
APP_FAKER_LOCALE=fa_IR
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USER
DB_PASSWORD=$DB_PASSWORD
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=$DOMAIN
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
CACHE_STORE=database
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@$DOMAIN
MAIL_FROM_NAME="مدیریت فروشگاه موبایل"
EOF
[[ $WITH_SSL -eq 1 ]] || sed -i "s|APP_URL=https://|APP_URL=http://|; s/SESSION_SECURE_COOKIE=true/SESSION_SECURE_COOKIE=false/" .env
chown "$APP_USER:www-data" .env
chmod 0640 .env

log "Installing PHP and frontend dependencies"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci --no-audit --no-fund
npm run build
rm -rf node_modules
chown -R "$APP_USER:www-data" "$APP_DIR"
find storage bootstrap/cache -type d -exec chmod 2775 {} +
find storage bootstrap/cache -type f -exec chmod 0664 {} +

if [[ -z "$APP_KEY" ]]; then
    runuser -u "$APP_USER" -- php artisan key:generate --force
fi

if runuser -u postgres -- psql -d "$DB_NAME" -Atqc "SELECT to_regclass('public.migrations')" | grep -q migrations; then
    install -d -o "$APP_USER" -g "$APP_USER" -m 0750 "$APP_DIR/backups"
    BACKUP_FILE="$APP_DIR/backups/pre_deploy_$(date +%Y%m%d_%H%M%S).dump"
    runuser -u postgres -- pg_dump --format=custom "$DB_NAME" >"$BACKUP_FILE"
    chown "$APP_USER:$APP_USER" "$BACKUP_FILE"
    chmod 0600 "$BACKUP_FILE"
fi

runuser -u "$APP_USER" -- php artisan down --retry=30 || true
trap 'runuser -u "$APP_USER" -- php artisan up >/dev/null 2>&1 || true' EXIT
runuser -u "$APP_USER" -- php artisan migrate --force
if [[ $RUN_SEEDERS -eq 1 ]]; then
    runuser -u "$APP_USER" -- php artisan db:seed --force
fi
runuser -u "$APP_USER" -- php artisan optimize

log "Configuring PHP-FPM and Nginx"
cat >"$PHP_FPM_CONF_DIR/99-pnp.ini" <<'EOF'
expose_php=Off
memory_limit=256M
upload_max_filesize=20M
post_max_size=24M
max_execution_time=60
EOF

cat >"/etc/nginx/sites-available/$DOMAIN" <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN;
    root $APP_DIR/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:$PHP_FPM_SOCKET;
    }
    location ~ /\.(?!well-known).* { deny all; }
    client_max_body_size 20m;
}
EOF
ln -sfn "/etc/nginx/sites-available/$DOMAIN" "/etc/nginx/sites-enabled/$DOMAIN"
rm -f /etc/nginx/sites-enabled/default

cat >/etc/systemd/system/pnp-queue.service <<EOF
[Unit]
Description=PNP Laravel queue worker
After=network.target postgresql.service

[Service]
User=$APP_USER
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

cat >/etc/systemd/system/pnp-scheduler.service <<EOF
[Unit]
Description=PNP Laravel scheduler

[Service]
Type=oneshot
User=$APP_USER
Group=www-data
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/php artisan schedule:run
EOF

cat >/etc/systemd/system/pnp-scheduler.timer <<'EOF'
[Unit]
Description=Run PNP Laravel scheduler every minute

[Timer]
OnCalendar=*-*-* *:*:00
Persistent=true
AccuracySec=1s

[Install]
WantedBy=timers.target
EOF

nginx -t
systemctl daemon-reload
systemctl enable --now nginx "$PHP_FPM_SERVICE" postgresql pnp-queue.service pnp-scheduler.timer
systemctl restart "$PHP_FPM_SERVICE" nginx pnp-queue.service

if [[ $WITH_SSL -eq 1 ]]; then
    log "Requesting Let's Encrypt certificate"
    certbot --nginx --non-interactive --agree-tos --redirect \
        --email "$LE_EMAIL" -d "$DOMAIN"
fi

runuser -u "$APP_USER" -- php artisan up
trap - EXIT

log "Deployment completed"
printf 'URL: %s\nApp directory: %s\nDatabase: %s\n' \
    "$([[ $WITH_SSL -eq 1 ]] && printf https || printf http)://$DOMAIN" "$APP_DIR" "$DB_NAME"
printf 'Create the first administrator with:\n  sudo -u %s php %s/artisan make:admin\n' "$APP_USER" "$APP_DIR"
