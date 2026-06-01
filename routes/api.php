<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Finance\AccountController;
use App\Http\Controllers\Api\Finance\TransactionController;
use App\Http\Controllers\Api\Finance\JournalEntryController;
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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Complete ERP API with 30+ modules
|--------------------------------------------------------------------------
*/

// Public Routes (no auth required)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/magic-link/send', [AuthController::class, 'sendMagicLink']);
Route::post('/auth/magic-link/verify', [AuthController::class, 'verifyMagicLink']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
        'modules' => [
            'finance' => true,
            'inventory' => true,
            'hr' => true,
            'sales' => true,
            'procurement' => true,
            'manufacturing' => true,
            'projects' => true,
            'quality' => true,
            'assets' => true,
            'field_service' => true,
            'lms' => true,
            'security' => true,
            'gdpr' => true,
            'notifications' => true,
            'reports' => true,
        ],
    ]);
});

// Authenticated Routes
Route::middleware(['auth:sanctum', 'throttle:api', 'cors.tpt'])->group(function () {
    // Auth Management
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::put('/auth/profile', [ProfileController::class, 'update']);
    Route::put('/auth/password', [ProfileController::class, 'changePassword']);
    Route::post('/auth/totp/enable', [AuthController::class, 'enableTOTP']);
    Route::post('/auth/totp/disable', [AuthController::class, 'disableTOTP']);
    Route::post('/auth/totp/verify', [AuthController::class, 'verifyTOTP']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/finance', [DashboardController::class, 'finance']);
    Route::get('/dashboard/inventory', [DashboardController::class, 'inventory']);
    Route::get('/dashboard/hr', [DashboardController::class, 'hr']);
    Route::get('/dashboard/sales', [DashboardController::class, 'sales']);

    // ===== FINANCE MODULE =====
    Route::prefix('finance')->group(function () {
        Route::apiResource('accounts', AccountController::class);
        Route::apiResource('transactions', TransactionController::class);
        Route::apiResource('journal-entries', JournalEntryController::class);
        Route::get('accounts/{account}/transactions', [TransactionController::class, 'byAccount']);
        Route::get('accounts/{account}/balance', [AccountController::class, 'balance']);
        Route::post('transactions/{transaction}/approve', [TransactionController::class, 'approve']);
        Route::post('transactions/{transaction}/void', [TransactionController::class, 'void']);
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet']);
        Route::get('reports/income-statement', [ReportController::class, 'incomeStatement']);
        Route::get('reports/cash-flow', [ReportController::class, 'cashFlow']);
        Route::get('reports/trial-balance', [ReportController::class, 'trialBalance']);
    });

    // ===== INVENTORY MODULE =====
    Route::prefix('inventory')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
        Route::apiResource('warehouses', WarehouseController::class);
        Route::apiResource('stock-movements', StockMovementController::class);
        Route::get('products/{product}/stock', [ProductController::class, 'stock']);
        Route::get('products/{product}/movements', [ProductController::class, 'movements']);
        Route::get('stock/low-stock', [ProductController::class, 'lowStock']);
        Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock']);
        Route::post('stock/transfer', [ProductController::class, 'transferStock']);
    });

    // ===== HR MODULE =====
    Route::prefix('hr')->group(function () {
        Route::apiResource('employees', EmployeeController::class);
        Route::apiResource('departments', DepartmentController::class);
        Route::apiResource('leave-requests', LeaveRequestController::class);
        Route::apiResource('attendance', AttendanceController::class);
        Route::apiResource('payroll', PayrollController::class);
        Route::post('payroll/{payroll}/process', [PayrollController::class, 'process']);
        Route::post('payroll/{payroll}/approve', [PayrollController::class, 'approve']);
        Route::post('payroll/{payroll}/mark-paid', [PayrollController::class, 'markPaid']);
        Route::get('employees/{employee}/attendance', [AttendanceController::class, 'byEmployee']);
        Route::get('employees/{employee}/leave-history', [LeaveRequestController::class, 'byEmployee']);
        Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('reports/attendance', [ReportController::class, 'attendance']);
        Route::get('reports/payroll', [ReportController::class, 'payroll']);
    });

    // ===== SALES MODULE =====
    Route::prefix('sales')->group(function () {
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('crm', CrmController::class);
        Route::get('crm/pipeline/summary', [CrmController::class, 'pipelineSummary']);
        Route::get('orders/{order}/items', [OrderController::class, 'items']);
        Route::post('orders/{order}/items', [OrderController::class, 'addItem']);
        Route::put('orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('invoices/{invoice}/record-payment', [InvoiceController::class, 'recordPayment']);
        Route::get('reports/sales', [ReportController::class, 'sales']);
        Route::get('reports/customer', [ReportController::class, 'customer']);
    });

    // ===== PROCUREMENT MODULE =====
    Route::prefix('procurement')->group(function () {
        Route::apiResource('vendors', VendorController::class);
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::get('purchase-orders/{po}/items', [PurchaseOrderController::class, 'items']);
        Route::post('purchase-orders/{po}/items', [PurchaseOrderController::class, 'addItem']);
        Route::put('purchase-orders/{po}/status', [PurchaseOrderController::class, 'updateStatus']);
        Route::post('purchase-orders/{po}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('purchase-orders/{po}/approve', [PurchaseOrderController::class, 'approve']);
        Route::get('reports/purchases', [ReportController::class, 'purchases']);
    });

    // ===== MANUFACTURING MODULE =====
    Route::prefix('manufacturing')->group(function () {
        Route::apiResource('boms', BomController::class);
        Route::apiResource('work-orders', WorkOrderController::class);
        Route::get('boms/{bom}/components', [BomController::class, 'components']);
        Route::post('work-orders/{wo}/start', [WorkOrderController::class, 'start']);
        Route::post('work-orders/{wo}/complete', [WorkOrderController::class, 'complete']);
        Route::post('work-orders/{wo}/record-production', [WorkOrderController::class, 'recordProduction']);
    });

    // ===== PROJECT MANAGEMENT =====
    Route::prefix('projects')->group(function () {
        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('tasks', TaskController::class);
        Route::apiResource('time-entries', TimeEntryController::class);
        Route::get('projects/{project}/tasks', [TaskController::class, 'byProject']);
        Route::put('tasks/{task}/status', [TaskController::class, 'updateStatus']);
        Route::post('tasks/{task}/assign', [TaskController::class, 'assign']);
        Route::get('reports/projects', [ReportController::class, 'projects']);
    });

    // ===== QUALITY MANAGEMENT =====
    Route::prefix('quality')->group(function () {
        Route::apiResource('checks', CheckController::class);
        Route::apiResource('non-conformances', NonConformanceController::class);
        Route::post('checks/{check}/record-result', [CheckController::class, 'recordResult']);
        Route::put('non-conformances/{nc}/status', [NonConformanceController::class, 'updateStatus']);
    });

    // ===== ASSET MANAGEMENT =====
    Route::prefix('assets')->group(function () {
        Route::apiResource('assets', AssetController::class);
        Route::apiResource('maintenance', MaintenanceController::class);
        Route::get('assets/{asset}/maintenance-history', [MaintenanceController::class, 'byAsset']);
        Route::post('assets/{asset}/depreciate', [AssetController::class, 'calculateDepreciation']);
    });

    // ===== FIELD SERVICE =====
    Route::prefix('field-service')->group(function () {
        Route::apiResource('tickets', TicketController::class);
        Route::put('tickets/{ticket}/status', [TicketController::class, 'updateStatus']);
        Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign']);
    });

    // ===== LEARNING MANAGEMENT =====
    Route::prefix('lms')->group(function () {
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('enrollments', EnrollmentController::class);
        Route::post('courses/{course}/enroll', [EnrollmentController::class, 'enroll']);
        Route::put('enrollments/{enrollment}/complete', [EnrollmentController::class, 'complete']);
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
        Route::get('/', [ReportController::class, 'index']);
        Route::get('/available', [ReportController::class, 'available']);
        Route::post('/generate', [ReportController::class, 'generate']);
        Route::get('/scheduled', [ReportController::class, 'scheduled']);
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

    // ===== COMPANY SETTINGS =====
    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings', [SettingsController::class, 'update'])->middleware('role:admin');

    // ===== SECURITY (admin only) =====
    Route::prefix('security')->middleware('role:admin')->group(function () {
        Route::get('/events', [\App\Http\Controllers\Api\SecurityController::class, 'events']);
        Route::get('/dashboard', [\App\Http\Controllers\Api\SecurityController::class, 'dashboard']);
        Route::get('/sessions', [\App\Http\Controllers\Api\SecurityController::class, 'sessions']);
        Route::delete('/sessions/{session}', [\App\Http\Controllers\Api\SecurityController::class, 'terminateSession']);
    });
});