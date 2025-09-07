-- TPT Open ERP - Finance/Accounting Module
-- Migration: 008
-- Description: Chart of accounts and account management

CREATE TABLE IF NOT EXISTS finance_accounts (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    account_number VARCHAR(20) NOT NULL UNIQUE,
    account_name VARCHAR(200) NOT NULL,
    account_type VARCHAR(50) NOT NULL, -- Asset, Liability, Equity, Revenue, Expense
    account_subtype VARCHAR(50), -- Current Asset, Fixed Asset, etc.
    parent_account_id INTEGER REFERENCES finance_accounts(id),

    -- Account properties
    is_active BOOLEAN DEFAULT true,
    is_system_account BOOLEAN DEFAULT false,
    allow_manual_entry BOOLEAN DEFAULT true,
    requires_approval BOOLEAN DEFAULT false,

    -- Financial attributes
    normal_balance VARCHAR(10) DEFAULT 'debit', -- debit, credit
    currency_code VARCHAR(3) DEFAULT 'USD',
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    current_balance DECIMAL(15,2) DEFAULT 0.00,

    -- Tax information
    tax_code VARCHAR(20),
    tax_rate DECIMAL(5,2) DEFAULT 0.00,
    is_taxable BOOLEAN DEFAULT true,

    -- Budgeting
    budget_amount DECIMAL(15,2) DEFAULT 0.00,
    budget_period VARCHAR(20) DEFAULT 'monthly', -- monthly, quarterly, yearly

    -- Department/Branch
    department_id INTEGER,
    branch_id INTEGER,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT finance_accounts_account_type CHECK (account_type IN ('Asset', 'Liability', 'Equity', 'Revenue', 'Expense')),
    CONSTRAINT finance_accounts_normal_balance CHECK (normal_balance IN ('debit', 'credit')),
    CONSTRAINT finance_accounts_budget_period CHECK (budget_period IN ('monthly', 'quarterly', 'yearly')),
    CONSTRAINT finance_accounts_no_self_parent CHECK (id != parent_account_id)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_finance_accounts_number ON finance_accounts(account_number);
CREATE INDEX IF NOT EXISTS idx_finance_accounts_type ON finance_accounts(account_type);
CREATE INDEX IF NOT EXISTS idx_finance_accounts_parent ON finance_accounts(parent_account_id);
CREATE INDEX IF NOT EXISTS idx_finance_accounts_active ON finance_accounts(is_active);
CREATE INDEX IF NOT EXISTS idx_finance_accounts_department ON finance_accounts(department_id);
CREATE INDEX IF NOT EXISTS idx_finance_accounts_branch ON finance_accounts(branch_id);

-- Triggers for updated_at
CREATE TRIGGER update_finance_accounts_updated_at BEFORE UPDATE ON finance_accounts
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert default chart of accounts
INSERT INTO finance_accounts (
    account_number, account_name, account_type, account_subtype,
    normal_balance, is_system_account, created_by
) VALUES
-- Assets
('1000', 'Cash and Cash Equivalents', 'Asset', 'Current Asset', 'debit', true, 1),
('1100', 'Accounts Receivable', 'Asset', 'Current Asset', 'debit', true, 1),
('1200', 'Inventory', 'Asset', 'Current Asset', 'debit', true, 1),
('1300', 'Prepaid Expenses', 'Asset', 'Current Asset', 'debit', true, 1),
('1400', 'Property, Plant and Equipment', 'Asset', 'Fixed Asset', 'debit', true, 1),
('1500', 'Accumulated Depreciation', 'Asset', 'Fixed Asset', 'credit', true, 1),

-- Liabilities
('2000', 'Accounts Payable', 'Liability', 'Current Liability', 'credit', true, 1),
('2100', 'Accrued Expenses', 'Liability', 'Current Liability', 'credit', true, 1),
('2200', 'Loans Payable', 'Liability', 'Current Liability', 'credit', true, 1),
('2300', 'Long-term Debt', 'Liability', 'Long-term Liability', 'credit', true, 1),

-- Equity
('3000', 'Common Stock', 'Equity', 'Stock', 'credit', true, 1),
('3100', 'Retained Earnings', 'Equity', 'Retained Earnings', 'credit', true, 1),
('3200', 'Dividends Paid', 'Equity', 'Dividends', 'debit', true, 1),

-- Revenue
('4000', 'Sales Revenue', 'Revenue', 'Operating Revenue', 'credit', true, 1),
('4100', 'Service Revenue', 'Revenue', 'Operating Revenue', 'credit', true, 1),
('4200', 'Interest Income', 'Revenue', 'Other Income', 'credit', true, 1),

-- Expenses
('5000', 'Cost of Goods Sold', 'Expense', 'Cost of Sales', 'debit', true, 1),
('5100', 'Salaries and Wages', 'Expense', 'Operating Expense', 'debit', true, 1),
('5200', 'Rent Expense', 'Expense', 'Operating Expense', 'debit', true, 1),
('5300', 'Utilities Expense', 'Expense', 'Operating Expense', 'debit', true, 1),
('5400', 'Marketing Expense', 'Expense', 'Operating Expense', 'debit', true, 1),
('5500', 'Depreciation Expense', 'Expense', 'Operating Expense', 'debit', true, 1)
ON CONFLICT (account_number) DO NOTHING;

-- Comments
COMMENT ON TABLE finance_accounts IS 'Chart of accounts for financial management';
COMMENT ON COLUMN finance_accounts.account_number IS 'Unique account identifier';
COMMENT ON COLUMN finance_accounts.account_type IS 'Asset, Liability, Equity, Revenue, or Expense';
COMMENT ON COLUMN finance_accounts.normal_balance IS 'Whether account normally has debit or credit balance';
COMMENT ON COLUMN finance_accounts.opening_balance IS 'Balance at start of fiscal year';
COMMENT ON COLUMN finance_accounts.current_balance IS 'Current account balance';
