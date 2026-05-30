# TPT Free ERP - Improvement Roadmap

## Status Legend
- [ ] Not Started
- [x] Completed
- [~] In Progress

---

## Laravel Migration Status

> The project is migrating from a custom PHP framework (`core/`, `api/`, `modules/`) to **Laravel 13.8**.
> Entry point (`public/index.php`) now bootstraps Laravel. Old code remains for reference until each module is replaced.

### Foundation (Done)
- [x] Laravel 13.8 scaffolding added (`bootstrap/`, `routes/`, `resources/`, `config/`, `artisan`)
- [x] API routes defined — `routes/api.php` (60+ endpoints across 11 modules)
- [x] Full ERP database schema — `database/migrations/2026_05_26_133000_create_erp_tables.php`
- [x] Base controllers — `app/Http/Controllers/Api/BaseApiController.php`, `AuthController.php`
- [x] User model — `app/Models/User.php`
- [x] Starter models — `app/Models/Finance/Account.php`, `Finance/Transaction.php`, `Inventory/Product.php`
- [x] All Eloquent models created — Finance, Inventory, HR, Sales, Procurement, Manufacturing, Projects, Quality, Assets, FieldService, Lms (see Models section below)
- [x] Missing tables migration — `database/migrations/2026_05_28_000000_create_missing_erp_tables.php` (Budget, TaxRate, Supplier, Payroll, CrmPipeline, Requisition+items, ProductionSchedule)

### Auth & Middleware
- [x] Configure Laravel Sanctum (replace custom JWT in `core/Request.php`)
- [x] Implement CORS middleware — `app/Http/Middleware/`
- [x] Implement rate limiting middleware — Laravel `throttle:api` applied to all authenticated routes in `routes/api.php`
- [x] Implement role/permission middleware (replace `api/middleware/`)
- [x] Implement CSRF protection

### Controllers (create in `app/Http/Controllers/Api/`)
- [x] `Finance/AccountController.php`
- [x] `Finance/TransactionController.php`
- [x] `Finance/ReportController.php`
- [x] `Finance/JournalEntryController.php`
- [x] `Inventory/ProductController.php`
- [x] `Inventory/WarehouseController.php`
- [x] `Inventory/StockMovementController.php`
- [x] `Inventory/CategoryController.php`
- [x] `HR/EmployeeController.php`
- [x] `HR/DepartmentController.php`
- [x] `HR/LeaveController.php` (LeaveRequestController)
- [x] `HR/PayrollController.php`
- [x] `HR/AttendanceController.php`
- [x] `Sales/CustomerController.php`
- [x] `Sales/OrderController.php`
- [x] `Sales/InvoiceController.php`
- [x] `Sales/CrmController.php`
- [x] `Procurement/VendorController.php`
- [x] `Procurement/PurchaseOrderController.php`
- [x] `Manufacturing/BomController.php`
- [x] `Manufacturing/WorkOrderController.php`
- [x] `Projects/ProjectController.php`
- [x] `Projects/TaskController.php`
- [x] `Projects/TimeEntryController.php`
- [x] `Quality/QualityCheckController.php` (CheckController + NonConformanceController)
- [x] `Assets/AssetController.php` (Asset/AssetController + Asset/MaintenanceController)
- [x] `FieldService/ServiceTicketController.php` (FieldService/TicketController)
- [x] `Lms/CourseController.php` (LMS/CourseController + LMS/EnrollmentController)

### Eloquent Models (create in `app/Models/`)
- [x] `Finance/Budget.php`, `Finance/TaxRate.php`, `Finance/JournalEntry.php`
- [x] `Inventory/Warehouse.php`, `Inventory/StockMovement.php`, `Inventory/Supplier.php`
- [x] `HR/Employee.php`, `HR/Department.php`, `HR/LeaveRequest.php`, `HR/Payroll.php`
- [x] `Sales/Customer.php`, `Sales/Order.php`, `Sales/Invoice.php`, `Sales/CrmPipeline.php`
- [x] `Procurement/Vendor.php`, `Procurement/PurchaseOrder.php`, `Procurement/Requisition.php`
- [x] `Manufacturing/Bom.php`, `Manufacturing/WorkOrder.php`, `Manufacturing/ProductionSchedule.php`
- [x] `Projects/Project.php`, `Projects/Task.php`, `Projects/TimeEntry.php`
- [x] `Quality/QualityCheck.php`, `Quality/NonConformance.php`
- [x] `Assets/Asset.php`, `Assets/MaintenanceRecord.php`
- [x] `FieldService/ServiceTicket.php`
- [x] `Lms/Course.php`, `Lms/Enrollment.php`

