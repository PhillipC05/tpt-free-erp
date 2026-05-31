# TPT Free ERP — Windows installer
# Run from the project root: .\install.ps1

$ErrorActionPreference = "Stop"

function Write-Step($msg) { Write-Host "`n==> $msg" -ForegroundColor Cyan }
function Write-Ok($msg)   { Write-Host "    [OK] $msg" -ForegroundColor Green }
function Write-Fail($msg) { Write-Host "    [!]  $msg" -ForegroundColor Red; exit 1 }

Write-Host @"
  _____ ____ _____ _____              ______ _____  ____
 |_   _|  _ \_   _|  ___|            |  ____|  __ \|  _ \
   | | | |_) || | | |_  _ __ ___  ___| |__  | |__) | |_) |
   | | |  __/ | | |  _|| '__/ _ \/ _ \  __| |  _  /|  __/
  _| |_| |    | | | |  | | |  __/  __/ |____| | \ \| |
 |_____|_|    |_| |_|  |_|  \___|\___|______|_|  \_\_|

  Open-Source ERP on Laravel 13
"@ -ForegroundColor White

# ── 1. Check prerequisites ────────────────────────────────────────────────────

Write-Step "Checking prerequisites..."

# PHP
try {
    $phpVer = (php -r "echo PHP_VERSION;") 2>&1
    if ($LASTEXITCODE -ne 0) { throw }
    $major, $minor = $phpVer.Split('.')[0,1]
    if ([int]$major -lt 8 -or ([int]$major -eq 8 -and [int]$minor -lt 3)) {
        Write-Fail "PHP 8.3+ required. Found $phpVer. Install Laravel Herd: https://herd.laravel.com/"
    }
    Write-Ok "PHP $phpVer"
} catch {
    Write-Fail "PHP not found. Install Laravel Herd (includes PHP 8.3): https://herd.laravel.com/"
}

# Composer
try {
    $composerVer = (composer --version --no-ansi 2>&1) -replace 'Composer version ',''
    Write-Ok "Composer $($composerVer.Split(' ')[0])"
} catch {
    Write-Fail "Composer not found. Install from: https://getcomposer.org/Composer-Setup.exe"
}

# Node.js
try {
    $nodeVer = (node --version) 2>&1
    $nodeMajor = [int]($nodeVer -replace 'v','').Split('.')[0]
    if ($nodeMajor -lt 18) {
        Write-Fail "Node.js 18+ required. Found $nodeVer. Install from: https://nodejs.org/"
    }
    Write-Ok "Node.js $nodeVer"
} catch {
    Write-Fail "Node.js not found. Install from: https://nodejs.org/"
}

# ── 2. Install PHP dependencies ───────────────────────────────────────────────

Write-Step "Installing PHP dependencies..."
composer install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) { Write-Fail "composer install failed" }
Write-Ok "PHP dependencies installed"

# ── 3. Set up .env ────────────────────────────────────────────────────────────

Write-Step "Configuring environment..."
if (-not (Test-Path ".env")) {
    Copy-Item ".env.example" ".env"
    Write-Ok "Created .env from .env.example"
} else {
    Write-Ok ".env already exists — skipping copy"
}

# ── 4. Create SQLite database file ───────────────────────────────────────────

Write-Step "Creating database..."
if (-not (Test-Path "database\database.sqlite")) {
    New-Item -ItemType File "database\database.sqlite" | Out-Null
    Write-Ok "Created database\database.sqlite"
} else {
    Write-Ok "database.sqlite already exists"
}

# ── 5. Generate app key ───────────────────────────────────────────────────────

Write-Step "Generating application key..."
php artisan key:generate --ansi
if ($LASTEXITCODE -ne 0) { Write-Fail "key:generate failed" }

# ── 6. Run migrations ─────────────────────────────────────────────────────────

Write-Step "Running database migrations..."
php artisan migrate --force --ansi
if ($LASTEXITCODE -ne 0) { Write-Fail "migrate failed" }
Write-Ok "All migrations applied"

# ── 7. Install JS dependencies and build frontend ────────────────────────────

Write-Step "Installing Node.js dependencies..."
npm install --ignore-scripts
if ($LASTEXITCODE -ne 0) { Write-Fail "npm install failed" }

Write-Step "Building frontend assets..."
npm run build
if ($LASTEXITCODE -ne 0) { Write-Fail "npm run build failed" }
Write-Ok "Frontend built"

# ── Done ──────────────────────────────────────────────────────────────────────

Write-Host @"

  Setup complete!

  Start the dev server:   composer run dev
  Open in browser:        http://localhost:8000
  API documentation:      http://localhost:8000/api/documentation
  Run tests:              composer run test

"@ -ForegroundColor Green
