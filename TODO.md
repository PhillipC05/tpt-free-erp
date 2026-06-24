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

### Cleanup
- [x] Remove `api/controllers/` equivalents — deleted 2026-05-31
- [x] Remove `modules/` class equivalents — deleted 2026-05-31
- [x] Remove `core/` — deleted 2026-05-31 (also removed orphan `config/bootstrap.php`)

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
- [x] Reports builder — `resources/js/views/reports/ReportsBuilderView.vue` (Finance, Sales, Procurement, Projects with CSV export)

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
- [x] Implement Redis caching — `BaseApiController::cacheRemember/cacheFlush`, tag-based invalidation; enabled on 10 controllers
- [x] Database query optimization and add proper database indexes — 65+ indexes on FK, status, and date columns (`2026_05_31_095348_add_performance_indexes_to_erp_tables.php`)
- [ ] CDN configuration for static assets
- [ ] Database read replicas

### API Documentation
- [x] Implement OpenAPI/Swagger — `app/Http/Controllers/Api/OpenApiSpec.php`, 59 paths via PHP 8 attributes
- [x] Auto-generate API docs — `php artisan l5-swagger:generate`
- [x] Interactive API explorer — Swagger UI at `/api/documentation`
- [x] Rate limiting per endpoint — Laravel `throttle` middleware already in `routes/api.php`

---

---

## Phase 6: RBAC, New Modules & Platform Expansion (2026-06-23)

### RBAC — Critical Security Fix
- [x] Create `app/Models/Permission.php` — `roles()` BelongsToMany
- [x] Add `permissions()` BelongsToMany to `app/Models/Role.php`
- [x] Add `hasPermission()`, `hasAnyPermission()`, `flushPermissionCache()` to `app/Models/User.php`
- [x] Create `app/Http/Middleware/PermissionMiddleware.php` — per-request permission check with 5-min cache
- [x] Register `permission` middleware alias in `bootstrap/app.php`
- [x] Update `database/seeders/RoleSeeder.php` — fill permissions for all 8 roles (was only admin/viewer)
- [x] Create `app/Http/Controllers/Api/RoleController.php` — admin CRUD + sync permissions + assign/revoke user roles
- [x] Apply `permission:module.action` middleware to all module route groups in `routes/api.php`
- [x] Add `/api/v1/roles` admin-only routes in `routes/api.php`
- [x] Add permission indexes to `role_permissions` and `user_roles` tables for query performance — `2026_06_24_000001_add_permission_indexes.php` (role_id, permission_id, user_id, expires_at, deleted_at)
- [x] Write tests for role expiration enforcement — `tests/Feature/RoleExpirationTest.php` (9 tests; also fixed `RoleMiddleware` which was not checking `expires_at`)
- [x] Add standalone `PermissionSeeder` — `database/seeders/PermissionSeeder.php`; run via `php artisan db:seed --class=PermissionSeeder`

### API Versioning & Webhooks
- [x] Wrap all module routes in `Route::prefix('v1')` group — all endpoints now at `/api/v1/`
- [x] Create `database/migrations/2026_06_23_000001_create_webhooks_tables.php`
- [x] Create `app/Models/Webhook.php` + `app/Models/WebhookDelivery.php`
- [x] Create `app/Services/WebhookService.php` — dispatch events to subscriber URLs
- [x] Create `app/Jobs/WebhookDeliveryJob.php` — HMAC-SHA256 signed POST, exponential backoff, auto-disable after 10 failures
- [x] Create `app/Http/Controllers/Api/WebhookController.php` — user manages their own webhooks + test-fire endpoint
- [x] Add webhook routes to `routes/api.php` — `/api/v1/webhooks`
- [x] Wire `WebhookService::dispatch()` calls into key model events — Eloquent observers for `Transaction`, `Product`, `StockMovement`, `Order`, `Invoice`; registered in `AppServiceProvider`
- [x] Add webhook event filtering UI in frontend — `resources/js/views/WebhooksView.vue` (CRUD + checkbox event picker + test-fire); route `webhooks` added
- [x] Write tests for `WebhookDeliveryJob` — `tests/Feature/WebhookDeliveryJobTest.php` (8 tests: delivered, retry, max-attempts fail, auto-disable, inactive skip, already-delivered skip, failed() hook, HMAC header)
- [x] Complete OpenAPI spec coverage for all new endpoints — Marketing, Network, Webhooks tags added; 10 new endpoint annotations (campaign ROI, avatar upload, public profile, webhook CRUD + test + deliveries)