### Form Request Validators (create in `app/Http/Requests/`)
- [x] Auth requests (login, register, TOTP, magic link, password reset)
- [x] Finance requests (account, transaction, report)
- [x] Inventory requests (product, warehouse, stock movement)
- [x] HR requests (employee, department, leave, payroll)
- [x] Sales requests (customer, order, invoice, CRM pipeline)
- [x] Procurement, Manufacturing, Projects requests (vendor, PO, BOM, work order, project, task)

### Database Migrations (add to `database/migrations/`)
- [x] `create_error_logs_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_security_events_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_behavioral_data_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_email_queue_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_magic_link_tokens_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_user_auth_methods_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_gdpr_requests_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_notifications_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_user_consents_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_auth_backup_codes_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_audit_log_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_user_sessions_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_user_devices_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_legal_holds_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_user_disputes_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_user_objections_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_data_processing_log_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_password_history_table` — in `2026_05_26_133000_create_erp_tables.php`
- [x] `create_team_behavioral_settings_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_company_behavioral_settings_table` — in `2026_05_29_000000_create_gdpr_and_device_tables.php`
- [x] `create_behavioral_analysis_table` — in `2026_05_26_133000_create_erp_tables.php`

### Database Seeding
- [x] Configure `database/seeders/DatabaseSeeder.php`
- [x] Create seeders for core reference data (roles, permissions, currencies, tax rates)
- [x] Create factory classes for testing (Finance/Account, Finance/Transaction, Inventory/Product, Inventory/Warehouse, HR/Employee, HR/Department)

### Cleanup (after each module is working in Laravel)
- [ ] Remove `api/controllers/` equivalents
- [ ] Remove `modules/` class equivalents
- [ ] Remove `core/` once all features are covered by Laravel

---

## Phase 1: Critical Security & Runtime Fixes

### SQL Injection Vulnerabilities
- [x] **Database.php line 250** — Fix `$limit`/`$offset` string interpolation in `findBy()` using parameterized query
- [x] **BehavioralBiometrics.php line 505** — Fix `$userId` concatenation in `getBehavioralAnalytics()`
- [x] **Notification.php lines 328-329** — Fix `$userId` concatenation in `getStats()`
- [x] **Database.php line 335** — Fix `quoteIdentifier()` using wrong PDO method for identifier quoting
- [x] **SessionManager.php line 212** — Fix `session_id()` typo (should be `session_id()`)
- [x] **Notification.php line 328** — Fix inline SQL variable interpolation

### Security Defaults
- [x] **Request.php line 100** — Remove hardcoded `'your-secret-key'` JWT default; throw exception if not configured
- [x] **Application.php line 97** — Change CORS wildcard `*` default to restrict to configured origins (with allowlist)
- [x] **Database.php line 64** — Remove credentials from PostgreSQL DSN string

### Runtime Errors
- [x] Create `storage/` directory structure
- [x] Create `logs/` directory structure
- [x] Create `storage/cache/` directory
- [x] Create `storage/uploads/` directory
- [x] Create `storage/uploads/thumbnails/` directory

### Class/Missing References
- [x] Fix `config/app.php` — Remove references to non-existent service providers and facades
- [x] Create `core/helpers.php` — Add `env()`, `base_path()`, `storage_path()`, `config_path()`, `public_path()`, `database_path()` helper functions
- [x] Fix `config/bootstrap.php` — Include helpers, use safeLoad(), proper error reporting
- [x] Update `.env.example` — Complete configuration with all documented settings

### Code Quality (still applies to existing `core/` code until retired)
- [ ] Fix all `// Ignore errors` catch blocks to properly log errors
- [ ] Add proper input validation layer (use Form Requests in Laravel controllers)
- [ ] Implement proper error responses (non-500 for validation errors)

---

## Phase 2: Architecture Modernization

> Most of Phase 2 is fulfilled by the Laravel migration.

