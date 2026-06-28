export interface Role {
    id: number;
    name: string;
    display_name: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    roles?: Role[];
    created_at: string;
    updated_at: string;
}

export interface AuthResponse {
    user: User;
    token: string;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface ApiResponse<T = unknown> {
    data: T;
    message?: string;
    success: boolean;
}

export interface Account {
    id: number;
    code: string;
    name: string;
    type: 'asset' | 'liability' | 'equity' | 'revenue' | 'expense';
    description: string | null;
    is_active: boolean;
    balance: number;
    created_at: string;
    updated_at: string;
}

export interface Transaction {
    id: number;
    account_id: number;
    account: Account;
    type: 'debit' | 'credit';
    amount: number;
    description: string;
    date: string;
    created_at: string;
    updated_at: string;
}

export interface Product {
    id: number;
    name: string;
    sku: string;
    description: string | null;
    price: number;
    cost: number;
    unit: string;
    category_id: number | null;
    category: Category | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Category {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
}

export interface Warehouse {
    id: number;
    name: string;
    code: string;
    address: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface StockMovement {
    id: number;
    product_id: number;
    product: Product;
    warehouse_id: number;
    warehouse: Warehouse;
    type: 'in' | 'out' | 'transfer';
    quantity: number;
    reference_type: string | null;
    reference_id: number | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Employee {
    id: number;
    user_id: number | null;
    user: User | null;
    employee_code: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    position: string | null;
    department_id: number | null;
    department: Department | null;
    hire_date: string;
    salary: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Department {
    id: number;
    name: string;
    code: string;
    description: string | null;
    manager_id: number | null;
    manager: Employee | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface LeaveRequest {
    id: number;
    employee_id: number;
    employee: Employee;
    type: 'annual' | 'sick' | 'personal' | 'maternity' | 'paternity' | 'other';
    start_date: string;
    end_date: string;
    reason: string;
    status: 'pending' | 'approved' | 'rejected';
    approved_by: number | null;
    approver: Employee | null;
    created_at: string;
    updated_at: string;
}

export interface Customer {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
    address: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Order {
    id: number;
    order_number: string;
    customer_id: number;
    customer: Customer;
    order_date: string;
    status: 'draft' | 'confirmed' | 'shipped' | 'delivered' | 'cancelled';
    total: number;
    notes: string | null;
    items: OrderItem[];
    created_at: string;
    updated_at: string;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: number;
    product: Product;
    quantity: number;
    unit_price: number;
    total: number;
    created_at: string;
    updated_at: string;
}

export interface Invoice {
    id: number;
    invoice_number: string;
    order_id: number | null;
    order: Order | null;
    customer_id: number;
    customer: Customer;
    invoice_date: string;
    due_date: string;
    status: 'draft' | 'sent' | 'paid' | 'overdue' | 'cancelled';
    total: number;
    paid_amount: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Vendor {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    company: string | null;
    address: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface PurchaseOrder {
    id: number;
    po_number: string;
    vendor_id: number;
    vendor: Vendor;
    order_date: string;
    expected_delivery: string | null;
    status: 'draft' | 'sent' | 'confirmed' | 'received' | 'cancelled';
    total: number;
    notes: string | null;
    items: POItem[];
    created_at: string;
    updated_at: string;
}

export interface POItem {
    id: number;
    purchase_order_id: number;
    product_id: number;
    product: Product;
    quantity: number;
    unit_price: number;
    total: number;
    received_quantity: number;
    created_at: string;
    updated_at: string;
}

export interface Bom {
    id: number;
    name: string;
    product_id: number;
    product: Product;
    quantity: number;
    type: 'manufacturing' | 'assembly';
    is_active: boolean;
    components: BomComponent[];
    created_at: string;
    updated_at: string;
}

export interface BomComponent {
    id: number;
    bom_id: number;
    product_id: number;
    product: Product;
    quantity: number;
    created_at: string;
    updated_at: string;
}

export interface WorkOrder {
    id: number;
    work_order_number: string;
    bom_id: number;
    bom: Bom;
    product_id: number;
    product: Product;
    quantity: number;
    produced_quantity: number;
    status: 'planned' | 'in_progress' | 'completed' | 'cancelled';
    scheduled_date: string | null;
    completed_date: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface Project {
    id: number;
    name: string;
    code: string;
    description: string | null;
    start_date: string;
    end_date: string | null;
    status: 'planning' | 'active' | 'on_hold' | 'completed' | 'cancelled';
    budget: number;
    manager_id: number | null;
    manager: Employee | null;
    created_at: string;
    updated_at: string;
}

export interface Task {
    id: number;
    title: string;
    description: string | null;
    project_id: number;
    project: Project;
    assigned_to: number | null;
    assignee: Employee | null;
    status: 'todo' | 'in_progress' | 'review' | 'done';
    priority: 'low' | 'medium' | 'high' | 'urgent';
    due_date: string | null;
    estimated_hours: number | null;
    actual_hours: number | null;
    created_at: string;
    updated_at: string;
}

export interface QualityCheck {
    id: number;
    check_number: string;
    product_id: number;
    product: Product;
    type: 'incoming' | 'in_process' | 'final';
    status: 'pending' | 'passed' | 'failed';
    inspector: string;
    notes: string | null;
    checked_at: string;
    created_at: string;
    updated_at: string;
}

export interface Asset {
    id: number;
    name: string;
    asset_code: string;
    category: string;
    purchase_date: string;
    purchase_cost: number;
    current_value: number;
    status: 'active' | 'maintenance' | 'retired';
    location: string | null;
    assigned_to: number | null;
    assignee: Employee | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface ServiceTicket {
    id: number;
    ticket_number: string;
    customer_id: number;
    customer: Customer;
    title: string;
    description: string;
    priority: 'low' | 'medium' | 'high' | 'urgent';
    status: 'open' | 'assigned' | 'in_progress' | 'resolved' | 'closed';
    assigned_to: number | null;
    assignee: Employee | null;
    resolved_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Course {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    instructor: string;
    duration_hours: number;
    difficulty: 'beginner' | 'intermediate' | 'advanced';
    is_published: boolean;
    created_at: string;
    updated_at: string;
}

export interface Payroll {
    id: number;
    employee_id: number;
    employee: Employee;
    period_start: string;
    period_end: string;
    basic_salary: number;
    allowances: number;
    deductions: number;
    net_salary: number;
    status: 'draft' | 'approved' | 'paid';
    paid_at: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface CrmPipeline {
    id: number;
    name: string;
    customer_id: number | null;
    customer: Customer | null;
    stage: 'lead' | 'prospect' | 'proposal' | 'negotiation' | 'closed_won' | 'closed_lost';
    value: number;
    probability: number;
    expected_close_date: string | null;
    assigned_to: number | null;
    assignee: Employee | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface PosTerminal {
    id: number;
    terminal_code: string;
    name: string;
    warehouse_id: number | null;
    warehouse: Warehouse | null;
    assigned_to: number | null;
    assignee: User | null;
    status: 'active' | 'inactive' | 'maintenance';
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface PosTransaction {
    id: number;
    transaction_number: string;
    terminal_id: number;
    terminal: PosTerminal;
    customer_id: number | null;
    customer: Customer | null;
    employee_id: number | null;
    employee: Employee | null;
    status: 'open' | 'completed' | 'voided' | 'refunded';
    subtotal: number;
    tax_amount: number;
    discount_amount: number;
    total_amount: number;
    currency: string;
    notes: string | null;
    completed_at: string | null;
    created_by: number;
    creator: User;
    items: PosTransactionItem[];
    payments: PosPayment[];
    created_at: string;
    updated_at: string;
}

export interface PosTransactionItem {
    id: number;
    transaction_id: number;
    product_id: number | null;
    product: Product | null;
    description: string;
    quantity: number;
    unit_price: number;
    discount_percent: number;
    tax_percent: number;
    line_total: number;
    created_at: string;
    updated_at: string;
}

export interface PosPayment {
    id: number;
    transaction_id: number;
    method: 'cash' | 'card' | 'bank_transfer' | 'digital_wallet' | 'other';
    amount: number;
    reference: string | null;
    notes: string | null;
    received_by: number | null;
    receiver: User | null;
    created_at: string;
    updated_at: string;
}

export interface PosSummary {
    completed_transactions: number;
    voided_transactions: number;
    total_revenue: number;
    total_tax: number;
    total_discounts: number;
    payment_breakdown: Array<{ method: string; count: number; total: number }>;
}

export interface FleetVehicle {
    id: number;
    vehicle_code: string;
    make: string;
    model: string;
    year: number;
    vin: string | null;
    license_plate: string;
    color: string | null;
    type: 'car' | 'truck' | 'van' | 'motorcycle' | 'bus' | 'trailer' | 'other';
    fuel_type: 'gasoline' | 'diesel' | 'electric' | 'hybrid' | 'other';
    current_odometer: number;
    fuel_capacity: number | null;
    fuel_level: number | null;
    status: 'active' | 'inactive' | 'maintenance' | 'retired';
    assigned_driver_id: number | null;
    assignedDriver: Employee | null;
    warehouse_id: number | null;
    warehouse: Warehouse | null;
    registration_expiry: string | null;
    insurance_expiry: string | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface FleetDriver {
    id: number;
    employee_id: number;
    employee: Employee;
    license_number: string;
    license_class: string | null;
    license_expiry: string;
    license_fee: number | null;
    certifications: string | null;
    status: 'active' | 'inactive' | 'suspended';
    created_at: string;
    updated_at: string;
}

export interface FleetTrip {
    id: number;
    trip_number: string;
    vehicle_id: number;
    vehicle: FleetVehicle;
    driver_id: number;
    driver: FleetDriver;
    start_location: string;
    end_location: string | null;
    start_odometer: number;
    end_odometer: number | null;
    distance: number | null;
    start_time: string;
    end_time: string | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    purpose: string | null;
    notes: string | null;
    created_by: number;
    creator: User;
    created_at: string;
    updated_at: string;
}

export interface FleetFuelLog {
    id: number;
    vehicle_id: number;
    vehicle: FleetVehicle;
    trip_id: number | null;
    trip: FleetTrip | null;
    date: string;
    quantity: number;
    unit_cost: number;
    total_cost: number;
    fuel_type: 'gasoline' | 'diesel' | 'electric' | 'hybrid' | 'other';
    odometer: number;
    station: string | null;
    receipt_number: string | null;
    logged_by: number | null;
    created_at: string;
    updated_at: string;
}

export interface FleetMaintenanceRecord {
    id: number;
    vehicle_id: number;
    vehicle: FleetVehicle;
    type: 'preventive' | 'corrective' | 'emergency' | 'inspection';
    title: string;
    description: string | null;
    scheduled_date: string | null;
    completed_date: string | null;
    cost: number | null;
    service_provider: string | null;
    odometer_at_service: number | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface FleetPartCategory {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    parent: FleetPartCategory | null;
    children: FleetPartCategory[];
    parts_count: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface FleetPart {
    id: number;
    part_number: string;
    name: string;
    description: string | null;
    category_id: number | null;
    category: FleetPartCategory | null;
    manufacturer: string | null;
    supplier: string | null;
    unit: string;
    unit_cost: number;
    sell_price: number | null;
    quantity_on_hand: number;
    reorder_level: number;
    reorder_quantity: number;
    bin_location: string | null;
    compatible_vehicles: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface FleetPartUsage {
    id: number;
    part_id: number;
    part: FleetPart;
    vehicle_id: number;
    vehicle: FleetVehicle;
    maintenance_id: number | null;
    maintenance: FleetMaintenanceRecord | null;
    trip_id: number | null;
    trip: FleetTrip | null;
    quantity: number;
    unit_cost: number;
    total_cost: number;
    used_date: string;
    used_by: number | null;
    user: User | null;
    notes: string | null;
    created_at: string;
    updated_at: string;
}

export interface FleetPartUsageSummary {
    total_cost: number;
    total_quantity: number;
    usage_count: number;
    top_parts: Array<{ part_id: number; total_qty: number; total_cost: number; part: FleetPart }>;
    cost_by_vehicle: Array<{ vehicle_id: number; total_cost: number; vehicle: FleetVehicle }>;
}

export interface FuelTrackingDashboard {
    summary: {
        total_cost: number;
        total_quantity: number;
        total_refuels: number;
        avg_cost_per_liter: number;
        period: { start: string; end: string };
    };
    monthly_trend: Array<{ month: string; cost: number; quantity: number; refuels: number }>;
    cost_by_fuel_type: Array<{ fuel_type: string; cost: number; quantity: number }>;
    top_stations: Array<{ station: string; visits: number; total_spent: number; avg_price: number }>;
    recent_logs: FleetFuelLog[];
}

export interface FuelEfficiencyRecord {
    date: string;
    odometer: number;
    distance_km: number;
    fuel_used: number;
    cost: number;
    km_per_liter: number;
    liters_per_100km: number;
    cost_per_km: number;
}

export interface FuelEfficiencyData {
    vehicle: FleetVehicle;
    efficiency_records: FuelEfficiencyRecord[];
    average_efficiency: {
        km_per_liter: number | null;
        liters_per_100km: number | null;
        cost_per_km: number | null;
    } | null;
    total_fuel_cost: number;
    total_fuel_quantity: number;
    total_distance_km: number;
}

export interface FuelConsumptionByVehicle {
    vehicle_id: number;
    vehicle: FleetVehicle;
    total_fuel: number;
    total_cost: number;
    refuel_count: number;
}

export interface FuelPriceHistory {
    fuel_type: string;
    overall: { avg_price: number; min_price: number; max_price: number } | null;
    daily_prices: Array<{ date: string; avg_price: number; min_price: number; max_price: number; samples: number }>;
}

export interface MaintenanceTrackingDashboard {
    summary: {
        overdue_count: number;
        upcoming_count: number;
        in_progress_count: number;
        total_spent_year: number;
    };
    overdue_records: FleetMaintenanceRecord[];
    upcoming_records: FleetMaintenanceRecord[];
    recent_completed: FleetMaintenanceRecord[];
    cost_by_type: Array<{ type: string; count: number; total_cost: number; avg_cost: number }>;
    cost_by_vehicle: Array<{ vehicle_id: number; count: number; total_cost: number; vehicle: FleetVehicle }>;
    monthly_cost: Array<{ month: string; cost: number; count: number }>;
}

export interface MaintenanceVehicleHistory {
    vehicle: FleetVehicle;
    records: FleetMaintenanceRecord[];
    total_cost: number;
    last_service_date: string | null;
    last_service_odometer: number | null;
    avg_interval_days: number | null;
    type_breakdown: Record<string, { count: number; total_cost: number }>;
}

export interface MaintenanceCostReport {
    summary: {
        total_cost: number;
        total_records: number;
        avg_cost_per_service: number;
        period: { start: string; end: string };
    };
    by_type: Array<{ type: string; count: number; total_cost: number; avg_cost: number }>;
    by_vehicle: Array<{ vehicle_id: number; count: number; total_cost: number; vehicle: FleetVehicle }>;
    by_provider: Array<{ service_provider: string; count: number; total_cost: number }>;
    by_month: Array<{ month: string; cost: number; count: number }>;
}

export interface SubscriptionPlan {
    id: number;
    code: string;
    name: string;
    description: string | null;
    price: number;
    currency: string;
    billing_interval: 'monthly' | 'quarterly' | 'annually';
    trial_days: number | null;
    max_users: number | null;
    included_usage: number | null;
    usage_overage_rate: number | null;
    features: string[] | null;
    is_active: boolean;
    sort_order: number;
    subscriptions_count?: number;
    created_at: string;
    updated_at: string;
}

export interface SubscriptionRecord {
    id: number;
    subscription_number: string;
    customer_id: number;
    customer: Customer;
    plan_id: number;
    plan: SubscriptionPlan;
    status: 'trialing' | 'active' | 'past_due' | 'cancelled' | 'suspended';
    trial_ends_at: string | null;
    current_period_start: string;
    current_period_end: string;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    quantity: number;
    discount_percent: number;
    notes: string | null;
    created_by: number;
    invoices?: SubscriptionInvoice[];
    usage_records?: SubscriptionUsageRecord[];
    plan_changes?: SubscriptionPlanChange[];
    created_at: string;
    updated_at: string;
}

export interface SubscriptionInvoice {
    id: number;
    invoice_number: string;
    subscription_id: number;
    amount: number;
    tax_amount: number;
    discount_amount: number;
    total_amount: number;
    status: 'draft' | 'sent' | 'paid' | 'overdue' | 'void';
    period_start: string;
    period_end: string;
    due_date: string;
    paid_at: string | null;
    payment_method: string | null;
    notes: string | null;
    created_at: string;
}

export interface SubscriptionUsageRecord {
    id: number;
    subscription_id: number;
    usage_type: string;
    quantity: number;
    unit_price: number;
    total_cost: number;
    recorded_at: string;
    period_start: string;
    period_end: string;
    notes: string | null;
    created_at: string;
}

export interface SubscriptionPlanChange {
    id: number;
    subscription_id: number;
    from_plan_id: number | null;
    from_plan: SubscriptionPlan | null;
    to_plan_id: number;
    to_plan: SubscriptionPlan;
    change_type: 'upgrade' | 'downgrade' | 'initial';
    effective_date: string;
    proration_amount: number;
    reason: string | null;
    created_at: string;
}

export interface SubscriptionDashboard {
    mrr: number;
    active_count: number;
    trialing_count: number;
    cancelled_recent: number;
    churn_rate_percent: number;
    plan_distribution: Array<{ plan_id: number; count: number; plan: SubscriptionPlan }>;
    recent_changes: SubscriptionRecord[];
    expiring_trials: SubscriptionRecord[];
}

export interface NotificationTemplateRecord {
    id: number;
    code: string;
    name: string;
    subject: string | null;
    body: string;
    html_body: string | null;
    default_channels: string[];
    variables: string[] | null;
    category: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface NotificationMessageRecord {
    id: number;
    user_id: number | null;
    template_id: number | null;
    template: NotificationTemplateRecord | null;
    channel: 'email' | 'in_app' | 'webhook';
    subject: string | null;
    body: string;
    data: Record<string, unknown> | null;
    status: 'pending' | 'sent' | 'failed';
    error_message: string | null;
    sent_at: string | null;
    read_at: string | null;
    created_at: string;
}

export interface NotificationPreferenceRecord {
    id: number;
    user_id: number;
    template_code: string | null;
    channels: string[];
    email_enabled: boolean;
    in_app_enabled: boolean;
    webhook_enabled: boolean;
    email_address: string | null;
    created_at: string;
}

export interface KpiData {
    revenue: { current: number; trend: number };
    orders: { current: number; trend: number };
    new_customers: number;
    pending_orders: number;
    fleet_costs: number;
    fleet_distance_km: number;
    trips_completed: number;
    mrr: number;
    active_subscriptions: number;
    active_vehicles: number;
    vehicles_in_maintenance: number;
    period: string;
}

export interface ChartData {
    revenue_trend: Array<{ month: string; value: number }>;
    orders_trend: Array<{ month: string; value: number }>;
    fleet_fuel_cost_trend: Array<{ month: string; value: number }>;
    fleet_maintenance_cost_trend: Array<{ month: string; value: number }>;
    subscription_mrr: Array<{ period: string; value: number }>;
    top_products: Array<{ name: string; total_quantity: number; total_revenue: number }>;
    fleet_cost_by_type: Array<{ type: string; value: number }>;
}

export interface ActivityItem {
    type: 'order' | 'trip' | 'maintenance' | 'notification';
    label: string;
    detail: string;
    created_at: string;
}

export interface ModuleSummary {
    finance: { total_assets: number; total_liabilities: number; total_revenue: number; total_expenses: number };
    inventory: { total_products: number; total_stock_value: number };
    sales: { total_customers: number; total_orders: number; pending_orders: number; total_revenue: number };
    fleet: { active_vehicles: number; total_vehicles: number; pending_maintenance: number };
    hr: { active_employees: number; pending_leave: number };
    subscription: { active_count: number; mrr: number };
}

export interface HRDashboard {
    summary: {
        total_employees: number;
        active_employees: number;
        new_this_month: number;
        terminated_this_month: number;
        turnover_rate: number;
    };
    attendance: {
        present_today: number;
        absent_today: number;
        late_today: number;
        attendance_rate: number;
    };
    leave: {
        pending_requests: number;
        on_leave_today: number;
    };
    department_breakdown: Array<{ id: number; name: string; employee_count: number }>;
    employment_type_breakdown: Array<{ employment_type: string; count: number }>;
    monthly_hires: Array<{ month: string; count: number }>;
    leave_by_type: Array<{ leave_type: string; count: number; total_days: number }>;
    recent_leave_requests: LeaveRequest[];
}

export interface HRAttendanceReport {
    overall_rate: number;
    total_days: number;
    daily_stats: Array<{ date: string; present: number; absent: number; late: number; total: number }>;
    by_employee: Array<{ employee_id: number; days: number; present_days: number; late_days: number; absent_days: number; employee: Employee }>;
}

export interface HRLeaveReport {
    by_status: Array<{ status: string; count: number; total_days: number }>;
    by_type: Array<{ leave_type: string; count: number; total_days: number }>;
    by_department: Array<{ department_name: string; count: number; total_days: number }>;
    monthly_trend: Array<{ month: string; count: number; days: number }>;
}

export interface HRPayrollReport {
    summary: { total_records: number; total_basic: number; total_allowances: number; total_deductions: number; total_net: number };
    by_status: Array<{ status: string; count: number; total_net: number }>;
    monthly_trend: Array<{ month: string; total: number; count: number }>;
    top_earners: Array<{ first_name: string; last_name: string; employee_code: string; net_salary: number }>;
}

export interface RecruitmentJob {
    id: number;
    job_code: string;
    title: string;
    description: string | null;
    requirements: string | null;
    department_id: number | null;
    department: Department | null;
    location: string | null;
    employment_type: string;
    salary_min: number | null;
    salary_max: number | null;
    currency: string;
    positions: number;
    status: 'draft' | 'open' | 'on_hold' | 'closed' | 'filled';
    posted_date: string | null;
    closing_date: string | null;
    applications_count?: number;
    applications?: RecruitmentApplication[];
    created_at: string;
    updated_at: string;
}

export interface RecruitmentApplication {
    id: number;
    application_number: string;
    job_id: number;
    job: RecruitmentJob;
    candidate_name: string;
    candidate_email: string;
    candidate_phone: string | null;
    resume_path: string | null;
    cover_letter: string | null;
    expected_salary: number | null;
    status: 'new' | 'screening' | 'interview' | 'offer' | 'hired' | 'rejected';
    rejection_reason: string | null;
    reviewed_by: number | null;
    reviewer: User | null;
    reviewed_at: string | null;
    interviews: RecruitmentInterview[];
    created_at: string;
    updated_at: string;
}

export interface RecruitmentInterview {
    id: number;
    application_id: number;
    interview_type: 'phone' | 'video' | 'onsite' | 'panel';
    scheduled_at: string;
    duration_minutes: number;
    location: string | null;
    interviewer_id: number | null;
    interviewer: Employee | null;
    status: 'scheduled' | 'completed' | 'cancelled' | 'no_show';
    score: number | null;
    feedback: string | null;
    notes: string | null;
    created_at: string;
}

export interface TrainingProgram {
    id: number;
    code: string;
    name: string;
    description: string | null;
    course_id: number | null;
    course: Course | null;
    type: 'onboarding' | 'compliance' | 'skill' | 'safety' | 'leadership' | 'other';
    duration_hours: number | null;
    cost: number | null;
    is_mandatory: boolean;
    is_active: boolean;
    sessions_count?: number;
    enrollments_count?: number;
    created_at: string;
}

export interface TrainingSession {
    id: number;
    program_id: number;
    program: TrainingProgram;
    title: string;
    description: string | null;
    starts_at: string;
    ends_at: string | null;
    location: string | null;
    instructor_id: number | null;
    instructor: Employee | null;
    max_participants: number | null;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
    enrollments?: TrainingEnrollment[];
    created_at: string;
}

export interface TrainingEnrollment {
    id: number;
    session_id: number;
    employee_id: number;
    employee: Employee;
    status: 'enrolled' | 'attended' | 'completed' | 'no_show';
    score: number | null;
    feedback: string | null;
    completed_at: string | null;
    created_at: string;
}

export interface TrainingCertification {
    id: number;
    employee_id: number;
    employee: Employee;
    program_id: number | null;
    program: TrainingProgram | null;
    certification_name: string;
    issuing_body: string | null;
    certificate_number: string | null;
    issued_date: string;
    expiry_date: string | null;
    status: 'active' | 'expired' | 'revoked';
    notes: string | null;
    created_at: string;
}