### Marketing Module
- [x] Migration: `marketing_campaigns`, `marketing_leads`, `campaign_analytics` tables
- [x] Models: `app/Models/Marketing/Campaign.php`, `Lead.php`, `CampaignAnalytic.php`
- [x] Factories: `database/factories/Marketing/CampaignFactory.php`, `LeadFactory.php`
- [x] Controllers: `Api/Marketing/CampaignController.php` + `LeadController.php`
- [x] Service: lead-to-customer conversion + add-to-CRM-pipeline in `LeadController`
- [x] Routes: `/api/v1/marketing/` with `permission:marketing.*` middleware
- [x] Frontend: `resources/js/views/marketing/CampaignsView.vue` + `LeadsView.vue`
- [x] Router + sidebar nav updated
- [x] Feature tests: `tests/Feature/Marketing/MarketingTest.php`
- [x] Add `CampaignAnalytic` seeding via a daily scheduled job — `app/Console/Commands/SeedCampaignAnalytics.php` (`marketing:seed-analytics`), runs daily at 01:00; supports `--date` for backfill
- [x] Add campaign ROI calculation endpoint — `GET /api/v1/marketing/campaigns/{id}/roi` returns `roi_percent`, `roas`, `cost_per_click`, `cost_per_acquisition`
- [ ] Email integration: campaign send via SMTP/Mailgun (Phase 7)