- [x] **Dependency Injection** — Laravel's IoC container replaces manual `getInstance()` calls
- [x] **Eloquent ORM** — replaces custom `Database.php` raw SQL (models still need creating — see Laravel Migration section)
- [x] **Migration system** — Laravel migrations replace manual schema management
- [x] **Testing infrastructure** — `tests/`, `phpunit.xml` scaffolded by Laravel
- [x] Write unit tests for Auth flow (`tests/Feature/Auth/LoginTest.php`, `RegisterTest.php`)
- [x] Write feature tests for Finance module (`tests/Feature/Finance/AccountTest.php`, `TransactionTest.php`)
- [x] Write feature tests for Inventory module (`tests/Feature/Inventory/ProductTest.php`, `WarehouseTest.php`)
- [x] Write feature tests for HR module (`tests/Feature/HR/EmployeeTest.php`, `LeaveRequestTest.php`)
- [x] Implement proper exception hierarchy — `app/Exceptions/ErpException.php`, `BusinessLogicException.php`, `ResourceNotFoundException.php`, `ForbiddenException.php`
- [x] Create HTTP exception classes and register JSON renderers in `bootstrap/app.php`
- [ ] Remove silent catch blocks in legacy `core/` code

---

## Phase 3: Business Logic Implementation

> Implement using Laravel controllers + Eloquent models + service classes.
> Reference existing logic in `modules/` but do not port monolithic files directly.
> All service classes created in `app/Services/` with full business logic for each module.

### Finance Module
- [x] Chart of Accounts management — `app/Services/Finance/AccountService.php`
- [x] General Ledger entries — `app/Services/Finance/JournalService.php`
- [x] Accounts Payable / Receivable — `app/Services/Finance/TransactionService.php`
- [x] Financial statements (Balance Sheet, P&L) — `app/Services/Finance/AccountService.php`
- [x] Budget management — `app/Services/Finance/BudgetService.php`
- [x] Tax management — `app/Services/Finance/TaxService.php`
- [x] Cash flow reports — `app/Services/Finance/JournalService.php`

### Inventory Module
- [x] Product catalog management — `app/Services/Inventory/ProductService.php`
- [x] Stock tracking and adjustments — `app/Services/Inventory/ProductService.php`
- [x] Warehouse management — existing `WarehouseController.php`
- [x] Inventory valuation methods (FIFO, LIFO, Average) — `app/Services/Inventory/ProductService.php`
- [x] Stock transfers — `app/Services/Inventory/ProductService.php`
- [x] Barcode/RFID integration — model field `barcode` on Product model

### HR Module
- [x] Employee records management — `app/Services/HR/EmployeeService.php`
- [x] Organizational chart — `app/Services/HR/EmployeeService.php`
- [x] Attendance tracking — `AttendanceController.php`
- [x] Leave management — `app/Services/HR/LeaveService.php`
- [x] Payroll processing — `app/Services/HR/PayrollService.php`
- [x] Performance reviews — `app/Services/HR/EmployeeService.php`
- [x] Leave balance tracking — `app/Services/HR/EmployeeService.php`

### Manufacturing Module
- [x] Bill of Materials (BOM) — `app/Services/Manufacturing/ManufacturingService.php`
- [x] Work orders — `app/Services/Manufacturing/ManufacturingService.php`
- [x] Production scheduling — `app/Services/Manufacturing/ManufacturingService.php`
- [x] Quality control integration — `app/Services/Quality/QualityService.php`
- [x] Costing — `app/Services/Manufacturing/ManufacturingService.php`

### Procurement Module
- [x] Purchase orders — `app/Services/Procurement/ProcurementService.php`
- [x] Vendor management — `VendorController.php` + `ProcurementService.php`
- [x] Requisition workflow — `app/Services/Procurement/ProcurementService.php`
- [x] Purchase contracts — PO status workflow
- [x] Goods receipt — `app/Services/Procurement/ProcurementService.php`

### Sales Module
- [x] Quotes and proposals — `OrderController.php` + `SalesService.php`
- [x] Sales orders — `app/Services/Sales/SalesService.php`
- [x] Invoicing — `app/Services/Sales/SalesService.php`
- [x] CRM pipeline — `CrmController.php` + `SalesService.php`
- [x] Customer management — `CustomerController.php`
- [x] Sales forecasting — `app/Services/Sales/SalesService.php`

