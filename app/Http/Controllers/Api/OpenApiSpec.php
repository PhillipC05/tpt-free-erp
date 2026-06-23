<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'TPT Free ERP API',
    description: 'Open-source Enterprise Resource Planning REST API. Finance, Inventory, HR, Sales, Procurement, Manufacturing, Projects, Quality, Assets, Field Service, and LMS.',
    contact: new OA\Contact(email: 'support@tptsolutions.co.nz'),
    license: new OA\License(name: 'Apache-2.0', url: 'https://www.apache.org/licenses/LICENSE-2.0')
)]
#[OA\Server(url: '/api', description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Laravel Sanctum token. Obtain via POST /api/auth/login.'
)]
#[OA\Tag(name: 'Auth',          description: 'Authentication — login, register, logout')]
#[OA\Tag(name: 'Finance',       description: 'Chart of accounts, transactions, journal entries, budgets')]
#[OA\Tag(name: 'Inventory',     description: 'Products, categories, warehouses, stock movements')]
#[OA\Tag(name: 'HR',            description: 'Employees, departments, leave, payroll, attendance')]
#[OA\Tag(name: 'Sales',         description: 'Customers, orders, invoices, CRM pipeline')]
#[OA\Tag(name: 'Procurement',   description: 'Vendors, purchase orders')]
#[OA\Tag(name: 'Manufacturing', description: 'Bills of materials, work orders')]
#[OA\Tag(name: 'Projects',      description: 'Projects, tasks, time entries')]
#[OA\Tag(name: 'Quality',       description: 'Quality checks, non-conformances')]
#[OA\Tag(name: 'Assets',        description: 'Asset lifecycle, depreciation, maintenance')]
#[OA\Tag(name: 'FieldService',  description: 'Service tickets')]
#[OA\Tag(name: 'LMS',           description: 'Courses and enrollments')]
#[OA\Tag(name: 'Reports',       description: 'Async report generation, scheduling, and download')]
#[OA\Tag(name: 'Agents',        description: 'AI agent profiles, skill assignments, executions, and schedules')]
class OpenApiSpec
{
    // ── Reusable response schemas ───────────────────────────────────────────

