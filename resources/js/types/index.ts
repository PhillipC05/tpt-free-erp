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