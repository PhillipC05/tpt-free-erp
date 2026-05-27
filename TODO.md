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

### Auth & Middleware
- [x] Configure Laravel Sanctum (replace custom JWT in `core/Request.php`)
- [x] Implement CORS middleware — `app/Http/Middleware/`
- [~] Implement rate limiting middleware
- [x] Implement role/permission middleware (replace `api/middleware/`)
- [x] Implement CSRF protection

### Controllers (create in `app/Http/Controllers/Api/`)
- [ ] `Finance/AccountController.php`
- [ ] `Finance/TransactionController.php`
- [ ] `Finance/ReportController.php`
- [ ] `Inventory/ProductController.php`
- [ ] `Inventory/WarehouseController.php`
- [ ] `Inventory/StockMovementController.php`
- [ ] `HR/EmployeeController.php`
- [ ] `HR/DepartmentController.php`
- [ ] `HR/LeaveController.php`
- [ ] `HR/PayrollController.php`
- [ ] `Sales/CustomerController.php`
- [ ] `Sales/OrderController.php`
- [ ] `Sales/InvoiceController.php`
- [ ] `Sales/CrmController.php`
- [ ] `Procurement/VendorController.php`
- [ ] `Procurement/PurchaseOrderController.php`
- [ ] `Manufacturing/BomController.php`
- [ ] `Manufacturing/WorkOrderController.php`
- [ ] `Projects/ProjectController.php`
- [ ] `Projects/TaskController.php`
- [ ] `Quality/QualityCheckController.php`
- [ ] `Assets/AssetController.php`
- [ ] `FieldService/ServiceTicketController.php`
- [ ] `Lms/CourseController.php`

### Eloquent Models (create in `app/Models/`)
- [ ] `Finance/Budget.php`, `Finance/TaxRate.php`, `Finance/JournalEntry.php`
- [ ] `Inventory/Warehouse.php`, `Inventory/StockMovement.php`, `Inventory/Supplier.php`
- [ ] `HR/Employee.php`, `HR/Department.php`, `HR/LeaveRequest.php`, `HR/Payroll.php`
- [ ] `Sales/Customer.php`, `Sales/Order.php`, `Sales/Invoice.php`, `Sales/CrmPipeline.php`
- [ ] `Procurement/Vendor.php`, `Procurement/PurchaseOrder.php`, `Procurement/Requisition.php`
- [ ] `Manufacturing/Bom.php`, `Manufacturing/WorkOrder.php`, `Manufacturing/ProductionSchedule.php`
- [ ] `Projects/Project.php`, `Projects/Task.php`, `Projects/TimeEntry.php`
- [ ] `Quality/QualityCheck.php`, `Quality/NonConformance.php`
- [ ] `Assets/Asset.php`, `Assets/MaintenanceRecord.php`
- [ ] `FieldService/ServiceTicket.php`
- [ ] `Lms/Course.php`, `Lms/Enrollment.php`

### Form Request Validators (create in `app/Http/Requests/`)
- [ ] Auth requests (login, register, TOTP)
- [ ] Finance requests
- [ ] Inventory requests
- [ ] HR requests
- [ ] Sales requests
- [ ] Procurement, Manufacturing, Projects requests

### Database Migrations (add to `database/migrations/`)
- [ ] `create_error_logs_table`
- [ ] `create_security_events_table`
- [ ] `create_behavioral_data_table`
- [ ] `create_email_queue_table`
- [ ] `create_magic_link_tokens_table`
- [ ] `create_user_auth_methods_table`
- [ ] `create_gdpr_requests_table`
- [ ] `create_notifications_table`
- [ ] `create_user_consents_table`
- [ ] `create_auth_backup_codes_table`
- [ ] `create_audit_log_table`
- [ ] `create_user_sessions_table`
- [ ] `create_user_devices_table`
- [ ] `create_legal_holds_table`
- [ ] `create_user_disputes_table`
- [ ] `create_user_objections_table`
- [ ] `create_data_processing_log_table`
- [ ] `create_password_history_table`
- [ ] `create_team_behavioral_settings_table`
- [ ] `create_company_behavioral_settings_table`
- [ ] `create_behavioral_analysis_table`

### Database Seeding
- [ ] Configure `database/seeders/DatabaseSeeder.php`
- [ ] Create seeders for core reference data (roles, permissions, currencies, tax rates)
- [ ] Create factory classes for testing

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
- [ ] Write unit tests for Auth flow (`tests/Feature/Auth/`)
- [ ] Write feature tests for Finance module (`tests/Feature/Finance/`)
- [ ] Write feature tests for Inventory module
- [ ] Write feature tests for HR module
- [ ] Implement proper exception hierarchy using Laravel's `Handler.php`
- [ ] Create HTTP exception classes (extend `HttpException`)
- [ ] Remove silent catch blocks in legacy `core/` code

---

## Phase 3: Business Logic Implementation

> Implement using Laravel controllers + Eloquent models + service classes.
> Reference existing logic in `modules/` but do not port monolithic files directly.

### Finance Module
- [ ] Chart of Accounts management
- [ ] General Ledger entries
- [ ] Accounts Payable / Receivable
- [ ] Financial statements (Balance Sheet, P&L)
- [ ] Budget management
- [ ] Tax management

### Inventory Module
- [ ] Product catalog management
- [ ] Stock tracking and adjustments
- [ ] Warehouse management
- [ ] Inventory valuation methods (FIFO, LIFO, Average)
- [ ] Stock transfers
- [ ] Barcode/RFID integration

### HR Module
- [ ] Employee records management
- [ ] Organizational chart
- [ ] Attendance tracking
- [ ] Leave management
- [ ] Payroll processing
- [ ] Performance reviews
- [ ] Recruitment management

### Manufacturing Module
- [ ] Bill of Materials (BOM)
- [ ] Work orders
- [ ] Production scheduling
- [ ] Quality control integration
- [ ] Costing

### Procurement Module
- [ ] Purchase orders
- [ ] Vendor management
- [ ] Requisition workflow
- [ ] Purchase contracts
- [ ] Goods receipt

### Sales Module
- [ ] Quotes and proposals
- [ ] Sales orders
- [ ] Invoicing
- [ ] CRM pipeline
- [ ] Customer management
- [ ] Sales forecasting

### Project Management
- [ ] Project planning
- [ ] Task assignments
- [ ] Time tracking
- [ ] Gantt charts
- [ ] Resource allocation

---

## Phase 4: Frontend Modernization

### Framework Migration
- [ ] Choose framework (Vue 3 + Pinia recommended)
- [ ] Set up Vite build system (`vite.config.js` already present)
- [ ] Add TypeScript support
- [ ] Configure ESLint + Prettier
- [ ] Set up routing

### UI Components
- [ ] Data table component with sorting/filtering
- [ ] Form components with validation
- [ ] Modal/dialog system
- [ ] Notification system
- [ ] Dashboard widgets
- [ ] Navigation (sidebar, breadcrumbs)
- [ ] Authentication pages (login, register, password reset)

### Module Pages
- [ ] Dashboard with real KPIs
- [ ] Finance pages (chart of accounts, transactions, reports)
- [ ] Inventory pages (products, stock, warehouses)
- [ ] HR pages (employees, org chart, time tracking)
- [ ] Manufacturing pages (BOM, work orders)
- [ ] Procurement pages (POs, vendors)
- [ ] Sales pages (quotes, orders, CRM)
- [ ] Reports builder

### Build & Performance
- [ ] Code splitting
- [ ] Lazy loading routes
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
