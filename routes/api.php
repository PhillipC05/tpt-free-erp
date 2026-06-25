<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Finance\AccountController;
use App\Http\Controllers\Api\Finance\TransactionController;
use App\Http\Controllers\Api\Finance\JournalEntryController;
use App\Http\Controllers\Api\Finance\ReportController as FinanceReportController;
use App\Http\Controllers\Api\Finance\BudgetController;
use App\Http\Controllers\Api\Finance\BudgetLineController;
use App\Http\Controllers\Api\Inventory\ProductController;
use App\Http\Controllers\Api\Inventory\CategoryController;
use App\Http\Controllers\Api\Inventory\WarehouseController;
use App\Http\Controllers\Api\Inventory\StockMovementController;
use App\Http\Controllers\Api\HR\EmployeeController;
use App\Http\Controllers\Api\HR\DepartmentController;
use App\Http\Controllers\Api\HR\LeaveRequestController;
use App\Http\Controllers\Api\HR\AttendanceController;
use App\Http\Controllers\Api\Sales\CustomerController;
use App\Http\Controllers\Api\Sales\OrderController;
use App\Http\Controllers\Api\Sales\InvoiceController;
use App\Http\Controllers\Api\Sales\CrmController;
use App\Http\Controllers\Api\HR\PayrollController;
use App\Http\Controllers\Api\Procurement\VendorController;
use App\Http\Controllers\Api\Procurement\PurchaseOrderController;
use App\Http\Controllers\Api\Manufacturing\BomController;
use App\Http\Controllers\Api\Manufacturing\WorkOrderController;
use App\Http\Controllers\Api\Projects\ProjectController;
use App\Http\Controllers\Api\Projects\TaskController;
use App\Http\Controllers\Api\Projects\TimeEntryController;
use App\Http\Controllers\Api\Quality\CheckController;
use App\Http\Controllers\Api\Quality\NonConformanceController;
use App\Http\Controllers\Api\Assets\AssetController;
use App\Http\Controllers\Api\Assets\MaintenanceController;
use App\Http\Controllers\Api\FieldService\TicketController;
use App\Http\Controllers\Api\LMS\CourseController;
use App\Http\Controllers\Api\LMS\EnrollmentController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\Marketing\CampaignController;
use App\Http\Controllers\Api\Marketing\LeadController;
use App\Http\Controllers\Api\Network\ProfileController as NetworkProfileController;
use App\Http\Controllers\Api\Network\DiscoveryController;
use App\Http\Controllers\Api\Network\FollowController;
use App\Http\Controllers\Api\Network\ConnectionController;
use App\Http\Controllers\Api\Network\FeedController;
use App\Http\Controllers\Api\Network\PostController;
use App\Http\Controllers\Api\Expenses\ExpenseController;
use App\Http\Controllers\Api\Expenses\ExpenseItemController;
use App\Http\Controllers\Api\Documents\DocumentController;
use App\Http\Controllers\Api\Contracts\ContractController;
use App\Http\Controllers\Api\Contracts\ContractMilestoneController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\Agent\AgentController;
use App\Http\Controllers\Api\Agent\AgentTokenController;
use App\Http\Controllers\Api\Agent\AgentSkillController;
use App\Http\Controllers\Api\Agent\AgentExecutionController;
use App\Http\Controllers\Api\Agent\AgentScheduleController;
use App\Http\Controllers\Api\ESignature\ESignatureController;
use App\Http\Controllers\Api\Pos\TerminalController;
use App\Http\Controllers\Api\Pos\TransactionController as PosTransactionController;
use App\Http\Controllers\Api\Fleet\VehicleController;
use App\Http\Controllers\Api\Fleet\DriverController;
use App\Http\Controllers\Api\Fleet\TripController;
use App\Http\Controllers\Api\Fleet\FuelLogController;
use App\Http\Controllers\Api\Fleet\MaintenanceController as FleetMaintenanceController;

/*
|--------------------------------------------------------------------------
| API Routes — TPT Free ERP v1
|--------------------------------------------------------------------------
| All versioned endpoints live under /api/v1/
| Auth endpoints live under /api/auth/ (no version prefix)
|--------------------------------------------------------------------------
*/

// ===== PUBLIC ROUTES (no auth, rate-limited to 5/min) =====
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/magic-link/send', [AuthController::class, 'sendMagicLink']);
    Route::post('/auth/magic-link/verify', [AuthController::class, 'verifyMagicLink']);
});

// ===== PUBLIC E-SIGNATURE ENDPOINTS (no auth — accessed via emailed token link) =====
Route::prefix('esignatures/sign')->group(function () {
    Route::get('/{token}', [ESignatureController::class, 'getByToken']);
    Route::post('/{token}', [ESignatureController::class, 'sign']);
    Route::post('/{token}/decline', [ESignatureController::class, 'decline']);
});

// ===== HEALTH CHECK =====
Route::get('/health', function () {
    return response()->json([
        'status'    => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version'   => config('app.version', '1.0.0'),
        'modules'   => [
            'finance', 'inventory', 'hr', 'sales', 'procurement',
            'manufacturing', 'projects', 'quality', 'assets',
            'field_service', 'lms', 'marketing', 'network',
            'expenses', 'budgets', 'documents', 'contracts', 'pos', 'fleet',
        ],
    ]);
});