### Project Management
- [x] Project planning — `ProjectController.php` + `ProjectService.php`
- [x] Task assignments — `TaskController.php`
- [x] Time tracking — `TimeEntryController.php`
- [x] Gantt charts — `app/Services/Projects/ProjectService.php`
- [x] Resource allocation — `app/Services/Projects/ProjectService.php`

### Additional Service Classes Created
- [x] `app/Services/Quality/QualityService.php` — Quality check management
- [x] `app/Services/Assets/AssetService.php` — Asset lifecycle & depreciation
- [x] `app/Services/FieldService/FieldServiceService.php` — Ticket management
- [x] `app/Services/Lms/LearningService.php` — Course enrollment management
- [x] `app/Models/Finance/JournalEntryLine.php` — Missing model for GL lines
- [x] `app/Services/TOTPService.php` — 2FA support

---

## Phase 4: Frontend Modernization

### Framework Migration
- [x] Choose framework — Vue 3 + Pinia
- [x] Set up Vite build system (`vite.config.ts`)
- [x] Add TypeScript support (`tsconfig.json`, `env.d.ts`)
- [x] Configure ESLint + Prettier (`package.json`)
- [x] Set up routing — `resources/js/router/index.ts`

### UI Components
- [x] Data table component with sorting/filtering — `resources/js/components/DataTable.vue`
- [x] Form components with validation — inline in each view
- [x] Modal/dialog system — `resources/js/components/ModalDialog.vue`
- [x] Notification system — `resources/js/components/NotificationContainer.vue`
- [x] Dashboard widgets — `resources/js/views/DashboardView.vue`
- [x] Navigation (sidebar, breadcrumbs) — `resources/js/layouts/MainLayout.vue`, `Breadcrumbs.vue`
- [x] Authentication pages (login, register, password reset) — `resources/js/views/auth/`

### Module Pages
- [x] Dashboard with KPI cards — `resources/js/views/DashboardView.vue`
- [x] Finance pages (accounts, transactions, reports) — `resources/js/views/finance/`
- [x] Inventory pages (products, warehouses, stock movements) — `resources/js/views/inventory/`
- [x] HR pages (employees, departments, leave requests, payroll) — `resources/js/views/hr/`
- [x] Manufacturing pages (BOMs, work orders) — `resources/js/views/manufacturing/`
- [x] Procurement pages (vendors, purchase orders) — `resources/js/views/procurement/`
- [x] Sales pages (customers, orders, invoices, CRM) — `resources/js/views/sales/`
- [x] Projects pages (projects, tasks) — `resources/js/views/projects/`
- [x] Quality checks — `resources/js/views/quality/`
- [x] Assets — `resources/js/views/assets/`
- [x] Field Service tickets — `resources/js/views/field-service/`
- [x] LMS courses — `resources/js/views/lms/`
- [ ] Reports builder

### Build & Performance
- [x] Lazy loading routes — all routes use `() => import(...)` dynamic imports
- [ ] Code splitting (advanced chunking config)
- [ ] Asset bundling/minification
- [ ] Image optimization
- [ ] PWA offline support
- [ ] Bundle size analysis

---

## Phase 5: Infrastructure & DevOps

### CI/CD
- [ ] GitHub Actions workflow for PHPUnit
- [ ] GitHub Actions for PHPStan analysis
- [ ] GitHub Actions for frontend build
- [ ] Docker optimization (multi-stage builds)
- [ ] Automated deployment scripts

### Monitoring
- [ ] Centralized logging (use Laravel's Log facade + channels)
- [ ] Application monitoring
- [ ] Performance profiling
- [ ] Error tracking
- [ ] Server health checks

### Performance
- [ ] Implement Redis caching for all frequent queries (use Laravel Cache facade)
- [ ] Database query optimization
- [ ] Add proper database indexes
- [ ] CDN configuration for static assets
- [ ] Database read replicas

### API Documentation
- [ ] Implement OpenAPI/Swagger
- [ ] Auto-generate API docs
- [ ] Interactive API explorer
- [ ] Rate limiting per endpoint (use Laravel's `throttle` middleware)

---

## Notes

- **Priority**: Get Auth + one complete module (Finance) working end-to-end in Laravel first
- **Testing**: Every new module must have minimum 70% test coverage
- **Security**: All endpoints must have proper authorization checks
- **Performance**: Every new feature must consider caching strategy
- **Cleanup**: Remove `api/`/`modules/`/`core/` equivalents once replaced by Laravel
