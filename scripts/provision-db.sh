#!/usr/bin/env bash
# Database provisioning script for Linux / Git Bash
set -e

MODE="${1:-Auto}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"
ENV_FILE="$BACKEND_DIR/.env"
SQLITE_FILE="$BACKEND_DIR/database/database.sqlite"

echo "=========================================="
echo " Protocol Platform Database Provisioner "
echo "=========================================="

# 1. Determine Target Mode
if [ "$MODE" = "Auto" ]; then
    echo "[1/4] Detecting environment services..."
    if nc -z 127.0.0.1 5433 2>/dev/null; then
        echo "       PostgreSQL container detected on 127.0.0.1:5433 -> Selecting Docker Mode"
        TARGET_MODE="Docker"
    else
        echo "       Port 5433 is inactive (Docker/WSL offline) -> Selecting Standalone Mode (SQLite)"
        TARGET_MODE="Standalone"
    fi
else
    TARGET_MODE="$MODE"
    echo "[1/4] Explicit Mode Selected: $TARGET_MODE"
fi

set_env_var() {
    local key="$1"
    local val="$2"
    if grep -q "^${key}=" "$ENV_FILE"; then
        sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
        echo "${key}=${val}" >> "$ENV_FILE"
    fi
}

echo "[2/4] Configuring backend/.env for $TARGET_MODE Mode..."
if [ ! -f "$ENV_FILE" ]; then
    cp "$BACKEND_DIR/.env.example" "$ENV_FILE" 2>/dev/null || touch "$ENV_FILE"
fi

if [ "$TARGET_MODE" = "Standalone" ]; then
    set_env_var "DB_CONNECTION" "sqlite"
    set_env_var "DB_DATABASE" "database/database.sqlite"
    set_env_var "CACHE_STORE" "file"
    set_env_var "QUEUE_CONNECTION" "sync"
    set_env_var "SESSION_DRIVER" "file"
    set_env_var "SCOUT_DRIVER" "null"
    touch "$SQLITE_FILE"
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

PHP_CMD="php"
if [ -f "/c/tools/php/php.exe" ]; then
    PHP_CMD="/c/tools/php/php.exe"
fi

echo "[3/4] Clearing cache and verifying configuration..."
cd "$BACKEND_DIR"
$PHP_CMD artisan config:clear > /dev/null 2>&1

echo "[4/4] Verifying database schema and seeding..."
if [ "$TARGET_MODE" = "Standalone" ]; then
    if [ ! -s "$SQLITE_FILE" ] || [ $(wc -c < "$SQLITE_FILE") -lt 50000 ]; then
        echo "       Seeding initial database fixtures into SQLite..."
        $PHP_CMD artisan migrate --force > /dev/null 2>&1
        $PHP_CMD artisan db:seed --force > /dev/null 2>&1
    else
        echo "       SQLite database is already populated with seed data."
    fi
else
    echo "       Migrating PostgreSQL database..."
    $PHP_CMD artisan migrate --force > /dev/null 2>&1
fi

echo "=========================================="
echo " Provisioning Complete ($TARGET_MODE Mode)!"
echo " You can now start the backend with:"
echo "   cd backend && php artisan serve --host=127.0.0.1 --port=8000"
echo "=========================================="
