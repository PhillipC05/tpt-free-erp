# TPT Free ERP

Open-source Enterprise Resource Planning system built on **Laravel 13.8** (PHP 8.3+). Covers Finance, Inventory, HR, Sales, Procurement, Manufacturing, Projects, Quality, Asset Management, Field Service, and LMS — 60+ REST API endpoints, 191 passing tests, interactive Swagger UI.

---

## Quick Start

### Prerequisites

| Tool | Version | Download |
|------|---------|----------|
| PHP | 8.3+ | [Laravel Herd](https://herd.laravel.com/) (Windows/macOS) or [php.net](https://windows.php.net/) |
| Composer | 2.x | [getcomposer.org](https://getcomposer.org/) |
| Node.js | 18+ | [nodejs.org](https://nodejs.org/) |
| Git | any | [git-scm.com](https://git-scm.com/) |

### One-command install

```bash
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup
```

`composer run setup` does everything: installs PHP and JS dependencies, copies `.env`, generates the app key, creates the SQLite database, runs all migrations, and builds the frontend.

### Start the dev server

```bash
composer run dev
```

Opens four concurrent processes (PHP server, queue, log viewer, Vite). Visit **http://localhost:8000**.

---

## Windows Installation (Step by Step)

The fastest path on Windows is [**Laravel Herd**](https://herd.laravel.com/) — a free installer that gives you PHP 8.3, Composer, and a zero-config web server in one click.

1. **Install Laravel Herd** from [herd.laravel.com](https://herd.laravel.com/) (free tier is sufficient)
2. **Install Node.js** from [nodejs.org](https://nodejs.org/) (LTS)
3. Open **PowerShell** and run:

```powershell
git clone https://github.com/PhillipC05/tpt-free-erp.git
cd tpt-free-erp
composer run setup
composer run dev
```

4. Open **http://localhost:8000**

> **No MySQL required.** The default setup uses SQLite, so there's nothing extra to install or configure.

### Alternative: install script

A PowerShell script is included for environments without Herd:

```powershell
.\install.ps1
```

It verifies prerequisites, runs setup, and prints the URL when done.

---

## Configuration

The setup command copies `.env.example` → `.env`. Key settings you may want to change:

```env
APP_NAME="TPT Free ERP"
APP_URL=http://localhost:8000

# Default: SQLite (no extra setup)
DB_CONNECTION=sqlite

# Switch to MySQL
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=tpt_erp
# DB_USERNAME=root
# DB_PASSWORD=

# Redis caching (optional but recommended for production)
# CACHE_STORE=redis
# REDIS_HOST=127.0.0.1
```

---

## Commands

```bash
# First-time setup (runs everything)
composer run setup

# Start full dev environment (server + queue + logs + Vite)
composer run dev

# Run all 191 tests
composer run test

# Run a single test file or filter
php artisan test tests/Feature/Finance/AccountTest.php
php artisan test --filter test_can_create_customer

# Database
php artisan migrate
php artisan migrate:fresh --seed   # reset + seed

# Code formatting (Laravel Pint)
./vendor/bin/pint

# Regenerate OpenAPI docs
php artisan l5-swagger:generate

# List all API routes
php artisan route:list --path=api
```

---

## API

All endpoints live under `/api/` and require a Sanctum Bearer token.

**Get a token:**
```bash
POST /api/auth/login
{ "email": "user@example.com", "password": "password" }
```

**Interactive docs:** `http://localhost:8000/api/documentation`

**Modules and key endpoints:**

| Module | Base path | Highlights |
|--------|-----------|------------|
| Auth | `/api/auth/` | Login, register, logout, profile |
| Finance | `/api/finance/` | Accounts, transactions, journal entries, balance sheet |
| Inventory | `/api/inventory/` | Products, warehouses, stock movements |
| HR | `/api/hr/` | Employees, departments, leave, payroll, attendance |
| Sales | `/api/sales/` | Customers, orders, invoices, CRM pipeline |
| Procurement | `/api/procurement/` | Vendors, purchase orders |
| Manufacturing | `/api/manufacturing/` | BOMs, work orders |
| Projects | `/api/projects/` | Projects, tasks, time entries |
| Quality | `/api/quality/` | Checks (pass/fail/conditional), non-conformances |
| Assets | `/api/assets/` | Asset lifecycle, straight-line depreciation, maintenance |
| Field Service | `/api/field-service/` | Service tickets |
| LMS | `/api/lms/` | Courses, enrollments |
| Reports | `/api/reports/` | Cross-module report generation |

---

## Architecture

```
app/
├── Http/Controllers/Api/     # 27 API controllers (one per module)
│   ├── BaseApiController.php # Shared CRUD, validation, Redis cache helpers
│   ├── OpenApiSpec.php       # Swagger annotations (59 documented paths)
│   └── {Module}/
├── Models/{Module}/          # Eloquent models
├── Services/{Module}/        # Business logic services
└── Exceptions/               # Custom exception hierarchy

database/
├── migrations/               # 9 migration files (full ERP schema + indexes)
└── factories/                # 24 model factories

tests/Feature/                # 191 feature tests (12 modules)
resources/js/                 # Vue 3 + Pinia frontend
routes/api.php                # 60+ API routes
```

**Stack:** Laravel 13.8 · PHP 8.3 · SQLite/MySQL/PostgreSQL · Vue 3 · Pinia · Vite · Tailwind CSS 4 · Laravel Sanctum · Swagger UI

**Performance:** Redis tag-based cache invalidation, 65+ database indexes on all filter/FK columns.

---

## Testing

Tests use an **in-memory SQLite database** — no database setup needed.

```bash
composer run test         # all 191 tests
php artisan test --filter Sales   # filter by name
```

Coverage: Auth, Finance, Inventory, HR, Sales, Procurement, Manufacturing, Projects, Quality, Assets, Field Service, LMS.

---

## Contributing

1. Fork the repo and create a branch
2. Write tests for any new feature (`tests/Feature/{Module}/`)
3. Run `./vendor/bin/pint` to format code
4. Open a PR — all 191 tests must pass

---

## License

Apache License 2.0 — see [LICENSE](LICENSE)

Copyright 2025 [TPT Solutions](https://github.com/TPT-Solutions)