    #[OA\Schema(schema: 'SuccessResponse', properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'data',    type: 'object'),
    ])]
    public function successResponse(): void {}

    #[OA\Schema(schema: 'ErrorResponse', properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'errors',  type: 'object'),
    ])]
    public function errorResponse(): void {}

    #[OA\Schema(schema: 'PaginationMeta', properties: [
        new OA\Property(property: 'current_page', type: 'integer'),
        new OA\Property(property: 'last_page',    type: 'integer'),
        new OA\Property(property: 'per_page',     type: 'integer'),
        new OA\Property(property: 'total',        type: 'integer'),
    ])]
    public function paginationMeta(): void {}

    // ── AUTH ───────────────────────────────────────────────────────────────

    #[OA\Post(path: '/auth/login', tags: ['Auth'], summary: 'Login and obtain API token',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email',    type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: 'Token returned'),
            new OA\Response(response: 422, description: 'Invalid credentials'),
        ]
    )]
    public function authLogin(): void {}

    #[OA\Post(path: '/auth/register', tags: ['Auth'], summary: 'Register a new user',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name',                  type: 'string'),
                new OA\Property(property: 'email',                 type: 'string', format: 'email'),
                new OA\Property(property: 'password',              type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'User created')]
    )]
    public function authRegister(): void {}

    #[OA\Post(path: '/auth/logout', tags: ['Auth'], summary: 'Revoke current token',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Logged out')]
    )]
    public function authLogout(): void {}

    #[OA\Get(path: '/auth/me', tags: ['Auth'], summary: 'Return authenticated user profile',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'User profile')]
    )]
    public function authMe(): void {}

    // ── FINANCE — Accounts ─────────────────────────────────────────────────

    #[OA\Get(path: '/finance/accounts', tags: ['Finance'], summary: 'List chart of accounts',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'type',     in: 'query', schema: new OA\Schema(type: 'string', enum: ['asset', 'liability', 'equity', 'revenue', 'expense'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated account list')]
    )]
    public function financeAccountIndex(): void {}

    #[OA\Post(path: '/finance/accounts', tags: ['Finance'], summary: 'Create account',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['code', 'name', 'type'],
            properties: [
                new OA\Property(property: 'code',     type: 'string', example: '1000'),
                new OA\Property(property: 'name',     type: 'string', example: 'Cash'),
                new OA\Property(property: 'type',     type: 'string', enum: ['asset', 'liability', 'equity', 'revenue', 'expense']),
                new OA\Property(property: 'currency', type: 'string', example: 'USD'),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Account created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function financeAccountStore(): void {}

    #[OA\Get(path: '/finance/accounts/{id}', tags: ['Finance'], summary: 'Get account by ID',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Account'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function financeAccountShow(): void {}

    #[OA\Put(path: '/finance/accounts/{id}', tags: ['Finance'], summary: 'Update account',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function financeAccountUpdate(): void {}

    #[OA\Delete(path: '/finance/accounts/{id}', tags: ['Finance'], summary: 'Delete account',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Deleted')]
    )]
    public function financeAccountDestroy(): void {}

    #[OA\Get(path: '/finance/accounts/{id}/balance', tags: ['Finance'], summary: 'Get account balance with debit/credit totals',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Balance details')]
    )]
    public function financeAccountBalance(): void {}

    // ── FINANCE — Transactions ─────────────────────────────────────────────

    #[OA\Get(path: '/finance/transactions', tags: ['Finance'], summary: 'List transactions',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'account_id', in: 'query', schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')]
    )]
    public function financeTransactionIndex(): void {}

    #[OA\Post(path: '/finance/transactions', tags: ['Finance'], summary: 'Create transaction',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['account_id', 'amount', 'type', 'transaction_date'],
            properties: [
                new OA\Property(property: 'account_id',       type: 'integer'),
                new OA\Property(property: 'amount',           type: 'number'),
                new OA\Property(property: 'type',             type: 'string', enum: ['debit', 'credit']),
                new OA\Property(property: 'transaction_date', type: 'string', format: 'date'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function financeTransactionStore(): void {}

    #[OA\Get(path: '/finance/transactions/{id}', tags: ['Finance'], summary: 'Get transaction',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Transaction')]
    )]
    public function financeTransactionShow(): void {}

    // ── FINANCE — Journal Entries ──────────────────────────────────────────

    #[OA\Get(path: '/finance/journal-entries', tags: ['Finance'], summary: 'List journal entries',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Paginated list')]
    )]
    public function financeJournalIndex(): void {}

    #[OA\Post(path: '/finance/journal-entries', tags: ['Finance'], summary: 'Create journal entry',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['entry_date', 'description'],
            properties: [
                new OA\Property(property: 'entry_date',   type: 'string', format: 'date'),
                new OA\Property(property: 'description',  type: 'string'),
                new OA\Property(property: 'reference',    type: 'string'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function financeJournalStore(): void {}

    // ── INVENTORY — Products ───────────────────────────────────────────────

    #[OA\Get(path: '/inventory/products', tags: ['Inventory'], summary: 'List products',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search',    in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page',  in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated product list')]
    )]
    public function inventoryProductIndex(): void {}

    #[OA\Post(path: '/inventory/products', tags: ['Inventory'], summary: 'Create product',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['sku', 'name', 'unit'],
            properties: [
                new OA\Property(property: 'sku',              type: 'string'),
                new OA\Property(property: 'name',             type: 'string'),
                new OA\Property(property: 'unit',             type: 'string'),
                new OA\Property(property: 'unit_price',       type: 'number'),
                new OA\Property(property: 'valuation_method', type: 'string', enum: ['fifo', 'lifo', 'average']),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Created')]
    )]
    public function inventoryProductStore(): void {}

    #[OA\Get(path: '/inventory/products/{id}', tags: ['Inventory'], summary: 'Get product',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Product'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function inventoryProductShow(): void {}

    #[OA\Put(path: '/inventory/products/{id}', tags: ['Inventory'], summary: 'Update product',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function inventoryProductUpdate(): void {}

    #[OA\Delete(path: '/inventory/products/{id}', tags: ['Inventory'], summary: 'Delete product',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Deleted')]
    )]
    public function inventoryProductDestroy(): void {}

    // ── INVENTORY — Warehouses & Stock ─────────────────────────────────────

    #[OA\Get(path: '/inventory/warehouses', tags: ['Inventory'], summary: 'List warehouses',
        security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'List')])]
    public function inventoryWarehouseIndex(): void {}

    #[OA\Post(path: '/inventory/warehouses', tags: ['Inventory'], summary: 'Create warehouse',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function inventoryWarehouseStore(): void {}

    #[OA\Get(path: '/inventory/warehouses/{id}', tags: ['Inventory'], summary: 'Get warehouse',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Warehouse')])]
    public function inventoryWarehouseShow(): void {}

    #[OA\Post(path: '/inventory/stock-movements', tags: ['Inventory'], summary: 'Record stock movement',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['product_id', 'warehouse_id', 'type', 'quantity'],
            properties: [
                new OA\Property(property: 'product_id',   type: 'integer'),
                new OA\Property(property: 'warehouse_id', type: 'integer'),
                new OA\Property(property: 'type',         type: 'string', enum: ['in', 'out', 'transfer', 'adjustment']),
                new OA\Property(property: 'quantity',     type: 'number'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Movement recorded')]
    )]
    public function inventoryStockMovementStore(): void {}

    // ── HR — Employees ─────────────────────────────────────────────────────

    #[OA\Get(path: '/hr/employees', tags: ['HR'], summary: 'List employees',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search',          in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status',          in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'on_leave', 'terminated'])),
            new OA\Parameter(name: 'employment_type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['full_time', 'part_time', 'contract', 'intern'])),
            new OA\Parameter(name: 'per_page',        in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated employee list')]
    )]
    public function hrEmployeeIndex(): void {}

    #[OA\Post(path: '/hr/employees', tags: ['HR'], summary: 'Create employee record',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['employee_code', 'first_name', 'last_name', 'email', 'hire_date', 'employment_type'],
            properties: [
                new OA\Property(property: 'employee_code',   type: 'string'),
                new OA\Property(property: 'first_name',      type: 'string'),
                new OA\Property(property: 'last_name',       type: 'string'),
                new OA\Property(property: 'email',           type: 'string', format: 'email'),
                new OA\Property(property: 'hire_date',       type: 'string', format: 'date'),
                new OA\Property(property: 'employment_type', type: 'string', enum: ['full_time', 'part_time', 'contract', 'intern']),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Employee created')]
    )]
    public function hrEmployeeStore(): void {}

    #[OA\Get(path: '/hr/employees/{id}', tags: ['HR'], summary: 'Get employee',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Employee')])]
    public function hrEmployeeShow(): void {}

    #[OA\Put(path: '/hr/employees/{id}', tags: ['HR'], summary: 'Update employee',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function hrEmployeeUpdate(): void {}

    #[OA\Delete(path: '/hr/employees/{id}', tags: ['HR'], summary: 'Terminate employee',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Terminated')])]
    public function hrEmployeeDestroy(): void {}

    #[OA\Get(path: '/hr/departments', tags: ['HR'], summary: 'List departments', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'List')])]
    public function hrDepartmentIndex(): void {}

    #[OA\Post(path: '/hr/departments', tags: ['HR'], summary: 'Create department',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function hrDepartmentStore(): void {}

    #[OA\Get(path: '/hr/leave', tags: ['HR'], summary: 'List leave requests', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function hrLeaveIndex(): void {}

    #[OA\Post(path: '/hr/leave', tags: ['HR'], summary: 'Submit leave request',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['employee_id', 'leave_type', 'start_date', 'end_date'],
            properties: [
                new OA\Property(property: 'employee_id', type: 'integer'),
                new OA\Property(property: 'leave_type',  type: 'string', enum: ['annual', 'sick', 'personal', 'maternity', 'paternity', 'other']),
                new OA\Property(property: 'start_date',  type: 'string', format: 'date'),
                new OA\Property(property: 'end_date',    type: 'string', format: 'date'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Leave request submitted')]
    )]
    public function hrLeaveStore(): void {}

    // ── SALES ──────────────────────────────────────────────────────────────

    #[OA\Get(path: '/sales/customers', tags: ['Sales'], summary: 'List customers',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive', 'blocked']))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function salesCustomerIndex(): void {}

    #[OA\Post(path: '/sales/customers', tags: ['Sales'], summary: 'Create customer',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name', 'email'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'email', type: 'string', format: 'email')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function salesCustomerStore(): void {}

    #[OA\Get(path: '/sales/customers/{id}', tags: ['Sales'], summary: 'Get customer', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Customer')])]
    public function salesCustomerShow(): void {}

    #[OA\Get(path: '/sales/orders', tags: ['Sales'], summary: 'List sales orders',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'customer_id', in: 'query', schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function salesOrderIndex(): void {}

    #[OA\Post(path: '/sales/orders', tags: ['Sales'], summary: 'Create sales order',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['order_number', 'customer_id', 'order_date', 'subtotal', 'total_amount'], properties: [new OA\Property(property: 'order_number', type: 'string'), new OA\Property(property: 'customer_id', type: 'integer'), new OA\Property(property: 'order_date', type: 'string', format: 'date'), new OA\Property(property: 'total_amount', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function salesOrderStore(): void {}

    #[OA\Get(path: '/sales/orders/{id}', tags: ['Sales'], summary: 'Get order', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Order')])]
    public function salesOrderShow(): void {}

    #[OA\Put(path: '/sales/orders/{id}/status', tags: ['Sales'], summary: 'Update order status',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [new OA\Property(property: 'status', type: 'string', enum: ['draft', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])])),
        responses: [new OA\Response(response: 200, description: 'Status updated')])]
    public function salesOrderUpdateStatus(): void {}

    #[OA\Get(path: '/sales/invoices', tags: ['Sales'], summary: 'List invoices', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function salesInvoiceIndex(): void {}

    #[OA\Post(path: '/sales/invoices', tags: ['Sales'], summary: 'Create invoice',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['invoice_number', 'order_id', 'customer_id', 'invoice_date', 'due_date', 'total_amount'], properties: [new OA\Property(property: 'invoice_number', type: 'string'), new OA\Property(property: 'order_id', type: 'integer'), new OA\Property(property: 'customer_id', type: 'integer'), new OA\Property(property: 'invoice_date', type: 'string', format: 'date'), new OA\Property(property: 'due_date', type: 'string', format: 'date'), new OA\Property(property: 'total_amount', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function salesInvoiceStore(): void {}

    #[OA\Post(path: '/sales/invoices/{id}/send', tags: ['Sales'], summary: 'Send invoice to customer',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Sent')])]
    public function salesInvoiceSend(): void {}

    #[OA\Post(path: '/sales/invoices/{id}/record-payment', tags: ['Sales'], summary: 'Record payment against invoice',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['amount'], properties: [new OA\Property(property: 'amount', type: 'number')])),
        responses: [new OA\Response(response: 200, description: 'Payment recorded')])]
    public function salesInvoiceRecordPayment(): void {}

    // ── PROCUREMENT ────────────────────────────────────────────────────────

    #[OA\Get(path: '/procurement/vendors', tags: ['Procurement'], summary: 'List vendors', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function procurementVendorIndex(): void {}

    #[OA\Post(path: '/procurement/vendors', tags: ['Procurement'], summary: 'Create vendor',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name', 'email'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'email', type: 'string', format: 'email')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function procurementVendorStore(): void {}

    #[OA\Get(path: '/procurement/vendors/{id}', tags: ['Procurement'], summary: 'Get vendor', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Vendor')])]
    public function procurementVendorShow(): void {}

    #[OA\Get(path: '/procurement/purchase-orders', tags: ['Procurement'], summary: 'List purchase orders', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function procurementPoIndex(): void {}

    #[OA\Post(path: '/procurement/purchase-orders', tags: ['Procurement'], summary: 'Create purchase order',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['po_number', 'vendor_id', 'order_date', 'subtotal', 'total_amount'], properties: [new OA\Property(property: 'po_number', type: 'string'), new OA\Property(property: 'vendor_id', type: 'integer'), new OA\Property(property: 'order_date', type: 'string', format: 'date'), new OA\Property(property: 'total_amount', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function procurementPoStore(): void {}

    #[OA\Put(path: '/procurement/purchase-orders/{id}/status', tags: ['Procurement'], summary: 'Update PO status',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [new OA\Property(property: 'status', type: 'string', enum: ['draft', 'sent', 'confirmed', 'received', 'cancelled'])])),
        responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function procurementPoUpdateStatus(): void {}

    #[OA\Post(path: '/procurement/purchase-orders/{id}/approve', tags: ['Procurement'], summary: 'Approve purchase order',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Approved')])]
    public function procurementPoApprove(): void {}

    #[OA\Post(path: '/procurement/purchase-orders/{id}/receive', tags: ['Procurement'], summary: 'Mark purchase order received',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Received')])]
    public function procurementPoReceive(): void {}

    // ── MANUFACTURING ──────────────────────────────────────────────────────

    #[OA\Get(path: '/manufacturing/boms', tags: ['Manufacturing'], summary: 'List bills of materials', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function manufacturingBomIndex(): void {}

    #[OA\Post(path: '/manufacturing/boms', tags: ['Manufacturing'], summary: 'Create BOM',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name', 'product_id', 'quantity'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'product_id', type: 'integer'), new OA\Property(property: 'quantity', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function manufacturingBomStore(): void {}

    #[OA\Get(path: '/manufacturing/boms/{id}/components', tags: ['Manufacturing'], summary: 'List BOM components',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Component list')])]
    public function manufacturingBomComponents(): void {}

    #[OA\Get(path: '/manufacturing/work-orders', tags: ['Manufacturing'], summary: 'List work orders', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function manufacturingWoIndex(): void {}

    #[OA\Post(path: '/manufacturing/work-orders', tags: ['Manufacturing'], summary: 'Create work order',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['wo_number', 'product_id', 'planned_quantity', 'start_date'], properties: [new OA\Property(property: 'wo_number', type: 'string'), new OA\Property(property: 'product_id', type: 'integer'), new OA\Property(property: 'planned_quantity', type: 'number'), new OA\Property(property: 'start_date', type: 'string', format: 'date')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function manufacturingWoStore(): void {}

    #[OA\Post(path: '/manufacturing/work-orders/{id}/start', tags: ['Manufacturing'], summary: 'Start work order',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Started')])]
    public function manufacturingWoStart(): void {}

    #[OA\Post(path: '/manufacturing/work-orders/{id}/complete', tags: ['Manufacturing'], summary: 'Complete work order',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Completed')])]
    public function manufacturingWoComplete(): void {}

    // ── PROJECTS ───────────────────────────────────────────────────────────

    #[OA\Get(path: '/projects/projects', tags: ['Projects'], summary: 'List projects', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function projectIndex(): void {}

    #[OA\Post(path: '/projects/projects', tags: ['Projects'], summary: 'Create project',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'name', 'start_date'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'start_date', type: 'string', format: 'date'), new OA\Property(property: 'status', type: 'string', enum: ['planning', 'active', 'on_hold', 'completed', 'cancelled'])])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function projectStore(): void {}

    #[OA\Get(path: '/projects/projects/{id}', tags: ['Projects'], summary: 'Get project', security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Project')])]
    public function projectShow(): void {}

    #[OA\Get(path: '/projects/projects/{id}/tasks', tags: ['Projects'], summary: 'List tasks for a project',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Task list')])]
    public function projectTasksByProject(): void {}

    #[OA\Get(path: '/projects/tasks', tags: ['Projects'], summary: 'List tasks', security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'integer')), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['todo', 'in_progress', 'review', 'done', 'cancelled']))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function taskIndex(): void {}

    #[OA\Post(path: '/projects/tasks', tags: ['Projects'], summary: 'Create task',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'project_id', 'title'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'project_id', type: 'integer'), new OA\Property(property: 'title', type: 'string'), new OA\Property(property: 'status', type: 'string', enum: ['todo', 'in_progress', 'review', 'done', 'cancelled']), new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'critical'])])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function taskStore(): void {}

    #[OA\Put(path: '/projects/tasks/{id}/status', tags: ['Projects'], summary: 'Update task status',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [new OA\Property(property: 'status', type: 'string', enum: ['todo', 'in_progress', 'review', 'done', 'cancelled'])])),
        responses: [new OA\Response(response: 200, description: 'Status updated')])]
    public function taskUpdateStatus(): void {}

    #[OA\Get(path: '/projects/time-entries', tags: ['Projects'], summary: 'List time entries', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'List')])]
    public function timeEntryIndex(): void {}

    #[OA\Post(path: '/projects/time-entries', tags: ['Projects'], summary: 'Log time entry',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['task_id', 'employee_id', 'hours', 'date'], properties: [new OA\Property(property: 'task_id', type: 'integer'), new OA\Property(property: 'employee_id', type: 'integer'), new OA\Property(property: 'hours', type: 'number'), new OA\Property(property: 'date', type: 'string', format: 'date')])),
        responses: [new OA\Response(response: 201, description: 'Logged')])]
    public function timeEntryStore(): void {}

    // ── QUALITY ────────────────────────────────────────────────────────────

    #[OA\Get(path: '/quality/checks', tags: ['Quality'], summary: 'List quality checks', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function qualityCheckIndex(): void {}

    #[OA\Post(path: '/quality/checks', tags: ['Quality'], summary: 'Create quality check',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['check_code', 'product_id', 'type'], properties: [new OA\Property(property: 'check_code', type: 'string'), new OA\Property(property: 'product_id', type: 'integer'), new OA\Property(property: 'type', type: 'string', enum: ['incoming', 'in_process', 'final', 'audit'])])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function qualityCheckStore(): void {}

    #[OA\Post(path: '/quality/checks/{id}/record-result', tags: ['Quality'], summary: 'Record inspection result',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['result'], properties: [new OA\Property(property: 'result', type: 'string', enum: ['pass', 'fail', 'conditional']), new OA\Property(property: 'notes', type: 'string')])),
        responses: [new OA\Response(response: 200, description: 'Result recorded')])]
    public function qualityCheckRecordResult(): void {}

    #[OA\Get(path: '/quality/non-conformances', tags: ['Quality'], summary: 'List non-conformances', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function qualityNcIndex(): void {}

    #[OA\Post(path: '/quality/non-conformances', tags: ['Quality'], summary: 'Create non-conformance',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['nc_number', 'description', 'severity'], properties: [new OA\Property(property: 'nc_number', type: 'string'), new OA\Property(property: 'description', type: 'string'), new OA\Property(property: 'severity', type: 'string', enum: ['minor', 'major', 'critical'])])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function qualityNcStore(): void {}

    #[OA\Put(path: '/quality/non-conformances/{id}/status', tags: ['Quality'], summary: 'Update NC status',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [new OA\Property(property: 'status', type: 'string', enum: ['open', 'investigating', 'resolved', 'closed'])])),
        responses: [new OA\Response(response: 200, description: 'Status updated')])]
    public function qualityNcUpdateStatus(): void {}

    // ── ASSETS ─────────────────────────────────────────────────────────────

    #[OA\Get(path: '/assets/assets', tags: ['Assets'], summary: 'List assets',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['equipment', 'vehicle', 'building', 'furniture', 'it', 'other'])), new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'maintenance', 'retired', 'disposed']))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function assetIndex(): void {}

    #[OA\Post(path: '/assets/assets', tags: ['Assets'], summary: 'Create asset',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['asset_code', 'name', 'type', 'purchase_date', 'purchase_cost'], properties: [new OA\Property(property: 'asset_code', type: 'string'), new OA\Property(property: 'name', type: 'string'), new OA\Property(property: 'type', type: 'string', enum: ['equipment', 'vehicle', 'building', 'furniture', 'it', 'other']), new OA\Property(property: 'purchase_date', type: 'string', format: 'date'), new OA\Property(property: 'purchase_cost', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function assetStore(): void {}

    #[OA\Get(path: '/assets/assets/{id}', tags: ['Assets'], summary: 'Get asset with maintenance history',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Asset')])]
    public function assetShow(): void {}

    #[OA\Post(path: '/assets/assets/{id}/depreciate', tags: ['Assets'], summary: 'Calculate and apply depreciation',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Depreciation applied'), new OA\Response(response: 422, description: 'Missing depreciation config')])]
    public function assetDepreciate(): void {}

    #[OA\Get(path: '/assets/assets/{id}/maintenance-history', tags: ['Assets'], summary: 'Get asset maintenance history',
        security: [['bearerAuth' => []]], parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Maintenance records')])]
    public function assetMaintenanceHistory(): void {}

    #[OA\Get(path: '/assets/maintenance', tags: ['Assets'], summary: 'List maintenance records', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function maintenanceIndex(): void {}

    #[OA\Post(path: '/assets/maintenance', tags: ['Assets'], summary: 'Schedule maintenance',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['asset_id', 'title', 'description', 'type', 'scheduled_date'], properties: [new OA\Property(property: 'asset_id', type: 'integer'), new OA\Property(property: 'title', type: 'string'), new OA\Property(property: 'description', type: 'string'), new OA\Property(property: 'type', type: 'string', enum: ['preventive', 'corrective', 'emergency']), new OA\Property(property: 'scheduled_date', type: 'string', format: 'date')])),
        responses: [new OA\Response(response: 201, description: 'Scheduled')])]
    public function maintenanceStore(): void {}

    // ── FIELD SERVICE ──────────────────────────────────────────────────────

    #[OA\Get(path: '/field-service/tickets', tags: ['FieldService'], summary: 'List service tickets',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['open', 'assigned', 'in_progress', 'resolved', 'closed'])), new OA\Parameter(name: 'priority', in: 'query', schema: new OA\Schema(type: 'string', enum: ['low', 'medium', 'high', 'urgent']))],
        responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function ticketIndex(): void {}

    #[OA\Post(path: '/field-service/tickets', tags: ['FieldService'], summary: 'Create service ticket',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['ticket_number', 'customer_id', 'title', 'description', 'priority'], properties: [new OA\Property(property: 'ticket_number', type: 'string'), new OA\Property(property: 'customer_id', type: 'integer'), new OA\Property(property: 'title', type: 'string'), new OA\Property(property: 'description', type: 'string'), new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'])])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function ticketStore(): void {}

    #[OA\Put(path: '/field-service/tickets/{id}/status', tags: ['FieldService'], summary: 'Update ticket status',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['status'], properties: [new OA\Property(property: 'status', type: 'string', enum: ['open', 'assigned', 'in_progress', 'resolved', 'closed', 'cancelled'])])),
        responses: [new OA\Response(response: 200, description: 'Updated')])]
    public function ticketUpdateStatus(): void {}

    // ── LMS ────────────────────────────────────────────────────────────────

    #[OA\Get(path: '/lms/courses', tags: ['LMS'], summary: 'List courses', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function lmsCourseIndex(): void {}

    #[OA\Post(path: '/lms/courses', tags: ['LMS'], summary: 'Create course',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['code', 'title', 'type'], properties: [new OA\Property(property: 'code', type: 'string'), new OA\Property(property: 'title', type: 'string'), new OA\Property(property: 'type', type: 'string', enum: ['online', 'classroom', 'blended']), new OA\Property(property: 'duration_hours', type: 'number')])),
        responses: [new OA\Response(response: 201, description: 'Created')])]
    public function lmsCourseStore(): void {}

    #[OA\Post(path: '/lms/courses/{id}/enroll', tags: ['LMS'], summary: 'Enroll employee in course',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['employee_id'], properties: [new OA\Property(property: 'employee_id', type: 'integer')])),
        responses: [new OA\Response(response: 201, description: 'Enrolled')])]
    public function lmsCourseEnroll(): void {}

    #[OA\Get(path: '/lms/enrollments', tags: ['LMS'], summary: 'List enrollments', security: [['bearerAuth' => []]], responses: [new OA\Response(response: 200, description: 'Paginated list')])]
    public function lmsEnrollmentIndex(): void {}

    #[OA\Put(path: '/lms/enrollments/{id}/complete', tags: ['LMS'], summary: 'Mark enrollment complete',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(properties: [new OA\Property(property: 'score', type: 'number', example: 88.5)])),
        responses: [new OA\Response(response: 200, description: 'Completed')])]
    public function lmsEnrollmentComplete(): void {}

    // ── REPORTS ────────────────────────────────────────────────────────────

    #[OA\Post(path: '/v1/reports/generate', tags: ['Reports'], summary: 'Queue a report for async generation',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['report_type', 'format'],
            properties: [
                new OA\Property(property: 'report_type', type: 'string', enum: ['trial_balance', 'income_statement', 'balance_sheet', 'cash_flow', 'hr_attendance', 'hr_payroll', 'sales_summary', 'procurement']),
                new OA\Property(property: 'format',      type: 'string', enum: ['json', 'csv', 'pdf']),
                new OA\Property(property: 'date_from',   type: 'string', format: 'date'),
                new OA\Property(property: 'date_to',     type: 'string', format: 'date'),
            ]
        )),
        responses: [
            new OA\Response(response: 202, description: 'Report queued — returns report_id'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function reportGenerate(): void {}

    #[OA\Get(path: '/v1/reports/{id}', tags: ['Reports'], summary: 'Poll report status and retrieve result',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Report record with status (queued|running|completed|failed)'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function reportShow(): void {}

    #[OA\Get(path: '/v1/reports/{id}/download', tags: ['Reports'], summary: 'Download completed report as JSON, CSV, or PDF',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'File download'),
            new OA\Response(response: 422, description: 'Report not yet completed'),
        ]
    )]
    public function reportDownload(): void {}

    #[OA\Get(path: '/v1/reports/scheduled', tags: ['Reports'], summary: 'List scheduled reports for current user',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'List of scheduled reports')]
    )]
    public function reportScheduledIndex(): void {}

    #[OA\Post(path: '/v1/reports/scheduled', tags: ['Reports'], summary: 'Create a scheduled report',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'report_type', 'format', 'frequency'],
            properties: [
                new OA\Property(property: 'name',        type: 'string'),
                new OA\Property(property: 'report_type', type: 'string'),
                new OA\Property(property: 'format',      type: 'string', enum: ['json', 'csv', 'pdf']),
                new OA\Property(property: 'frequency',   type: 'string', enum: ['daily', 'weekly', 'monthly']),
                new OA\Property(property: 'delivery_email', type: 'string', format: 'email'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Scheduled report created')]
    )]
    public function reportScheduledStore(): void {}

    #[OA\Delete(path: '/v1/reports/scheduled/{id}', tags: ['Reports'], summary: 'Delete a scheduled report',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Deleted')]
    )]
    public function reportScheduledDestroy(): void {}

    // ── AGENTS ─────────────────────────────────────────────────────────────

    #[OA\Get(path: '/v1/agents', tags: ['Agents'], summary: 'List agent profiles',
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: 'Paginated list of agents')]
    )]
    public function agentIndex(): void {}

    #[OA\Post(path: '/v1/agents', tags: ['Agents'], summary: 'Create an agent profile',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'agent_type'],
            properties: [
                new OA\Property(property: 'name',            type: 'string', example: 'Finance Bot'),
                new OA\Property(property: 'description',     type: 'string'),
                new OA\Property(property: 'agent_type',      type: 'string', enum: ['local', 'openrouter', 'api', 'human_subcontractor']),
                new OA\Property(property: 'provider_config', type: 'object', description: 'Model/API configuration'),
                new OA\Property(property: 'is_active',       type: 'boolean', default: true),
            ]
        )),
        responses: [
            new OA\Response(response: 201, description: 'Agent created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function agentStore(): void {}

    #[OA\Get(path: '/v1/agents/{id}', tags: ['Agents'], summary: 'Get agent with assigned skills',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Agent profile with skill assignments'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function agentShow(): void {}

    #[OA\Put(path: '/v1/agents/{id}', tags: ['Agents'], summary: 'Update agent profile',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')),
        responses: [new OA\Response(response: 200, description: 'Updated')]
    )]
    public function agentUpdate(): void {}

    #[OA\Delete(path: '/v1/agents/{id}', tags: ['Agents'], summary: 'Soft-delete agent profile',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Deleted')]
    )]
    public function agentDestroy(): void {}

    #[OA\Post(path: '/v1/agents/{id}/tokens', tags: ['Agents'], summary: 'Create a scoped API token for an agent',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name',                 type: 'string'),
                new OA\Property(property: 'abilities',            type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'allowed_skill_slugs',  type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'rate_limit_per_minute', type: 'integer', default: 10),
                new OA\Property(property: 'expires_at',           type: 'string', format: 'date-time'),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Token created — plain_token shown once')]
    )]
    public function agentTokenStore(): void {}

    #[OA\Delete(path: '/v1/agents/{id}/tokens/{tokenId}', tags: ['Agents'], summary: 'Revoke an agent token',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',      in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tokenId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Token revoked')]
    )]
    public function agentTokenDestroy(): void {}

    #[OA\Get(path: '/v1/agents/skills/available', tags: ['Agents'], summary: 'Browse full skill catalog',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'All available skills from the registry')]
    )]
    public function agentSkillCatalog(): void {}

    #[OA\Post(path: '/v1/agents/skills/upload', tags: ['Agents'], summary: 'Upload a new skill .md file (admin)',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(
            required: ['skill_file'],
            properties: [new OA\Property(property: 'skill_file', type: 'string', format: 'binary')]
        ))),
        responses: [
            new OA\Response(response: 200, description: 'Skill uploaded and registry cache cleared'),
            new OA\Response(response: 422, description: 'Validation error — missing frontmatter or invalid slug'),
        ]
    )]
    public function agentSkillUpload(): void {}

    #[OA\Get(path: '/v1/agents/{id}/skills', tags: ['Agents'], summary: 'List skills assigned to an agent with registry metadata',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Agent skill assignments with full metadata')]
    )]
    public function agentSkillIndex(): void {}

    #[OA\Put(path: '/v1/agents/{id}/skills/{slug}', tags: ['Agents'], summary: 'Enable or disable a skill for an agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',   in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'is_enabled',       type: 'boolean'),
                new OA\Property(property: 'config_overrides', type: 'object'),
            ]
        )),
        responses: [new OA\Response(response: 200, description: 'Assignment updated')]
    )]
    public function agentSkillUpdate(): void {}

    #[OA\Post(path: '/v1/agents/{id}/skills/{slug}/run', tags: ['Agents'], summary: 'Manually trigger a skill execution',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',   in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['input'],
            properties: [new OA\Property(property: 'input', type: 'object', description: 'Skill input matching the skill\'s inputs schema')]
        )),
        responses: [
            new OA\Response(response: 202, description: 'Execution queued — returns execution_id'),
            new OA\Response(response: 404, description: 'Agent or skill not found'),
            new OA\Response(response: 422, description: 'Skill disabled for this agent'),
        ]
    )]
    public function agentSkillRun(): void {}

    #[OA\Get(path: '/v1/agents/{id}/executions', tags: ['Agents'], summary: 'Paginated execution audit log for an agent',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',     in: 'path',  required: true,  schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['queued', 'running', 'completed', 'failed'])),
            new OA\Parameter(name: 'skill',  in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated execution log')]
    )]
    public function agentExecutionIndex(): void {}

    #[OA\Get(path: '/v1/agents/{id}/executions/{execId}', tags: ['Agents'], summary: 'Get single execution detail',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',     in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'execId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Execution detail'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function agentExecutionShow(): void {}

    #[OA\Get(path: '/v1/agents/{id}/schedules', tags: ['Agents'], summary: 'List schedules for an agent',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Agent schedules')]
    )]
    public function agentScheduleIndex(): void {}

    #[OA\Post(path: '/v1/agents/{id}/schedules', tags: ['Agents'], summary: 'Create a cron schedule for an agent skill',
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['skill_slug'],
            properties: [
                new OA\Property(property: 'skill_slug',       type: 'string', example: 'finance.extract_invoice'),
                new OA\Property(property: 'cron_expression',  type: 'string', example: '0 9 * * 1'),
                new OA\Property(property: 'input_template',   type: 'object'),
                new OA\Property(property: 'is_active',        type: 'boolean', default: true),
            ]
        )),
        responses: [new OA\Response(response: 201, description: 'Schedule created')]
    )]
    public function agentScheduleStore(): void {}

    #[OA\Delete(path: '/v1/agents/{id}/schedules/{schedId}', tags: ['Agents'], summary: 'Delete an agent schedule',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id',      in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'schedId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Deleted')]
    )]
    public function agentScheduleDestroy(): void {}
}
