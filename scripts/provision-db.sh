#!/usr/bin/env bash
# =============================================================================
# provision-db.sh — Provisions PostgreSQL database & generates login credentials
# =============================================================================
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKEND_DIR="$PROJECT_ROOT/backend"

USERNAME="${1:-protocol_user}"
PASSWORD="${2:-$(openssl rand -hex 12 2>/dev/null || tr -dc 'A-Za-z0-9' </dev/urandom | head -c 16)}"
DATABASE="${3:-protocol_platform}"
PORT="${4:-5433}"
RUN_MIGRATIONS="${5:-false}"

echo -e "\033[1;36m=========================================================="
echo "   PostgreSQL Database & Credentials Provisioning Script   "
echo -e "==========================================================\033[0m"
echo "Target Configuration:"
echo "  Host:     127.0.0.1"
echo "  Port:     $PORT"
echo "  Database: $DATABASE"
echo "  Username: $USERNAME"
echo "  Password: $PASSWORD"
echo ""

echo -e "\033[1;33m[1/5] Checking PostgreSQL container (protocol_pg17)...\033[0m"
if ! docker ps --filter "name=protocol_pg17" --format "{{.Status}}" | grep -q "Up"; then
    echo "      Starting protocol_pg17 container..."
    docker start protocol_pg17
    sleep 2
fi
echo -e "\033[1;32m      PostgreSQL container is online.\033[0m"

echo -e "\033[1;33m[2/5] Creating user, database, and setting permissions...\033[0m"
# Create user/role
docker exec -i protocol_pg17 psql -U postgres -c "DO \$do\$ BEGIN IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$USERNAME') THEN CREATE ROLE $USERNAME WITH LOGIN PASSWORD '$PASSWORD' CREATEDB; ELSE ALTER ROLE $USERNAME WITH LOGIN PASSWORD '$PASSWORD'; END IF; END \$do\$;" >/dev/null

# Create database if not exists
DB_EXISTS=$(docker exec -i protocol_pg17 psql -U postgres -tAc "SELECT 1 FROM pg_database WHERE datname = '$DATABASE';" | tr -d ' \r\n')
if [ "$DB_EXISTS" != "1" ]; then
    docker exec -i protocol_pg17 psql -U postgres -c "CREATE DATABASE $DATABASE;" >/dev/null
fi

# Grant permissions
docker exec -i protocol_pg17 psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE $DATABASE TO $USERNAME; ALTER DATABASE $DATABASE OWNER TO $USERNAME;" >/dev/null
docker exec -i protocol_pg17 psql -U postgres -d "$DATABASE" -c "GRANT ALL ON SCHEMA public TO $USERNAME; ALTER SCHEMA public OWNER TO $USERNAME; GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $USERNAME; GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO $USERNAME; ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $USERNAME; ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO $USERNAME;" >/dev/null

echo -e "\033[1;32m      User '$USERNAME' and database '$DATABASE' provisioned.\033[0m"

echo -e "\033[1;33m[3/5] Updating backend/.env file...\033[0m"
ENV_FILE="$BACKEND_DIR/.env"
if [ -f "$ENV_FILE" ]; then
    sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$ENV_FILE"
    sed -i "s/^DB_HOST=.*/DB_HOST=127.0.0.1/" "$ENV_FILE"
    sed -i "s/^DB_PORT=.*/DB_PORT=$PORT/" "$ENV_FILE"
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DATABASE/" "$ENV_FILE"
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$USERNAME/" "$ENV_FILE"
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$PASSWORD/" "$ENV_FILE"
    echo -e "\033[1;32m      backend/.env updated with new credentials.\033[0m"
fi

echo -e "\033[1;33m[4/5] Clearing Laravel configuration cache...\033[0m"
cd "$BACKEND_DIR"
php artisan config:clear >/dev/null 2>&1 || true
php artisan cache:clear >/dev/null 2>&1 || true
echo -e "\033[1;32m      Laravel caches cleared.\033[0m"

if [ "$RUN_MIGRATIONS" = "true" ] || [ "$RUN_MIGRATIONS" = "--migrate" ]; then
    echo -e "\033[1;33m[5/5] Running migrations and database seeders...\033[0m"
    php artisan migrate --seed
    echo -e "\033[1;32m      Migrations and seeders completed.\033[0m"
else
    echo -e "\033[0;37m[5/5] Preserving existing database data (pass 'true' as 5th argument to run migrations).\033[0m"
fi

echo ""
echo -e "\033[1;32m=========================================================="
echo "   CREDENTIALS SUCCESSFULLY GENERATED & CONFIGURED        "
echo "=========================================================="
echo "  DB_CONNECTION=pgsql"
echo "  DB_HOST=127.0.0.1"
echo "  DB_PORT=$PORT"
echo "  DB_DATABASE=$DATABASE"
echo "  DB_USERNAME=$USERNAME"
echo "  DB_PASSWORD=$PASSWORD"
echo -e "==========================================================\033[0m"