// ===== AUTHENTICATED ROUTES — all under /v1 =====
Route::middleware(['auth:sanctum', 'throttle:api', 'cors.tpt'])->prefix('v1')->group(function () {

    // ----- Auth Management -----
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [ProfileController::class, 'update']);
    Route::put('/auth/password', [ProfileController::class, 'changePassword']);
    Route::post('/auth/totp/enable', [AuthController::class, 'enableTOTP']);
    Route::post('/auth/totp/disable', [AuthController::class, 'disableTOTP']);
    Route::post('/auth/totp/verify', [AuthController::class, 'verifyTOTP']);

    // ----- Dashboard -----
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/finance', [DashboardController::class, 'finance']);
    Route::get('/dashboard/inventory', [DashboardController::class, 'inventory']);
    Route::get('/dashboard/hr', [DashboardController::class, 'hr']);
    Route::get('/dashboard/sales', [DashboardController::class, 'sales']);

    // ----- Onboarding -----
    Route::prefix('onboarding')->group(function () {
        Route::get('/presets', [OnboardingController::class, 'presets']);
        Route::get('/status', [OnboardingController::class, 'status']);
        Route::post('/apply', [OnboardingController::class, 'apply']);
        Route::post('/skip', [OnboardingController::class, 'skip']);
    });

    // ===== FINANCE MODULE =====
    Route::prefix('finance')->group(function () {
        Route::middleware('permission:finance.view')->group(function () {
            Route::get('accounts', [AccountController::class, 'index']);
            Route::get('accounts/{account}', [AccountController::class, 'show']);
            Route::get('accounts/{account}/transactions', [TransactionController::class, 'byAccount']);
            Route::get('accounts/{account}/balance', [AccountController::class, 'balance']);
            Route::get('transactions', [TransactionController::class, 'index']);
            Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
            Route::get('journal-entries', [JournalEntryController::class, 'index']);
            Route::get('journal-entries/{entry}', [JournalEntryController::class, 'show']);
            Route::get('reports/balance-sheet', [FinanceReportController::class, 'balanceSheet']);
            Route::get('reports/income-statement', [FinanceReportController::class, 'incomeStatement']);
            Route::get('reports/cash-flow', [FinanceReportController::class, 'cashFlow']);
            Route::get('reports/trial-balance', [FinanceReportController::class, 'trialBalance']);
            Route::get('budgets', [BudgetController::class, 'index']);
            Route::get('budgets/{budget}', [BudgetController::class, 'show']);
            Route::get('budgets/{budget}/lines', [BudgetLineController::class, 'listLines']);
            Route::get('budgets/{budget}/lines/{line}', [BudgetLineController::class, 'getLine']);
            Route::get('budgets/{budget}/variance', [BudgetLineController::class, 'variance']);
        });
        Route::middleware('permission:finance.create')->group(function () {
            Route::post('accounts', [AccountController::class, 'store']);
            Route::post('transactions', [TransactionController::class, 'store']);
            Route::post('journal-entries', [JournalEntryController::class, 'store']);
            Route::post('budgets', [BudgetController::class, 'store']);
            Route::post('budgets/{budget}/lines', [BudgetLineController::class, 'createLine']);
        });
        Route::middleware('permission:finance.edit')->group(function () {
            Route::put('accounts/{account}', [AccountController::class, 'update']);
            Route::patch('accounts/{account}', [AccountController::class, 'update']);
            Route::put('transactions/{transaction}', [TransactionController::class, 'update']);
            Route::patch('transactions/{transaction}', [TransactionController::class, 'update']);
            Route::put('journal-entries/{entry}', [JournalEntryController::class, 'update']);
            Route::patch('journal-entries/{entry}', [JournalEntryController::class, 'update']);
            Route::put('budgets/{budget}', [BudgetController::class, 'update']);
            Route::put('budgets/{budget}/lines/{line}', [BudgetLineController::class, 'updateLine']);
            Route::patch('budgets/{budget}/lines/{line}', [BudgetLineController::class, 'updateLine']);
        });
        Route::middleware('permission:finance.approve')->group(function () {
            Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve']);
            Route::post('transactions/{transaction}/void', [TransactionController::class, 'void']);
            Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve']);
            Route::post('budgets/{budget}/close', [BudgetController::class, 'close']);
        });
        Route::middleware('permission:finance.delete')->group(function () {
            Route::delete('accounts/{account}', [AccountController::class, 'destroy']);
            Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);
            Route::delete('journal-entries/{entry}', [JournalEntryController::class, 'destroy']);
            Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);
            Route::delete('budgets/{budget}/lines/{line}', [BudgetLineController::class, 'deleteLine']);
        });
    });

    // ===== INVENTORY MODULE =====
    Route::prefix('inventory')->group(function () {
        Route::middleware('permission:inventory.view')->group(function () {
            Route::get('products', [ProductController::class, 'index']);
            Route::get('products/{product}', [ProductController::class, 'show']);
            Route::get('products/{product}/stock', [ProductController::class, 'stock']);
            Route::get('products/{product}/movements', [ProductController::class, 'movements']);
            Route::get('stock/low-stock', [ProductController::class, 'lowStock']);
            Route::get('categories', [CategoryController::class, 'index']);
            Route::get('categories/{category}', [CategoryController::class, 'show']);
            Route::get('warehouses', [WarehouseController::class, 'index']);
            Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
            Route::get('stock-movements', [StockMovementController::class, 'index']);
            Route::get('stock-movements/{movement}', [StockMovementController::class, 'show']);
        });
        Route::middleware('permission:inventory.create')->group(function () {
            Route::post('products', [ProductController::class, 'store']);
            Route::post('categories', [CategoryController::class, 'store']);
            Route::post('warehouses', [WarehouseController::class, 'store']);
            Route::post('stock-movements', [StockMovementController::class, 'store']);
            Route::post('stock/transfer', [ProductController::class, 'transferStock']);
        });
        Route::middleware('permission:inventory.edit')->group(function () {
            Route::put('products/{product}', [ProductController::class, 'update']);
            Route::patch('products/{product}', [ProductController::class, 'update']);
            Route::put('categories/{category}', [CategoryController::class, 'update']);
            Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
            Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
        });
        Route::middleware('permission:inventory.delete')->group(function () {
            Route::delete('products/{product}', [ProductController::class, 'destroy']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
            Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
        });
    });

    // ===== HR MODULE =====
    Route::prefix('hr')->group(function () {
        Route::middleware('permission:hr.view')->group(function () {
            Route::get('employees', [EmployeeController::class, 'index']);
            Route::get('employees/{employee}', [EmployeeController::class, 'show']);
            Route::get('employees/{employee}/attendance', [AttendanceController::class, 'byEmployee']);
            Route::get('employees/{employee}/leave-history', [LeaveRequestController::class, 'byEmployee']);
            Route::get('departments', [DepartmentController::class, 'index']);
            Route::get('departments/{department}', [DepartmentController::class, 'show']);
            Route::get('leave-requests', [LeaveRequestController::class, 'index']);
            Route::get('leave-requests/{request}', [LeaveRequestController::class, 'show']);
            Route::get('attendance', [AttendanceController::class, 'index']);
            Route::get('attendance/{record}', [AttendanceController::class, 'show']);
            Route::get('payroll', [PayrollController::class, 'index']);
            Route::get('payroll/{payroll}', [PayrollController::class, 'show']);
            Route::get('reports/attendance', [ReportController::class, 'attendance']);
            Route::get('reports/payroll', [ReportController::class, 'payroll']);
        });
        Route::middleware('permission:hr.create')->group(function () {
            Route::post('employees', [EmployeeController::class, 'store']);
            Route::post('departments', [DepartmentController::class, 'store']);
            Route::post('leave-requests', [LeaveRequestController::class, 'store']);
            Route::post('attendance', [AttendanceController::class, 'store']);
            Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
            Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut']);
            Route::post('payroll', [PayrollController::class, 'store']);
        });
        Route::middleware('permission:hr.edit')->group(function () {
            Route::put('employees/{employee}', [EmployeeController::class, 'update']);
            Route::patch('employees/{employee}', [EmployeeController::class, 'update']);
            Route::put('departments/{department}', [DepartmentController::class, 'update']);
            Route::put('leave-requests/{request}', [LeaveRequestController::class, 'update']);
            Route::put('attendance/{record}', [AttendanceController::class, 'update']);
        });
        Route::middleware('permission:hr.approve')->group(function () {
            Route::post('payroll/{payroll}/process', [PayrollController::class, 'process']);
            Route::post('payroll/{payroll}/approve', [PayrollController::class, 'approve']);
            Route::post('payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid']);
        });
        Route::middleware('permission:hr.delete')->group(function () {
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy']);
            Route::delete('departments/{department}', [DepartmentController::class, 'destroy']);
            Route::delete('leave-requests/{request}', [LeaveRequestController::class, 'destroy']);
        });
    });

    // ===== SALES MODULE =====
    Route::prefix('sales')->group(function () {
        Route::middleware('permission:sales.view')->group(function () {
            Route::get('customers', [CustomerController::class, 'index']);
            Route::get('customers/{customer}', [CustomerController::class, 'show']);
            Route::get('orders', [OrderController::class, 'index']);
            Route::get('orders/{order}', [OrderController::class, 'show']);
            Route::get('orders/{order}/items', [OrderController::class, 'items']);
            Route::get('invoices', [InvoiceController::class, 'index']);
            Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
            Route::get('crm', [CrmController::class, 'index']);
            Route::get('crm/{pipeline}', [CrmController::class, 'show']);
            Route::get('crm/pipeline/summary', [CrmController::class, 'pipelineSummary']);
            Route::get('reports/sales', [ReportController::class, 'sales']);
            Route::get('reports/customer', [ReportController::class, 'customer']);
        });
        Route::middleware('permission:sales.create')->group(function () {
            Route::post('customers', [CustomerController::class, 'store']);
            Route::post('orders', [OrderController::class, 'store']);
            Route::post('orders/{order}/items', [OrderController::class, 'addItem']);
            Route::post('invoices', [InvoiceController::class, 'store']);
            Route::post('crm', [CrmController::class, 'store']);
        });
        Route::middleware('permission:sales.edit')->group(function () {
            Route::put('customers/{customer}', [CustomerController::class, 'update']);
            Route::patch('customers/{customer}', [CustomerController::class, 'update']);
            Route::put('orders/{order}', [OrderController::class, 'update']);
            Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);
            Route::put('invoices/{invoice}', [InvoiceController::class, 'update']);
            Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
            Route::post('invoices/{invoice}/record-payment', [InvoiceController::class, 'recordPayment']);
            Route::put('crm/{pipeline}', [CrmController::class, 'update']);
        });
        Route::middleware('permission:sales.delete')->group(function () {
            Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
            Route::delete('orders/{order}', [OrderController::class, 'destroy']);
            Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy']);
            Route::delete('crm/{pipeline}', [CrmController::class, 'destroy']);
        });
    });

    // ===== PROCUREMENT MODULE =====
    Route::prefix('procurement')->group(function () {
        Route::middleware('permission:procurement.view')->group(function () {
            Route::get('vendors', [VendorController::class, 'index']);
            Route::get('vendors/{vendor}', [VendorController::class, 'show']);
            Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
            Route::get('purchase-orders/{po}', [PurchaseOrderController::class, 'show']);
            Route::get('purchase-orders/{po}/items', [PurchaseOrderController::class, 'items']);
            Route::get('reports/purchases', [ReportController::class, 'purchases']);
        });
        Route::middleware('permission:procurement.create')->group(function () {
            Route::post('vendors', [VendorController::class, 'store']);
            Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
            Route::post('purchase-orders/{po}/items', [PurchaseOrderController::class, 'addItem']);
        });
        Route::middleware('permission:procurement.edit')->group(function () {
            Route::put('vendors/{vendor}', [VendorController::class, 'update']);
            Route::put('purchase-orders/{po}', [PurchaseOrderController::class, 'update']);
            Route::put('purchase-orders/{po}/status', [PurchaseOrderController::class, 'updateStatus']);
            Route::post('purchase-orders/{po}/receive', [PurchaseOrderController::class, 'receive']);
        });
        Route::middleware('permission:procurement.approve')->group(function () {
            Route::post('purchase-orders/{po}/approve', [PurchaseOrderController::class, 'approve']);
        });
        Route::middleware('permission:procurement.delete')->group(function () {
            Route::delete('vendors/{vendor}', [VendorController::class, 'destroy']);
            Route::delete('purchase-orders/{po}', [PurchaseOrderController::class, 'destroy']);
        });
    });

    // ===== MANUFACTURING MODULE =====
    Route::prefix('manufacturing')->group(function () {
        Route::middleware('permission:manufacturing.view')->group(function () {
            Route::get('boms', [BomController::class, 'index']);
            Route::get('boms/{bom}', [BomController::class, 'show']);
            Route::get('boms/{bom}/components', [BomController::class, 'components']);
            Route::get('work-orders', [WorkOrderController::class, 'index']);
            Route::get('work-orders/{wo}', [WorkOrderController::class, 'show']);
        });
        Route::middleware('permission:manufacturing.create')->group(function () {
            Route::post('boms', [BomController::class, 'store']);
            Route::post('work-orders', [WorkOrderController::class, 'store']);
        });
        Route::middleware('permission:manufacturing.edit')->group(function () {
            Route::put('boms/{bom}', [BomController::class, 'update']);
            Route::put('work-orders/{wo}', [WorkOrderController::class, 'update']);
            Route::post('work-orders/{wo}/start', [WorkOrderController::class, 'start']);
            Route::post('work-orders/{wo}/complete', [WorkOrderController::class, 'complete']);
            Route::post('work-orders/{wo}/record-production', [WorkOrderController::class, 'recordProduction']);
        });
        Route::middleware('permission:manufacturing.delete')->group(function () {
            Route::delete('boms/{bom}', [BomController::class, 'destroy']);
            Route::delete('work-orders/{wo}', [WorkOrderController::class, 'destroy']);
        });
    });

    // ===== PROJECTS MODULE =====
    Route::prefix('projects')->group(function () {
        Route::middleware('permission:projects.view')->group(function () {
            Route::get('projects', [ProjectController::class, 'index']);
            Route::get('projects/{project}', [ProjectController::class, 'show']);
            Route::get('projects/{project}/tasks', [TaskController::class, 'byProject']);
            Route::get('tasks', [TaskController::class, 'index']);
            Route::get('tasks/{task}', [TaskController::class, 'show']);
            Route::get('time-entries', [TimeEntryController::class, 'index']);
            Route::get('time-entries/{entry}', [TimeEntryController::class, 'show']);
            Route::get('reports/projects', [ReportController::class, 'projects']);
        });
        Route::middleware('permission:projects.create')->group(function () {
            Route::post('projects', [ProjectController::class, 'store']);
            Route::post('tasks', [TaskController::class, 'store']);
            Route::post('time-entries', [TimeEntryController::class, 'store']);
        });
        Route::middleware('permission:projects.edit')->group(function () {
            Route::put('projects/{project}', [ProjectController::class, 'update']);
            Route::put('tasks/{task}', [TaskController::class, 'update']);
            Route::put('tasks/{task}/status', [TaskController::class, 'updateStatus']);
            Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);
            Route::put('time-entries/{entry}', [TimeEntryController::class, 'update']);
        });
        Route::middleware('permission:projects.delete')->group(function () {
            Route::delete('projects/{project}', [ProjectController::class, 'destroy']);
            Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
            Route::delete('time-entries/{entry}', [TimeEntryController::class, 'destroy']);
        });
    });

    // ===== QUALITY MODULE =====
    Route::prefix('quality')->group(function () {
        Route::middleware('permission:quality.view')->group(function () {
            Route::get('checks', [CheckController::class, 'index']);
            Route::get('checks/{check}', [CheckController::class, 'show']);
            Route::get('non-conformances', [NonConformanceController::class, 'index']);
            Route::get('non-conformances/{nc}', [NonConformanceController::class, 'show']);
        });
        Route::middleware('permission:quality.create')->group(function () {
            Route::post('checks', [CheckController::class, 'store']);
            Route::post('non-conformances', [NonConformanceController::class, 'store']);
        });
        Route::middleware('permission:quality.edit')->group(function () {
            Route::put('checks/{check}', [CheckController::class, 'update']);
            Route::post('checks/{check}/record-result', [CheckController::class, 'recordResult']);
            Route::put('non-conformances/{nc}', [NonConformanceController::class, 'update']);
            Route::put('non-conformances/{nc}/status', [NonConformanceController::class, 'updateStatus']);
        });
        Route::middleware('permission:quality.delete')->group(function () {
            Route::delete('checks/{check}', [CheckController::class, 'destroy']);
            Route::delete('non-conformances/{nc}', [NonConformanceController::class, 'destroy']);
        });
    });

    // ===== ASSETS MODULE =====
    Route::prefix('assets')->group(function () {
        Route::middleware('permission:assets.view')->group(function () {
            Route::get('assets', [AssetController::class, 'index']);
            Route::get('assets/{asset}', [AssetController::class, 'show']);
            Route::get('assets/{asset}/maintenance-history', [MaintenanceController::class, 'byAsset']);
            Route::get('maintenance', [MaintenanceController::class, 'index']);
            Route::get('maintenance/{record}', [MaintenanceController::class, 'show']);
        });
        Route::middleware('permission:assets.create')->group(function () {
            Route::post('assets', [AssetController::class, 'store']);
            Route::post('maintenance', [MaintenanceController::class, 'store']);
        });
        Route::middleware('permission:assets.edit')->group(function () {
            Route::put('assets/{asset}', [AssetController::class, 'update']);
            Route::patch('assets/{asset}', [AssetController::class, 'update']);
            Route::post('assets/{asset}/depreciate', [AssetController::class, 'calculateDepreciation']);
            Route::put('maintenance/{record}', [MaintenanceController::class, 'update']);
        });
        Route::middleware('permission:assets.delete')->group(function () {
            Route::delete('assets/{asset}', [AssetController::class, 'destroy']);
            Route::delete('maintenance/{record}', [MaintenanceController::class, 'destroy']);
        });
    });

    // ===== FIELD SERVICE MODULE =====
    Route::prefix('field-service')->group(function () {
        Route::middleware('permission:field_service.view')->group(function () {
            Route::get('tickets', [TicketController::class, 'index']);
            Route::get('tickets/{ticket}', [TicketController::class, 'show']);
        });
        Route::middleware('permission:field_service.create')->group(function () {
            Route::post('tickets', [TicketController::class, 'store']);
        });
        Route::middleware('permission:field_service.edit')->group(function () {
            Route::put('tickets/{ticket}', [TicketController::class, 'update']);
            Route::put('tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
            Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
        });
        Route::middleware('permission:field_service.delete')->group(function () {
            Route::delete('tickets/{ticket}', [TicketController::class, 'destroy']);
        });
    });

    // ===== LMS MODULE =====
    Route::prefix('lms')->group(function () {
        Route::middleware('permission:lms.view')->group(function () {
            Route::get('courses', [CourseController::class, 'index']);
            Route::get('courses/{course}', [CourseController::class, 'show']);
            Route::get('enrollments', [EnrollmentController::class, 'index']);
            Route::get('enrollments/{enrollment}', [EnrollmentController::class, 'show']);
        });
        Route::middleware('permission:lms.create')->group(function () {
            Route::post('courses', [CourseController::class, 'store']);
            Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
            Route::post('enrollments', [EnrollmentController::class, 'store']);
        });
        Route::middleware('permission:lms.edit')->group(function () {
            Route::put('courses/{course}', [CourseController::class, 'update']);
            Route::put('enrollments/{enrollment}', [EnrollmentController::class, 'update']);
            Route::put('enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete']);
        });
        Route::middleware('permission:lms.delete')->group(function () {
            Route::delete('courses/{course}', [CourseController::class, 'destroy']);
            Route::delete('enrollments/{enrollment}', [EnrollmentController::class, 'destroy']);
        });
    });

    // ===== MARKETING MODULE =====
    Route::prefix('marketing')->group(function () {
        Route::middleware('permission:marketing.view')->group(function () {
            Route::get('campaigns', [CampaignController::class, 'index']);
            Route::get('campaigns/{campaign}', [CampaignController::class, 'show']);
            Route::get('campaigns/{campaign}/analytics', [CampaignController::class, 'analytics']);
            Route::get('campaigns/{campaign}/roi', [CampaignController::class, 'roi']);
            Route::get('leads', [LeadController::class, 'index']);
            Route::get('leads/{lead}', [LeadController::class, 'show']);
        });
        Route::middleware('permission:marketing.create')->group(function () {
            Route::post('campaigns', [CampaignController::class, 'store']);
            Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send']);
            Route::post('leads', [LeadController::class, 'store']);
        });
        Route::middleware('permission:marketing.edit')->group(function () {
            Route::put('campaigns/{campaign}', [CampaignController::class, 'update']);
            Route::patch('campaigns/{campaign}', [CampaignController::class, 'update']);
            Route::put('leads/{lead}', [LeadController::class, 'update']);
            Route::post('leads/{lead}/convert', [LeadController::class, 'convert']);
            Route::post('leads/{lead}/add-to-pipeline', [LeadController::class, 'addToPipeline']);
        });
        Route::middleware('permission:marketing.delete')->group(function () {
            Route::delete('campaigns/{campaign}', [CampaignController::class, 'destroy']);
            Route::delete('leads/{lead}', [LeadController::class, 'destroy']);
        });
    });

    // ===== NETWORK MODULE =====
    Route::prefix('network')->group(function () {
        // Profile (everyone authenticated can manage their own)
        Route::get('/profile/me', [NetworkProfileController::class, 'me']);
        Route::post('/profile', [NetworkProfileController::class, 'store']);
        Route::put('/profile', [NetworkProfileController::class, 'update']);
        Route::post('/profile/opt-in', [NetworkProfileController::class, 'optIn']);
        Route::post('/profile/opt-out', [NetworkProfileController::class, 'optOut']);
        Route::post('/profile/avatar', [NetworkProfileController::class, 'uploadAvatar']);
        Route::get('/profiles/{profile}', [NetworkProfileController::class, 'show']);

        // Discovery (view discoverable profiles)
        Route::get('/discovery', [DiscoveryController::class, 'index']);
        Route::post('/discovery/{profile}/add-to-crm', [DiscoveryController::class, 'addToCrm']);
        Route::post('/discovery/{profile}/add-to-lead', [DiscoveryController::class, 'addToLead']);

        // Follows
        Route::get('/following', [FollowController::class, 'following']);
        Route::get('/followers', [FollowController::class, 'followers']);
        Route::post('/follow/{user}', [FollowController::class, 'follow']);
        Route::delete('/unfollow/{user}', [FollowController::class, 'unfollow']);

        // Connections
        Route::get('/connections', [ConnectionController::class, 'index']);
        Route::post('/connections/request/{user}', [ConnectionController::class, 'request']);
        Route::post('/connections/{connection}/accept', [ConnectionController::class, 'accept']);
        Route::post('/connections/{connection}/decline', [ConnectionController::class, 'decline']);
        Route::delete('/connections/{connection}', [ConnectionController::class, 'destroy']);

        // Feed & Posts
        Route::get('/feed', [FeedController::class, 'index']);
        Route::apiResource('/posts', PostController::class);
        Route::post('/posts/{post}/react', [PostController::class, 'react']);
        Route::post('/posts/{post}/comments', [PostController::class, 'addComment']);
        Route::delete('/posts/{post}/comments/{comment}', [PostController::class, 'deleteComment']);
        Route::delete('/posts/{post}/attachment', [PostController::class, 'removeAttachment']);
    });

    // ===== EXPENSES MODULE =====
    Route::prefix('expenses')->group(function () {
        Route::middleware('permission:expenses.view')->group(function () {
            Route::get('/', [ExpenseController::class, 'index']);
            Route::get('/summary', [ExpenseController::class, 'summary']);
            Route::get('/{expense}', [ExpenseController::class, 'show']);
            Route::get('/{expense}/items', [ExpenseItemController::class, 'listItems']);
            Route::get('/{expense}/items/{item}', [ExpenseItemController::class, 'getItem']);
        });
        Route::middleware('permission:expenses.create')->group(function () {
            Route::post('/', [ExpenseController::class, 'store']);
            Route::post('/{expense}/items', [ExpenseItemController::class, 'createItem']);
            Route::post('/{expense}/items/{item}/receipt', [ExpenseItemController::class, 'uploadReceipt']);
        });
        Route::middleware('permission:expenses.edit')->group(function () {
            Route::put('/{expense}', [ExpenseController::class, 'update']);
            Route::patch('/{expense}', [ExpenseController::class, 'update']);
            Route::put('/{expense}/items/{item}', [ExpenseItemController::class, 'updateItem']);
            Route::patch('/{expense}/items/{item}', [ExpenseItemController::class, 'updateItem']);
        });
        Route::middleware('permission:expenses.approve')->group(function () {
            Route::post('/{expense}/approve', [ExpenseController::class, 'approve']);
            Route::post('/{expense}/reject', [ExpenseController::class, 'reject']);
        });
        Route::middleware('permission:expenses.delete')->group(function () {
            Route::delete('/{expense}', [ExpenseController::class, 'destroy']);
            Route::delete('/{expense}/items/{item}', [ExpenseItemController::class, 'deleteItem']);
        });
    });

    // ===== DOCUMENTS MODULE =====
    // Public shared-link download (no auth required)
    Route::get('documents/shared/{token}', [DocumentController::class, 'sharedDownload']);

    Route::prefix('documents')->group(function () {
        Route::middleware('permission:documents.view')->group(function () {
            Route::get('/', [DocumentController::class, 'index']);
            Route::get('/folders', [DocumentController::class, 'folders']);
            Route::get('/{document}', [DocumentController::class, 'show']);
            Route::get('/{document}/download', [DocumentController::class, 'download']);
        });
        Route::middleware('permission:documents.create')->group(function () {
            Route::post('/', [DocumentController::class, 'store']);
            Route::post('/folders', [DocumentController::class, 'createFolder']);
            Route::post('/{document}/share', [DocumentController::class, 'share']);
        });
        Route::middleware('permission:documents.edit')->group(function () {
            Route::put('/{document}', [DocumentController::class, 'update']);
        });
        Route::middleware('permission:documents.delete')->group(function () {
            Route::delete('/{document}', [DocumentController::class, 'destroy']);
        });
    });

    // ===== CONTRACTS MODULE =====
    Route::prefix('contracts')->group(function () {
        Route::middleware('permission:contracts.view')->group(function () {
            Route::get('/', [ContractController::class, 'index']);
            Route::get('/{contract}', [ContractController::class, 'show']);
        });
        Route::middleware('permission:contracts.create')->group(function () {
            Route::post('/', [ContractController::class, 'store']);
        });
        Route::middleware('permission:contracts.edit')->group(function () {
            Route::put('/{contract}', [ContractController::class, 'update']);
            Route::patch('/{contract}', [ContractController::class, 'update']);
            Route::put('/{contract}/status', [ContractController::class, 'updateStatus']);
        });
        Route::middleware('permission:contracts.approve')->group(function () {
            Route::post('/{contract}/sign', [ContractController::class, 'sign']);
        });
        Route::middleware('permission:contracts.delete')->group(function () {
            Route::delete('/{contract}', [ContractController::class, 'destroy']);
        });

        // Milestones
        Route::middleware('permission:contracts.view')->group(function () {
            Route::get('/{contract}/milestones', [ContractMilestoneController::class, 'listMilestones']);
            Route::get('/{contract}/milestones/{milestone}', [ContractMilestoneController::class, 'getMilestone']);
        });
        Route::middleware('permission:contracts.create')->group(function () {
            Route::post('/{contract}/milestones', [ContractMilestoneController::class, 'createMilestone']);
        });
        Route::middleware('permission:contracts.edit')->group(function () {
            Route::put('/{contract}/milestones/{milestone}', [ContractMilestoneController::class, 'updateMilestone']);
            Route::patch('/{contract}/milestones/{milestone}', [ContractMilestoneController::class, 'updateMilestone']);
            Route::patch('/{contract}/milestones/{milestone}/complete', [ContractMilestoneController::class, 'completeMilestone']);
        });
        Route::middleware('permission:contracts.delete')->group(function () {
            Route::delete('/{contract}/milestones/{milestone}', [ContractMilestoneController::class, 'deleteMilestone']);
        });
    });

    // ===== POS MODULE =====
    Route::prefix('pos')->group(function () {
        Route::middleware('permission:pos.view')->group(function () {
            Route::get('terminals', [TerminalController::class, 'index']);
            Route::get('terminals/{terminal}', [TerminalController::class, 'show']);
            Route::get('transactions/summary', [PosTransactionController::class, 'summary']);
            Route::get('transactions', [PosTransactionController::class, 'index']);
            Route::get('transactions/{transaction}', [PosTransactionController::class, 'show']);
        });
        Route::middleware('permission:pos.create')->group(function () {
            Route::post('terminals', [TerminalController::class, 'store']);
            Route::post('transactions', [PosTransactionController::class, 'store']);
            Route::post('transactions/{transaction}/items', [PosTransactionController::class, 'addItem']);
            Route::post('transactions/{transaction}/checkout', [PosTransactionController::class, 'checkout']);
        });
        Route::middleware('permission:pos.edit')->group(function () {
            Route::put('terminals/{terminal}', [TerminalController::class, 'update']);
            Route::put('transactions/{transaction}', [PosTransactionController::class, 'update']);
            Route::delete('transactions/{transaction}/items/{item}', [PosTransactionController::class, 'removeItem']);
        });
        Route::middleware('permission:pos.delete')->group(function () {
            Route::delete('terminals/{terminal}', [TerminalController::class, 'destroy']);
            Route::delete('transactions/{transaction}', [PosTransactionController::class, 'destroy']);
            Route::post('transactions/{transaction}/void', [PosTransactionController::class, 'void']);
            Route::post('transactions/{transaction}/refund', [PosTransactionController::class, 'refund']);
        });
    });

    // ===== FLEET MANAGEMENT MODULE =====
    Route::prefix('fleet')->group(function () {
        Route::middleware('permission:fleet.view')->group(function () {
            Route::get('vehicles', [VehicleController::class, 'index']);
            Route::get('vehicles/{vehicle}', [VehicleController::class, 'show']);
            Route::get('drivers', [DriverController::class, 'index']);
            Route::get('drivers/{driver}', [DriverController::class, 'show']);
            Route::get('trips', [TripController::class, 'index']);
            Route::get('trips/{trip}', [TripController::class, 'show']);
            Route::get('fuel-logs', [FuelLogController::class, 'index']);
            Route::get('fuel-logs/{fuelLog}', [FuelLogController::class, 'show']);
            Route::get('maintenance', [FleetMaintenanceController::class, 'index']);
            Route::get('maintenance/{record}', [FleetMaintenanceController::class, 'show']);
        });
        Route::middleware('permission:fleet.create')->group(function () {
            Route::post('vehicles', [VehicleController::class, 'store']);
            Route::post('drivers', [DriverController::class, 'store']);
            Route::post('trips', [TripController::class, 'store']);
            Route::post('fuel-logs', [FuelLogController::class, 'store']);
            Route::post('maintenance', [FleetMaintenanceController::class, 'store']);
        });
        Route::middleware('permission:fleet.edit')->group(function () {
            Route::put('vehicles/{vehicle}', [VehicleController::class, 'update']);
            Route::put('drivers/{driver}', [DriverController::class, 'update']);
            Route::put('trips/{trip}', [TripController::class, 'update']);
            Route::post('trips/{trip}/start', [TripController::class, 'start']);
            Route::post('trips/{trip}/complete', [TripController::class, 'complete']);
            Route::put('maintenance/{record}', [FleetMaintenanceController::class, 'update']);
            Route::post('maintenance/{record}/complete', [FleetMaintenanceController::class, 'complete']);
        });
        Route::middleware('permission:fleet.delete')->group(function () {
            Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy']);
            Route::delete('drivers/{driver}', [DriverController::class, 'destroy']);
            Route::delete('trips/{trip}', [TripController::class, 'destroy']);
            Route::delete('fuel-logs/{fuelLog}', [FuelLogController::class, 'destroy']);
            Route::delete('maintenance/{record}', [FleetMaintenanceController::class, 'destroy']);
            Route::post('trips/{trip}/cancel', [TripController::class, 'cancel']);
        });
    });

    // ===== E-SIGNATURE MODULE =====
    Route::prefix('esignatures')->group(function () {
        Route::middleware('permission:contracts.view')->group(function () {
            Route::get('/', [ESignatureController::class, 'index']);
            Route::get('/{id}', [ESignatureController::class, 'show']);
            Route::get('/{id}/verify', [ESignatureController::class, 'verify']);
        });
        Route::middleware('permission:contracts.create')->group(function () {
            Route::post('/', [ESignatureController::class, 'store']);
        });
        Route::middleware('permission:contracts.delete')->group(function () {
            Route::delete('/{id}', [ESignatureController::class, 'destroy']);
        });
    });

    // ===== WEBHOOKS =====
    Route::prefix('webhooks')->group(function () {
        Route::get('/', [WebhookController::class, 'index']);
        Route::post('/', [WebhookController::class, 'store']);
        Route::get('/{webhook}', [WebhookController::class, 'show']);
        Route::put('/{webhook}', [WebhookController::class, 'update']);
        Route::delete('/{webhook}', [WebhookController::class, 'destroy']);
        Route::post('/{webhook}/test', [WebhookController::class, 'test']);
        Route::get('/{webhook}/deliveries', [WebhookController::class, 'deliveries']);
    });

    // ===== NOTIFICATIONS =====
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::put('/preferences', [NotificationController::class, 'updatePreferences']);
    });

    // ===== REPORTS =====
    Route::prefix('reports')->group(function () {
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/scheduled', [ReportController::class, 'scheduledIndex']);
        Route::post('/scheduled', [ReportController::class, 'scheduledStore']);
        Route::delete('/scheduled/{id}', [ReportController::class, 'scheduledDestroy']);
        Route::get('/{id}', [ReportController::class, 'show']);
        Route::get('/{id}/download', [ReportController::class, 'download']);
    });

    // ===== AI AGENTS MODULE (admin + agents.manage) =====
    Route::prefix('agents')->middleware('role:admin')->group(function () {
        // Skill catalog — all agents can browse
        Route::get('/skills/available', [AgentSkillController::class, 'catalog']);
        Route::post('/skills/upload', [AgentSkillController::class, 'upload']);

        // Agent CRUD
        Route::get('/', [AgentController::class, 'index']);
        Route::post('/', [AgentController::class, 'store']);
        Route::get('/{id}', [AgentController::class, 'show']);
        Route::put('/{id}', [AgentController::class, 'update']);
        Route::delete('/{id}', [AgentController::class, 'destroy']);

        // Tokens
        Route::post('/{id}/tokens', [AgentTokenController::class, 'createToken']);
        Route::delete('/{id}/tokens/{tokenId}', [AgentTokenController::class, 'deleteToken']);

        // Skills per agent
        Route::get('/{id}/skills', [AgentSkillController::class, 'listSkills']);
        Route::put('/{id}/skills/{slug}', [AgentSkillController::class, 'updateSkill']);
        Route::post('/{id}/skills/{slug}/run', [AgentSkillController::class, 'run']);

        // Executions (audit log)
        Route::get('/{id}/executions', [AgentExecutionController::class, 'listExecutions']);
        Route::get('/{id}/executions/{execId}', [AgentExecutionController::class, 'getExecution']);

        // Schedules
        Route::get('/{id}/schedules', [AgentScheduleController::class, 'listSchedules']);
        Route::post('/{id}/schedules', [AgentScheduleController::class, 'createSchedule']);
        Route::delete('/{id}/schedules/{schedId}', [AgentScheduleController::class, 'deleteSchedule']);
    });

    // ===== GDPR =====
    Route::prefix('gdpr')->group(function () {
        Route::get('/export', [\App\Http\Controllers\Api\GDPRController::class, 'exportData']);
        Route::post('/erase', [\App\Http\Controllers\Api\GDPRController::class, 'requestErasure']);
        Route::post('/rectify', [\App\Http\Controllers\Api\GDPRController::class, 'requestRectification']);
        Route::get('/consents', [\App\Http\Controllers\Api\GDPRController::class, 'consents']);
        Route::post('/consents/{type}/withdraw', [\App\Http\Controllers\Api\GDPRController::class, 'withdrawConsent']);
    });

    // ===== USER MANAGEMENT (admin only) =====
    Route::prefix('users')->middleware('role:admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\UserController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\UserController::class, 'store']);
        Route::get('/{user}', [\App\Http\Controllers\Api\UserController::class, 'show']);
        Route::put('/{user}', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::delete('/{user}', [\App\Http\Controllers\Api\UserController::class, 'destroy']);
    });

    // ===== ROLE & PERMISSION MANAGEMENT (admin only) =====
    Route::prefix('roles')->middleware('role:admin')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('/permissions', [RoleController::class, 'permissions']);
        Route::get('/{role}', [RoleController::class, 'show']);
        Route::put('/{role}', [RoleController::class, 'update']);
        Route::delete('/{role}', [RoleController::class, 'destroy']);
        Route::put('/{role}/permissions', [RoleController::class, 'syncPermissions']);
        Route::post('/{role}/users/assign', [RoleController::class, 'assignUser']);
        Route::post('/{role}/users/revoke', [RoleController::class, 'revokeUser']);
    });

    // ===== COMPANY SETTINGS =====
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::put('/settings', [SettingsController::class, 'update'])->middleware('role:admin');

    // ===== SECURITY (admin only) =====
    Route::prefix('security')->middleware('role:admin')->group(function () {
        Route::get('/events', [\App\Http\Controllers\Api\SecurityController::class, 'events']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\SecurityController::class, 'dashboard']);
        Route::get('/sessions', [\App\Http\Controllers\Api\SecurityController::class, 'sessions']);
        Route::delete('/sessions/{session}', [\App\Http\Controllers\Api\SecurityController::class, 'terminateSession']);
    });
});

// ===== LEGACY ALIASES — /api/{module} redirects to /api/v1/{module} for backwards compat =====
Route::middleware(['auth:sanctum', 'throttle:api', 'cors.tpt'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout.legacy');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me.legacy');
});
