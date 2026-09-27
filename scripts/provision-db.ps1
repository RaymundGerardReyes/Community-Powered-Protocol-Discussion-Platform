<#
.SYNOPSIS
    Provisions and switches database environments for the Protocol Platform.
.DESCRIPTION
    Supports dual-mode execution:
      - Standalone (Default fallback): Zero-dependency local SQLite, file cache, sync queue, null scout.
      - Docker: PostgreSQL 17 on port 5433 with Typesense on port 8108.
.PARAMETER Mode
    'Auto' (default), 'Standalone', or 'Docker'
#>
[CmdletBinding()]
param(
    [ValidateSet('Auto', 'Standalone', 'Docker')]
    [string]$Mode = 'Auto'
)

$ErrorActionPreference = 'Stop'
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$BackendDir = Join-Path $ProjectRoot 'backend'
$EnvFile = Join-Path $BackendDir '.env'
$SqliteFile = Join-Path $BackendDir 'database\database.sqlite'

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host " Protocol Platform Database Provisioner " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

# 1. Determine Target Mode
if ($Mode -eq 'Auto') {
    Write-Host "[1/4] Detecting environment services..." -ForegroundColor Yellow
    $pgPortActive = $false
    try {
        $tcp = Test-NetConnection -ComputerName 127.0.0.1 -Port 5433 -WarningAction SilentlyContinue
        $pgPortActive = $tcp.TcpTestSucceeded
    } catch {
        $pgPortActive = $false
    }

    if ($pgPortActive) {
        Write-Host "       PostgreSQL container detected on 127.0.0.1:5433 -> Selecting Docker Mode" -ForegroundColor Green
        $TargetMode = 'Docker'
    } else {
        Write-Host "       Port 5433 is inactive (Docker/WSL offline) -> Selecting Standalone Mode (SQLite)" -ForegroundColor Magenta
        $TargetMode = 'Standalone'
    }
} else {
    $TargetMode = $Mode
    Write-Host "[1/4] Explicit Mode Selected: $TargetMode" -ForegroundColor Yellow
}

# Helper to update or append .env key-value
function Set-EnvVar([string]$FilePath, [string]$Key, [string]$Value) {
    $content = Get-Content $FilePath
    $pattern = "^$Key=.*$"
    if ($content -match $pattern) {
        $content = $content -replace $pattern, "$Key=$Value"
    } else {
        $content += "$Key=$Value"
    }
    Set-Content $FilePath $content -Encoding utf8
}

# 2. Configure Backend .env
Write-Host "[2/4] Configuring backend/.env for $TargetMode Mode..." -ForegroundColor Yellow

if (-not (Test-Path $EnvFile)) {
    $EnvExample = Join-Path $BackendDir '.env.example'
    if (Test-Path $EnvExample) {
        Copy-Item $EnvExample $EnvFile
    } else {
        New-Item $EnvFile -ItemType File | Out-Null
    }
}

if ($TargetMode -eq 'Standalone') {
    Set-EnvVar $EnvFile 'DB_CONNECTION' 'sqlite'
    Set-EnvVar $EnvFile 'DB_DATABASE' 'database/database.sqlite'
    Set-EnvVar $EnvFile 'CACHE_STORE' 'file'
    Set-EnvVar $EnvFile 'QUEUE_CONNECTION' 'sync'
    Set-EnvVar $EnvFile 'SESSION_DRIVER' 'file'
    Set-EnvVar $EnvFile 'SCOUT_DRIVER' 'null'

    # Ensure SQLite file exists
    if (-not (Test-Path $SqliteFile)) {
        New-Item $SqliteFile -ItemType File | Out-Null
    }
} else {
    Set-EnvVar $EnvFile 'DB_CONNECTION' 'pgsql'
    Set-EnvVar $EnvFile 'DB_HOST' '127.0.0.1'
    Set-EnvVar $EnvFile 'DB_PORT' '5433'
    Set-EnvVar $EnvFile 'DB_DATABASE' 'protocol_platform'
    Set-EnvVar $EnvFile 'DB_USERNAME' 'postgres'
    Set-EnvVar $EnvFile 'DB_PASSWORD' 'secret'
    Set-EnvVar $EnvFile 'CACHE_STORE' 'file'
    Set-EnvVar $EnvFile 'QUEUE_CONNECTION' 'sync'
    Set-EnvVar $EnvFile 'SESSION_DRIVER' 'file'
    Set-EnvVar $EnvFile 'SCOUT_DRIVER' 'typesense'
    Set-EnvVar $EnvFile 'TYPESENSE_HOST' 'localhost'
    Set-EnvVar $EnvFile 'TYPESENSE_PORT' '8108'
}

# 3. Locate PHP executable
Write-Host "[3/4] Checking PHP executable..." -ForegroundColor Yellow
$PhpExe = 'C:\tools\php\php.exe'
if (-not (Test-Path $PhpExe)) {
    $PhpCmd = Get-Command php -ErrorAction SilentlyContinue
    if ($PhpCmd) { $PhpExe = $PhpCmd.Source } else { $PhpExe = 'php' }
}

# 4. Migrate and Seed if Needed
Write-Host "[4/4] Verifying database schema and seeding..." -ForegroundColor Yellow
Push-Location $BackendDir
try {
    & $PhpExe artisan config:clear | Out-Null

    if ($TargetMode -eq 'Standalone') {
        # Check if protocols table has data
        $hasData = $false
        try {
            $count = & $PhpExe -r "echo file_exists('database/database.sqlite') && filesize('database/database.sqlite') > 50000 ? 'yes' : 'no';"
            if ($count -eq 'yes') { $hasData = $true }
        } catch {
            $hasData = $false
        }

        if (-not $hasData) {
            Write-Host "       Seeding initial database fixtures into SQLite..." -ForegroundColor Cyan
            & $PhpExe artisan migrate --force | Out-Null
            & $PhpExe artisan db:seed --force | Out-Null
        } else {
            Write-Host "       SQLite database is already populated with seed data." -ForegroundColor Green
        }
    } else {
        Write-Host "       Migrating PostgreSQL database..." -ForegroundColor Cyan
        & $PhpExe artisan migrate --force | Out-Null
    }

    Write-Host "==========================================" -ForegroundColor Green
    Write-Host " Provisioning Complete ($TargetMode Mode)!" -ForegroundColor Green
    Write-Host " You can now start the backend with:" -ForegroundColor White
    Write-Host "   cd $BackendDir" -ForegroundColor Gray
    Write-Host "   $PhpExe artisan serve --host=127.0.0.1 --port=8000" -ForegroundColor Gray
    Write-Host "==========================================" -ForegroundColor Green
} finally {
    Pop-Location
}
