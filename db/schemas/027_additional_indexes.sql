-- TPT Open ERP - Additional Performance Indexes
-- Migration: 027
-- Description: Additional indexes for query optimization

-- Composite indexes for common query patterns

-- Tasks: project and status combination
CREATE INDEX IF NOT EXISTS idx_tasks_project_status ON tasks(project_id, status);
CREATE INDEX IF NOT EXISTS idx_tasks_assigned_due ON tasks(assigned_to, due_date);
CREATE INDEX IF NOT EXISTS idx_tasks_parent_status ON tasks(parent_task_id, status);

-- Time entries: user and date range
CREATE INDEX IF NOT EXISTS idx_time_entries_user_date_range ON time_entries(user_id, start_time, end_time);
CREATE INDEX IF NOT EXISTS idx_time_entries_project_date ON time_entries(project_id, start_time);
CREATE INDEX IF NOT EXISTS idx_time_entries_billable ON time_entries(billable, start_time);

-- Projects: manager and status
CREATE INDEX IF NOT EXISTS idx_projects_manager_status ON projects(manager_id, status);
CREATE INDEX IF NOT EXISTS idx_projects_client_status ON projects(client_id, status);
CREATE INDEX IF NOT EXISTS idx_projects_dates_range ON projects(start_date, end_date);

-- Sales orders: customer and status
CREATE INDEX IF NOT EXISTS idx_sales_orders_customer_status ON sales_orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_sales_orders_date_status ON sales_orders(order_date, status);

-- Finance transactions: account and date
CREATE INDEX IF NOT EXISTS idx_finance_transactions_account_date ON finance_transactions(account_id, transaction_date);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_type_date ON finance_transactions(transaction_type, transaction_date);

-- Inventory movements: product and date
CREATE INDEX IF NOT EXISTS idx_inventory_movements_product_date ON inventory_stock_movements(product_id, created_at);
CREATE INDEX IF NOT EXISTS idx_inventory_movements_type_date ON inventory_stock_movements(movement_type, created_at);

-- Audit log: table and timestamp
CREATE INDEX IF NOT EXISTS idx_audit_log_table_timestamp ON audit_log(table_name, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_log_user_timestamp ON audit_log(user_id, created_at);

-- Users: active and verified
CREATE INDEX IF NOT EXISTS idx_users_active_verified_created ON users(is_active, is_verified, created_at);
CREATE INDEX IF NOT EXISTS idx_users_last_login ON users(last_login_at);

-- Partial indexes for specific queries
CREATE INDEX IF NOT EXISTS idx_tasks_overdue ON tasks(due_date) WHERE due_date < CURRENT_DATE AND status != 'completed';
CREATE INDEX IF NOT EXISTS idx_projects_over_budget ON projects(id) WHERE actual_cost > budget;
CREATE INDEX IF NOT EXISTS idx_time_entries_unbilled ON time_entries(id) WHERE billable = true AND end_time IS NOT NULL;

-- Comments
COMMENT ON INDEX idx_tasks_project_status IS 'Optimizes queries filtering tasks by project and status';
COMMENT ON INDEX idx_time_entries_user_date_range IS 'Optimizes time tracking reports by user and date range';
COMMENT ON INDEX idx_projects_manager_status IS 'Optimizes project management dashboards';
COMMENT ON INDEX idx_tasks_overdue IS 'Quickly find overdue tasks';
