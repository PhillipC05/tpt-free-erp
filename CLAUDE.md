# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TPT Free ERP is an open-source enterprise resource planning system built on **Laravel 13.8** (PHP 8.3+). It covers Finance, Inventory, HR, Sales, Procurement, Manufacturing, Projects, Quality, Asset Management, Field Service, and LMS modules.

The project is **mid-migration** from a custom PHP framework. The old framework (`core/`, `api/`, `modules/`) is legacy reference code being replaced module by module. All new work goes into the Laravel layer (`app/`, `routes/`, `database/`).

## Commands

```bash
# First-time setup
composer run setup

# Start full dev environment (PHP server + queue + log viewer + Vite, concurrently)
composer run dev

# Run all tests
composer run test

# Run a single test or filter by name
php artisan test --filter TestName
php artisan test tests/Feature/Finance/AccountTest.php

# Code formatting (Laravel Pint)
./vendor/bin/pint

# Database
php artisan migrate
php artisan migrate:fresh --seed

# Inspect registered routes
php artisan route:list --path=api
```

## Architecture

### Entry Point
`public/index.php` → `bootstrap/app.php` → Laravel kernel

**Important:** `routes/api.php` is defined but not yet registered in `bootstrap/app.php`. To activate API routes, add `api: __DIR__.'/../routes/api.php'` to the `withRouting()` call in [bootstrap/app.php](bootstrap/app.php).

### API Layer
All API controllers live under `app/Http/Controllers/Api/{Module}/` and extend [BaseApiController](app/Http/Controllers/Api/BaseApiController.php), which provides:
- `respondSuccess(string $message, mixed $data)` → `{"success": true, "message": ..., "data": ...}`
- `respondError(string $message, int $status, ?array $errors)` → `{"success": false, ...}`
- `respondCreated`, `respondNotFound`, `respondValidationError`
- `validate(array $data, array $rules)` — returns a `JsonResponse` on failure, `null` on pass

`BaseApiController` also provides default `index`, `store`, `show`, `update`, `destroy` implementations via an injected `$model` property. Override only what needs custom logic.

### Models
Eloquent models live under `app/Models/{Module}/` (e.g., `app/Models/Finance/Account.php`). The ERP database schema is fully defined in [database/migrations/2026_05_26_133000_create_erp_tables.php](database/migrations/2026_05_26_133000_create_erp_tables.php) — consult it when creating new models.

### Frontend
Vite + Tailwind CSS 4. Entry points: `resources/css/app.css` and `resources/js/app.js`. No frontend framework chosen yet (Vue 3 + Pinia is planned).

### Testing
Tests use SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`) — no real database needed to run the test suite. Feature tests go in `tests/Feature/{Module}/`, unit tests in `tests/Unit/`.

### Legacy Code (do not extend)
| Directory | Status |
|-----------|--------|
| `core/` | Custom framework — being retired |
| `api/controllers/` | Old controllers — being replaced by `app/Http/Controllers/Api/` |
| `modules/` | Monolithic business logic — use as reference when implementing Laravel services |

When a module is fully working in Laravel, delete the corresponding legacy files.

## Adding a New Module

1. Create Eloquent models in `app/Models/{Module}/`
2. Create controllers in `app/Http/Controllers/Api/{Module}/` extending `BaseApiController`
3. Register routes in `routes/api.php` under the appropriate `Route::middleware('auth:sanctum')->group()`
4. Add any missing tables as new migration files in `database/migrations/`
5. Add feature tests in `tests/Feature/{Module}/`
