# Changelog

All notable changes to TPT Free ERP are documented here.

## [1.0.0] - 2025-06-23

### Added

**Core Platform**
- Laravel 13.8 (PHP 8.3+) backend with full REST API
- Vue 3 + Pinia + Tailwind CSS 4 frontend (single-page application)
- Laravel Sanctum token-based authentication with magic link and TOTP 2FA support
- Role-based access control middleware
- Redis tag-based cache invalidation with graceful database fallback
- Multi-database support: SQLite (default), MySQL, PostgreSQL
- OpenAPI / Swagger documentation for all 59+ endpoints (`/api/documentation`)
- 191 feature tests across all modules (SQLite in-memory, no external DB required)

**Modules**
- **Finance** — Chart of accounts, transactions, journal entries, balance sheet, income statement, cash flow statement, trial balance
- **Inventory** — Products, categories, warehouses, stock movements, FIFO/LIFO/average valuation
- **HR** — Employees, departments, attendance, leave requests, payroll
- **Sales** — Customers, sales orders, invoices, CRM pipeline
- **Procurement** — Vendors, purchase orders
- **Manufacturing** — Bills of materials, work orders
- **Projects** — Projects, tasks, time entries
- **Quality** — Quality checks, non-conformances with root-cause tracking
- **Assets** — Asset lifecycle, straight-line/declining/sum-of-years depreciation, maintenance records
- **Field Service** — Service tickets with priority and assignment
- **LMS** — Courses and enrollments

**Security**
- Auth endpoints rate-limited (5 requests/minute)
- TOTP secret and magic link tokens excluded from all API responses
- GDPR compliance tools (data export, right-to-erasure)
- Audit logging for login, password changes, role changes, data access/modification
- CSRF protection and session management

**Developer Experience**
- `composer run setup` — one-command first-time setup
- `composer run dev` — full dev environment (PHP + queue + Vite, concurrently)
- `composer run test` — all 191 tests in under 30 seconds
- Laravel Pint code formatting
- 65+ database performance indexes

[1.0.0]: https://github.com/PhillipC05/tpt-free-erp/releases/tag/v1.0.0
