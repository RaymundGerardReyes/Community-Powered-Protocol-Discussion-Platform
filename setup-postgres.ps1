# PostgreSQL Setup Script for Protocol Platform
# Run this script in PowerShell to create the database and user account automatically.

Write-Host "=========================================" -ForegroundColor Cyan
Write-Host "  Protocol Platform - PostgreSQL 17 Setup" -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan

# 1. Prompt for current PostgreSQL master password (input is hidden)
$promptPass = Read-Host "Enter your current PostgreSQL 'postgres' password (input hidden)" -AsSecureString
$BSTR = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($promptPass)
$masterPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto($BSTR)

$env:PGPASSWORD = $masterPassword

# 2. Test connection
Write-Host "`n[1/4] Testing connection to PostgreSQL 17 on 127.0.0.1:5432..." -ForegroundColor Yellow
$test = psql -U postgres -h 127.0.0.1 -c "SELECT version();" 2>&1
if ($LASTEXITCODE -ne 0) {
    Write-Host "`n[ERROR] Could not connect to PostgreSQL with the provided password." -ForegroundColor Red
    Write-Host "Error details: $test" -ForegroundColor DarkRed
    exit 1
}
Write-Host "Connected successfully!" -ForegroundColor Green

# 3. Create database if it does not exist
Write-Host "`n[2/4] Creating 'protocol_platform' database if not exists..." -ForegroundColor Yellow
$createDbSql = "SELECT 'CREATE DATABASE protocol_platform' WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'protocol_platform')\gexec"
psql -U postgres -h 127.0.0.1 -c $createDbSql

# 4. Create dedicated application user 'protocol_user' with password 'secret'
Write-Host "`n[3/4] Creating dedicated 'protocol_user' account with password 'secret'..." -ForegroundColor Yellow
$createUserSql = @"
DO `$do`$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'protocol_user') THEN
    CREATE USER protocol_user WITH ENCRYPTED PASSWORD 'secret';
  ELSE
    ALTER USER protocol_user WITH ENCRYPTED PASSWORD 'secret';
  END IF;
END
`$do`$;
GRANT ALL PRIVILEGES ON DATABASE protocol_platform TO protocol_user;
ALTER DATABASE protocol_platform OWNER TO protocol_user;
"@

psql -U postgres -h 127.0.0.1 -c $createUserSql

# Grant schema privileges inside the database
$grantSchemaSql = @"
GRANT ALL ON SCHEMA public TO protocol_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO protocol_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO protocol_user;
"@
psql -U postgres -h 127.0.0.1 -d protocol_platform -c $grantSchemaSql
Write-Host "User 'protocol_user' created and permissions granted!" -ForegroundColor Green

# 5. Update backend/.env with the new credentials
Write-Host "`n[4/4] Updating backend/.env configuration..." -ForegroundColor Yellow
$envPath = "D:\PHP\protocol-platform\backend\.env"
$envContent = Get-Content $envPath -Raw

$envContent = $envContent -replace 'DB_USERNAME=.*', 'DB_USERNAME=protocol_user'
$envContent = $envContent -replace 'DB_PASSWORD=.*', 'DB_PASSWORD=secret'

Set-Content -Path $envPath -Value $envContent
Write-Host "backend/.env updated with DB_USERNAME=protocol_user and DB_PASSWORD=secret" -ForegroundColor Green

# 6. Run migrations & seeders
Write-Host "`n=========================================" -ForegroundColor Cyan
Write-Host "Running Laravel migrations and seeders..." -ForegroundColor Cyan
Write-Host "=========================================" -ForegroundColor Cyan
Set-Location "D:\PHP\protocol-platform\backend"
php artisan migrate:fresh --seed

Write-Host "`n[SUCCESS] PostgreSQL 17 setup and database seeding complete!" -ForegroundColor Green
Write-Host "You can now run: php artisan serve" -ForegroundColor Cyan
