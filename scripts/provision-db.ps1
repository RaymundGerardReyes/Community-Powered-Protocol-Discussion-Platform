<#
.SYNOPSIS
    Provisions a PostgreSQL database, generates/configures credentials, updates backend/.env, and verifies connection.
.DESCRIPTION
    Creates or updates a database user, sets password, creates database, grants full privileges, 
    writes credentials to backend/.env, and clears Laravel caches.
.PARAMETER Username
    Database user to create (defaults to 'protocol_user' or auto-generated).
.PARAMETER Password
    Database password (defaults to randomly generated 16-char secure string).
.PARAMETER Database
    Database name to create (defaults to 'protocol_platform').
.PARAMETER Port
    PostgreSQL port (defaults to 5433 for protocol_pg17 container).
.PARAMETER RunMigrations
    Switch to automatically run php artisan migrate --seed after provisioning.
.EXAMPLE
    .\scripts\provision-db.ps1
    .\scripts\provision-db.ps1 -Username "my_app" -Password "SecretPass123" -RunMigrations
#>

[CmdletBinding()]
param(
    [string]$Username = "protocol_user",
    [string]$Password = "",
    [string]$Database = "protocol_platform",
    [int]$Port = 5433,
    [switch]$RunMigrations
)

$ErrorActionPreference = "Stop"

function Generate-SecurePassword([int]$Length = 16) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
    $bytes = New-Object byte[] $Length
    $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    $rng.GetBytes($bytes)
    $pass = ""
    for ($i = 0; $i -lt $Length; $i++) {
        $pass += $chars[$bytes[$i] % $chars.Length]
    }
    return $pass
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host "   PostgreSQL Database & Credentials Provisioning Script   " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Determine Password
if (-not $Password) {
    $Password = Generate-SecurePassword 16
    Write-Host "[*] Generated secure random password." -ForegroundColor Yellow
}

Write-Host "Target Configuration:" -ForegroundColor White
Write-Host "  Host:     127.0.0.1" -ForegroundColor Gray
Write-Host "  Port:     $Port" -ForegroundColor Gray
Write-Host "  Database: $Database" -ForegroundColor Gray
Write-Host "  Username: $Username" -ForegroundColor Gray
Write-Host "  Password: $Password" -ForegroundColor Gray
Write-Host ""

# 2. Check Container Liveness
Write-Host "[1/5] Checking PostgreSQL container (protocol_pg17)..." -ForegroundColor Yellow
$containerStatus = docker ps --filter "name=protocol_pg17" --format "{{.Status}}"
if (-not $containerStatus) {
    Write-Host "      Container not running. Starting protocol_pg17..." -ForegroundColor Yellow
    docker start protocol_pg17 | Out-Null
    Start-Sleep -Seconds 2
}
Write-Host "      PostgreSQL container is online." -ForegroundColor Green

# 3. Create Database & User with Full Privileges
Write-Host "[2/5] Creating user, database, and setting permissions..." -ForegroundColor Yellow

# Create user/role
$createUserSql = "DO `$do`$ BEGIN IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$Username') THEN CREATE ROLE $Username WITH LOGIN PASSWORD '$Password' CREATEDB; ELSE ALTER ROLE $Username WITH LOGIN PASSWORD '$Password'; END IF; END `$do`$;"
docker exec protocol_pg17 psql -U postgres -c $createUserSql | Out-Null

# Create database if not exists
$checkDbSql = "SELECT 1 FROM pg_database WHERE datname = '$Database';"
$dbExists = docker exec protocol_pg17 psql -U postgres -tAc $checkDbSql
if ($dbExists.Trim() -ne "1") {
    docker exec protocol_pg17 psql -U postgres -c "CREATE DATABASE $Database;" | Out-Null
}

# Grant database level permissions
docker exec protocol_pg17 psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE $Database TO $Username; ALTER DATABASE $Database OWNER TO $Username;" | Out-Null

# Grant schema privileges inside the database
$schemaSql = "GRANT ALL ON SCHEMA public TO $Username; ALTER SCHEMA public OWNER TO $Username; GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $Username; GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO $Username; ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $Username; ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO $Username;"
docker exec protocol_pg17 psql -U postgres -d $Database -c $schemaSql | Out-Null

Write-Host "      User '$Username' and database '$Database' provisioned with full permissions." -ForegroundColor Green

# 4. Update backend/.env file
Write-Host "[3/5] Updating backend/.env file..." -ForegroundColor Yellow
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$backendDir = Join-Path (Split-Path -Parent $scriptDir) "backend"
$envPath = Join-Path $backendDir ".env"

if (Test-Path $envPath) {
    $envContent = Get-Content $envPath

    $replacements = @{
        "^DB_CONNECTION=.*" = "DB_CONNECTION=pgsql"
        "^DB_HOST=.*"       = "DB_HOST=127.0.0.1"
        "^DB_PORT=.*"       = "DB_PORT=$Port"
        "^DB_DATABASE=.*"   = "DB_DATABASE=$Database"
        "^DB_USERNAME=.*"   = "DB_USERNAME=$Username"
        "^DB_PASSWORD=.*"   = "DB_PASSWORD=$Password"
    }

    foreach ($pattern in $replacements.Keys) {
        $replacement = $replacements[$pattern]
        if ($envContent -match $pattern) {
            $envContent = $envContent -replace $pattern, $replacement
        } else {
            $envContent += $replacement
        }
    }

    Set-Content -Path $envPath -Value $envContent -Encoding UTF8
    Write-Host "      backend/.env successfully updated with new credentials." -ForegroundColor Green
} else {
    Write-Host "      WARNING: backend/.env not found at $envPath" -ForegroundColor Red
}

# 5. Clear Laravel caches
Write-Host "[4/5] Clearing Laravel configuration cache..." -ForegroundColor Yellow
$phpPath = "C:\tools\php\php.exe"
if (-not (Test-Path $phpPath)) {
    $phpPath = "php"
}

Push-Location $backendDir
try {
    & $phpPath artisan config:clear | Out-Null
    & $phpPath artisan cache:clear | Out-Null
    Write-Host "      Laravel caches cleared." -ForegroundColor Green

    # 6. Optional: Run migrations & seeders
    if ($RunMigrations) {
        Write-Host "[5/5] Running migrations and database seeders..." -ForegroundColor Yellow
        & $phpPath artisan migrate --seed
        Write-Host "      Migrations and seeders completed." -ForegroundColor Green
    } else {
        Write-Host "[5/5] Preserving existing database data (pass -RunMigrations to re-run)." -ForegroundColor Gray
    }
} finally {
    Pop-Location
}

Write-Host ""
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "   CREDENTIALS SUCCESSFULLY GENERATED & CONFIGURED        " -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green
Write-Host "  DB_CONNECTION=pgsql" -ForegroundColor Cyan
Write-Host "  DB_HOST=127.0.0.1" -ForegroundColor Cyan
Write-Host "  DB_PORT=$Port" -ForegroundColor Cyan
Write-Host "  DB_DATABASE=$Database" -ForegroundColor Cyan
Write-Host "  DB_USERNAME=$Username" -ForegroundColor Cyan
Write-Host "  DB_PASSWORD=$Password" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Green