### Network Module (Professional Networking)
- [x] Migration: `user_profiles`, `user_profile_interests`, `user_follows`, `user_connections`, `network_posts`, `network_post_reactions`, `network_post_comments` tables
- [x] Models: `app/Models/Network/` — UserProfile, UserProfileInterest, UserFollow, UserConnection, NetworkPost, NetworkPostReaction, NetworkPostComment
- [x] User model: added `profile()` HasOne relation
- [x] Controllers: ProfileController, DiscoveryController, FollowController, ConnectionController, FeedController, PostController
- [x] Routes: `/api/v1/network/` (auth only, no module-level permission gate — self-service)
- [x] CRM bridge: `DiscoveryController::addToCrm()` + `addToLead()`
- [x] Frontend: NetworkFeedView, NetworkDiscoveryView, MyProfileView, ConnectionsView, FollowingView
- [x] Feature tests: `tests/Feature/Network/NetworkTest.php`
- [x] Add profile avatar upload endpoint — `POST /api/v1/network/profile/avatar` (multipart, stores in `storage/app/public/avatars/`); avatar picker UI added to `MyProfileView.vue`
- [x] Add `ProfileView.vue` (view someone else's public profile) — `resources/js/views/network/PublicProfileView.vue`; route `network.profile.public` at `/network/profiles/:id`; linked from Discovery cards
- [ ] Add post image/attachment support (Phase 7)
- [x] Add notification on connection request / new follower — `ConnectionController` and `FollowController` insert into `notifications` table with type `connection_request` / `new_follower`
- [x] Privacy: ensure non-discoverable profiles never appear in Discovery or Feed to non-connections — `FeedController` now filters to own posts + accepted connections + discoverable-followed users only

### Expense Management Module
- [x] Migration: `expense_categories`, `expense_reports`, `expense_items` tables
- [x] Models: `app/Models/Expenses/ExpenseReport.php`, `ExpenseItem.php`, `ExpenseCategory.php`
- [x] Factory: `database/factories/Expenses/ExpenseReportFactory.php`
- [x] Controller: `app/Http/Controllers/Api/Expenses/ExpenseController.php`
- [x] Routes: `/api/v1/expenses/` with `permission:expenses.*` middleware
- [x] Frontend: `resources/js/views/expenses/ExpensesView.vue`
- [x] Feature tests: `tests/Feature/Expenses/ExpenseTest.php`
- [ ] Add `ExpenseItem` CRUD endpoints (currently only ExpenseReport top-level)
- [ ] Add receipt upload endpoint (store in `storage/app/expenses/`)
- [ ] Add expense category seeder with common defaults (Meals, Travel, Accommodation, Software, etc.)
- [ ] Add expense summary dashboard widget

### Budget & Forecasting Module
- [x] Migration: `finance_budgets`, `budget_lines` tables
- [x] Models: `app/Models/Finance/Budget.php`, `BudgetLine.php`
- [x] Controller: `app/Http/Controllers/Api/Finance/BudgetController.php`
- [x] Routes: `/api/v1/finance/budgets/` with `permission:finance.*` middleware
- [x] Frontend: `resources/js/views/finance/BudgetsView.vue`
- [ ] Add budget vs actuals variance calculation endpoint
- [ ] Add budget line CRUD endpoints (`/api/v1/finance/budgets/{id}/lines`)
- [ ] Add budget approval workflow (draft → approved)
- [ ] Write feature tests: `tests/Feature/Finance/BudgetTest.php`

### Document Management Module
- [x] Migration: `document_folders`, `documents` tables (polymorphic `documentable`)
- [x] Models: `app/Models/Documents/Document.php`, `DocumentFolder.php`
- [x] Controller: `app/Http/Controllers/Api/Documents/DocumentController.php`
- [x] Routes: `/api/v1/documents/` with `permission:documents.*` middleware
- [x] Frontend: `resources/js/views/documents/DocumentsView.vue`
- [x] Feature tests: `tests/Feature/Documents/DocumentTest.php`
- [ ] Add actual file upload via `Storage::disk('local')` (currently stores metadata only)
- [ ] Add document version history tracking
- [ ] Add document sharing endpoint (`/api/v1/documents/{id}/share`)
- [ ] Add polymorphic document attachment to Invoice, Contract, Employee, Asset views

### Contract Management Module
- [x] Migration: `contracts`, `contract_milestones` tables
- [x] Models: `app/Models/Contracts/Contract.php`, `ContractMilestone.php`
- [x] Factory: `database/factories/Contracts/ContractFactory.php`
- [x] Controller: `app/Http/Controllers/Api/Contracts/ContractController.php`
- [x] Routes: `/api/v1/contracts/` with `permission:contracts.*` middleware
- [x] Frontend: `resources/js/views/contracts/ContractsView.vue`
- [x] Feature tests: `tests/Feature/Contracts/ContractTest.php`
- [ ] Add milestone CRUD endpoints (`/api/v1/contracts/{id}/milestones`)
- [ ] Add contract expiry alert (auto-notify 30/7/1 days before end_date)
- [x] Add in-house e-signature module — `ESignature` model, controller, migration, factory, 14 tests, Vue management view + public signing page with canvas/typed modes, token-based audit trail with SHA-256 tamper detection

### Onboarding Wizard (15-Industry Preset)
- [x] Migration: `onboarding_presets`, `onboarding_completions` tables
- [x] Models: `app/Models/OnboardingPreset.php`, `app/Models/OnboardingCompletion.php`
- [x] Controller: `app/Http/Controllers/Api/OnboardingController.php` — presets/status/apply/skip
- [x] Routes: `/api/v1/onboarding/` (auth, no permission gate)
- [x] Seeder: `database/seeders/OnboardingPresetSeeder.php` — all 15 industries with full CoA + department templates
- [x] Added `OnboardingPresetSeeder` to `DatabaseSeeder`
- [x] Frontend: `resources/js/views/onboarding/OnboardingWizardView.vue` — 5-step wizard
- [x] Pinia store: `resources/js/stores/onboarding.ts`
- [x] Auth store: checks onboarding status after login, sets `onboardingPending` flag
- [x] Feature tests: `tests/Feature/OnboardingTest.php`
- [ ] Trigger onboarding wizard redirect in `MainLayout.vue` when `onboardingPending` is true
- [ ] Add "Re-run onboarding" option in Settings
- [ ] Add industry preset import for existing accounts (don't duplicate existing CoA codes)

### GitHub URL & Housekeeping
- [x] Update `README.md` — clone URL already correct (`PhillipC05`)
- [x] Update `composer.json` — homepage/support URLs updated to `PhillipC05`
- [x] Update `package.json` — repository URL updated to `PhillipC05`
- [x] Update `CHANGELOG.md` — release URL updated to `PhillipC05`

### Future Modules (Phase 7)
- [ ] **Point of Sale (POS)** — for Retail & Hospitality industries
- [ ] **Fleet Management** — for Transportation & Field Services
- [ ] **Subscription/Recurring Billing** — for Technology/SaaS
- [ ] **Donor/Grant Management** — for Non-Profit sector
- [x] **E-signature Module** — in-house, token-based, audit trail + SHA-256 tamper detection (linked to Contracts + Documents)
- [ ] **Email Campaign Sending** — link to Marketing module
- [ ] **API rate limiting tiers** — premium vs. standard per-user limits
- [ ] **Developer Portal** — self-service API key management + usage analytics
- [ ] **Push Notifications** — web push for tickets, approvals, alerts

---

## Phase 7: AI Agents, Skills System & Reporting Enhancement (2026-06-24)

### Reporting Enhancement
- [x] Create `app/Console/Kernel.php` — Laravel console kernel (was missing)
- [x] Create `app/Console/Commands/RunScheduledReports.php` — `php artisan reports:run-scheduled`
- [x] Create `app/Console/Commands/RunAgentSchedules.php` — `php artisan agents:run-schedules`
- [x] Migration: `generated_reports` + `scheduled_reports` tables — `2026_06_24_000001_create_reporting_tables.php`
- [x] Create `app/Jobs/ReportGenerationJob.php` — async report execution (8 report types: trial balance, income statement, balance sheet, cash flow, HR attendance, HR payroll, sales summary, procurement)
- [x] Wire `app/Http/Controllers/Api/ReportController.php` — `generate()`, `show()`, `download()`, `scheduledIndex/Store/Destroy()`
- [x] Update `routes/api.php` — `POST /reports/generate`, `GET /reports/{id}`, `GET /reports/{id}/download`, `GET/POST/DELETE /reports/scheduled`
- [x] Frontend: `resources/js/views/reports/ScheduledReportsView.vue` — manage scheduled reports
- [x] Router + sidebar updated (Scheduled Reports link under Finance/Reports)
- [x] Write feature tests: `tests/Feature/ReportGenerationTest.php` — queue, poll status, download CSV, ownership isolation, scheduled CRUD
- [x] Add PDF export support via `barryvdh/laravel-dompdf` — added to composer.json, `toPdf()` in ReportGenerationJob, download streams PDF with correct Content-Type
- [x] Add report expiry cleanup command: `app/Console/Commands/CleanExpiredReports.php` — `php artisan reports:clean-expired` (runs daily via scheduler)
- [ ] Add per-report caching so identical parameters within 1 hour reuse existing result

### AI Agent Infrastructure
- [x] Migration: `agent_profiles`, `agent_tokens`, `agent_skill_assignments`, `agent_executions`, `agent_schedules` — `2026_06_24_000002_create_agent_infrastructure_tables.php`
- [x] Models: `app/Models/Agent/` — AgentProfile, AgentToken, AgentSkillAssignment, AgentExecution, AgentSchedule
- [x] Service: `app/Services/Agent/SkillRegistry.php` — scan+parse+cache `storage/app/skills/*.md`
- [x] Service: `app/Services/Agent/AgentExecutionService.php` — validate + dispatch `AgentSkillJob`
- [x] Service: `app/Services/Agent/LocalModelService.php` — Ollama `/api/generate` + `/api/chat`
- [x] Service: `app/Services/Agent/OpenRouterService.php` — OpenRouter chat completions
- [x] Job: `app/Jobs/AgentSkillJob.php` — execute skill against provider, parse JSON output, audit log
- [x] Config: `config/ai.php` — `OLLAMA_BASE_URL`, `AI_OPENROUTER_API_KEY`, `AI_DEFAULT_PROVIDER/MODEL`
- [x] `.env.example` updated with AI config keys
- [x] Controllers: `app/Http/Controllers/Api/Agent/` — AgentController, AgentTokenController, AgentSkillController, AgentExecutionController, AgentScheduleController
- [x] Routes: `/api/v1/agents/` (admin only) — full CRUD + tokens + skills + executions + schedules
- [x] `RoleSeeder.php` updated — `agents` module added to all roles, `agents.execute` extra permission for admin
- [x] Write feature tests: `tests/Feature/Agent/AgentTest.php` — CRUD, skill assign, token create, execution log, queued run, admin-only guard
- [x] Write unit tests: `tests/Feature/Agent/SkillRegistryTest.php` — parse, find, byCategory, cache warm/clear, real skills parseable, invalid files skipped
- [x] Create `app/Console/Commands/SyncSkills.php` — `php artisan skills:sync` (lists all parsed skills)
- [ ] Add rate-limit enforcement for agent tokens (check `rate_limit_per_minute` in `AgentSkillJob`)
- [ ] Add multi-company agent access: `agent_company_access` pivot table (Phase 8 — multi-tenant)
- [ ] Add agent execution webhook: fire `WebhookService::dispatch('agent.execution.completed', ...)` after successful run
- [x] Complete OpenAPI spec for all `/api/v1/agents/` and `/api/v1/reports/` endpoints in `OpenApiSpec.php` — added Reports + Agents tags and 20+ endpoint annotations

### Skills System
- [x] Create `storage/app/skills/` directory structure (finance/, hr/, sales/, inventory/, expenses/)
- [x] Write 10 Tier 1 skill files with YAML frontmatter + Markdown instructions:
  - [x] `finance/extract_invoice.md` — OCR invoice data extraction
  - [x] `finance/categorize_transaction.md` — auto-categorise bank transactions
  - [x] `finance/match_purchase_order.md` — 3-way PO match
  - [x] `hr/draft_job_description.md` — JD generator
  - [x] `hr/generate_payslip_summary.md` — payslip narrative
  - [x] `hr/draft_performance_review.md` — performance review drafter
  - [x] `sales/score_crm_lead.md` — lead scoring 0–100
  - [x] `sales/draft_quote.md` — sales quote generator
  - [x] `inventory/reorder_alert.md` — reorder point detection + draft PO
  - [x] `expenses/categorize_expense.md` — expense categorisation
- [x] Write Tier 2 skills (10 files): finance.forecast_cashflow, finance.reconcile_accounts, finance.budget_variance_analysis, sales.draft_followup_email, sales.customer_churn_risk, procurement.evaluate_vendor, procurement.rfq_generator, projects.generate_status_report, hr.onboard_employee, marketing.generate_campaign_brief
- [x] Add skill file upload endpoint: `POST /api/v1/agents/skills/upload` (admin only) — validates frontmatter, stores .md, clears registry cache; exposed `SkillRegistry::parseContent()` for validation
- [ ] Add `SkillRegistry` fallback: if `storage/app/skills/` is empty, return empty array with helpful message
- [ ] Add skill validation on upload (must have slug, category, required_permissions, inputs, outputs)

### AI Agent Frontend
- [x] `resources/js/views/agents/AgentsView.vue` — list + filter + create agents
- [x] `resources/js/views/agents/AgentDetailView.vue` — tabbed detail: Skills, Executions, API Tokens, Schedules
- [x] `resources/js/views/agents/SkillCatalogView.vue` — searchable/filterable skill catalog
- [x] Router: `/agents`, `/agents/:id`, `/agents/skills/catalog` routes added
- [x] Sidebar: "AI Agents" nav group (admin-only) added to `MainLayout.vue`
- [x] Create `resources/js/stores/agents.ts` — Pinia store with typed interfaces, CRUD, skill catalog cache, execution polling with auto-cleanup
- [x] Add execution status polling in `AgentDetailView.vue` — auto-refresh every 5s when queued/running; animated pulse dot on tab; auto-clears on unmount
- [x] Onboarding redirect wired in `MainLayout.vue` — redirects to `/onboarding` when `authStore.onboardingPending` is true
- [ ] Add `SkillEnableModal` component — enable a skill on an agent with optional config overrides
- [ ] Add `ScheduleCreateModal` component in `AgentDetailView.vue` — cron expression builder UI

### Skills — Tier 3 & 4 (completed this session)
- [x] Write Tier 3 skills (10 files): inventory.demand_forecast, manufacturing.optimise_bom, hr.leave_coverage_check, quality.analyse_nonconformance, assets.maintenance_schedule, finance.budget_variance_analysis, sales.upsell_opportunities, contracts.review_terms, hr.interview_question_generator, finance.audit_trail_summary, marketing.lead_nurture_sequence
- [x] Write Tier 4 skills (10 files): finance.tax_return_prep, projects.resource_allocation, sales.win_loss_analysis, hr.org_chart_analysis, inventory.shrinkage_detection, manufacturing.production_schedule, quality.supplier_quality_report, assets.depreciation_schedule, finance.scenario_planning, procurement.price_trend_analysis
- [x] Write `tests/Feature/Finance/BudgetTest.php` — CRUD, validation, year filter, soft-delete, auth guard
- [x] Write `database/factories/Finance/BudgetFactory.php`

### Future AI Capabilities (Phase 8)
- [ ] **Tier 5 skills** — write remaining 10 skill files (external API dependent): documents.extract_contract_terms, hr.benchmark_salaries, sales.competitive_analysis, manufacturing.yield_analysis, hr.training_needs_analysis, projects.retrospective_summary, sales.territory_planning, finance.investor_report, etc.
- [ ] **Skill marketplace** — community-contributed skills, importable from GitHub repo
- [ ] **Agent teams** — orchestrate multiple agents on a single complex task (chain of skills)
- [ ] **Multi-company agent sharing** — `agent_company_access` pivot, cross-tenant token scoping
- [ ] **Model cost tracking** — dashboard showing token usage + estimated cost per agent/skill/period
- [ ] **Skill A/B testing** — compare two skill versions on the same input to evaluate quality
- [ ] **Agent audit export** — export `agent_executions` as CSV for compliance

---

## Notes

- **Priority**: Get Auth + one complete module (Finance) working end-to-end in Laravel first
- **Testing**: Every new module must have minimum 70% test coverage
- **Security**: All endpoints must have proper authorization checks — RBAC now enforced on all routes
- **Performance**: Every new feature must consider caching strategy
- **Cleanup**: Remove `api/`/`modules/`/`core/` equivalents once replaced by Laravel
- **API versioning**: All new endpoints under `/api/v1/`. Legacy `/api/auth/*` routes maintained for backward compat.
- **AI agents**: Completely optional — no agent code affects ERP operation. Enable per-company via admin panel.
