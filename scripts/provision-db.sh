#!/bin/sh
# Provision database script for Git Bash, WSL, and Linux
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"
ENV_FILE="$BACKEND_DIR/.env"
SQLITE_FILE="$BACKEND_DIR/database/database.sqlite"

MODE="${1:-Auto}"

echo "=========================================="
echo " Protocol Platform Database Provisioner "
echo "=========================================="

if [ "$MODE" = "Auto" ]; then
    echo "[1/4] Detecting environment services..."
    if nc -z 127.0.0.1 5433 2>/dev/null || (exec 3<>/dev/tcp/127.0.0.1/5433) 2>/dev/null; then
        echo "       PostgreSQL container detected on 127.0.0.1:5433 -> Selecting Docker Mode"
        TARGET_MODE="Docker"
    else
        echo "       Port 5433 is inactive -> Selecting Standalone Mode (SQLite)"
        TARGET_MODE="Standalone"
    fi
else
    TARGET_MODE="$MODE"
    echo "[1/4] Explicit Mode Selected: $TARGET_MODE"
fi

set_env_var() {
    local key="$1"
    local val="$2"
    if grep -q "^$key=" "$ENV_FILE" 2>/dev/null; then
        sed -i "s|^$key=.*|$key=$val|" "$ENV_FILE"
    else
        echo "$key=$val" >> "$ENV_FILE"
    fi
}

echo "[2/4] Configuring backend/.env for $TARGET_MODE Mode..."
if [ ! -f "$ENV_FILE" ]; then
    if [ -f "$BACKEND_DIR/.env.example" ]; then
        cp "$BACKEND_DIR/.env.example" "$ENV_FILE"
    else
        touch "$ENV_FILE"
    fi
fi

if [ "$TARGET_MODE" = "Standalone" ]; then
    set_env_var "DB_CONNECTION" "sqlite"
    set_env_var "DB_DATABASE" "database/database.sqlite"
    set_env_var "CACHE_STORE" "file"
    set_env_var "QUEUE_CONNECTION" "sync"
    set_env_var "SESSION_DRIVER" "file"
    set_env_var "SCOUT_DRIVER" "null"
    mkdir -p "$(dirname "$SQLITE_FILE")"
    [ ! -f "$SQLITE_FILE" ] && touch "$SQLITE_FILE"
else
    set_env_var "DB_CONNECTION" "pgsql"
    set_env_var "DB_HOST" "127.0.0.1"
    set_env_var "DB_PORT" "5433"
    set_env_var "DB_DATABASE" "protocol_platform"
    set_env_var "DB_USERNAME" "postgres"
    set_env_var "DB_PASSWORD" "secret"
    set_env_var "CACHE_STORE" "file"
    set_env_var "QUEUE_CONNECTION" "sync"
    set_env_var "SESSION_DRIVER" "file"
    set_env_var "SCOUT_DRIVER" "typesense"
    set_env_var "TYPESENSE_HOST" "localhost"
    set_env_var "TYPESENSE_PORT" "8108"
fi

echo "[3/4] Checking PHP executable..."
PHP_BIN="php"
if [ -x "/c/tools/php/php.exe" ]; then
    PHP_BIN="/c/tools/php/php.exe"
fi

echo "[4/4] Verifying database schema and seeding..."
cd "$BACKEND_DIR"
$PHP_BIN artisan config:clear > /dev/null 2>&1 || true

if [ "$TARGET_MODE" = "Standalone" ]; then
    if [ ! -s "$SQLITE_FILE" ]; then
        echo "       Seeding initial database fixtures into SQLite..."
        $PHP_BIN artisan migrate --force > /dev/null
        $PHP_BIN artisan db:seed --force > /dev/null
    else
        echo "       SQLite database is already populated with seed data."
    fi
else
    echo "       Migrating PostgreSQL database..."
    $PHP_BIN artisan migrate --force > /dev/null
fi

echo "=========================================="
echo " Provisioning Complete ($TARGET_MODE Mode)!"
echo " Start backend with: php artisan serve --host=127.0.0.1 --port=8000"
echo "=========================================="
