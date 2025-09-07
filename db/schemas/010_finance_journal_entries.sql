-- TPT Open ERP - Finance Journal Entries
-- Migration: 010
-- Description: Detailed line items for transactions (double-entry bookkeeping)

CREATE TABLE IF NOT EXISTS finance_journal_entries (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    transaction_id INTEGER NOT NULL REFERENCES finance_transactions(id) ON DELETE CASCADE,

    -- Account information
    account_id INTEGER NOT NULL REFERENCES finance_accounts(id),
    account_number VARCHAR(20) NOT NULL,
    account_name VARCHAR(200) NOT NULL,

    -- Entry amounts
    debit_amount DECIMAL(15,2) DEFAULT 0.00,
    credit_amount DECIMAL(15,2) DEFAULT 0.00,

    -- Entry details
    line_number INTEGER NOT NULL,
    description TEXT,
    reference VARCHAR(100),

    -- Dimensions for analysis
    department_id INTEGER,
    project_id INTEGER,
    cost_center_id INTEGER,
    location_id INTEGER,

    -- Tax information
    tax_code VARCHAR(20),
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_rate DECIMAL(5,2) DEFAULT 0.00,

    -- Additional metadata
    metadata JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT finance_journal_entries_amounts CHECK (
        (debit_amount > 0 AND credit_amount = 0) OR
        (credit_amount > 0 AND debit_amount = 0)
    ),
    CONSTRAINT finance_journal_entries_not_both_zero CHECK (
        NOT (debit_amount = 0 AND credit_amount = 0)
    )
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_transaction ON finance_journal_entries(transaction_id);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_account ON finance_journal_entries(account_id);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_account_number ON finance_journal_entries(account_number);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_line_number ON finance_journal_entries(transaction_id, line_number);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_department ON finance_journal_entries(department_id);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_project ON finance_journal_entries(project_id);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_cost_center ON finance_journal_entries(cost_center_id);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_transaction_account ON finance_journal_entries(transaction_id, account_id);
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_account_date ON finance_journal_entries(account_id, created_at);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_debits ON finance_journal_entries(account_id, debit_amount) WHERE debit_amount > 0;
CREATE INDEX IF NOT EXISTS idx_finance_journal_entries_credits ON finance_journal_entries(account_id, credit_amount) WHERE credit_amount > 0;

-- Triggers for updated_at
CREATE TRIGGER update_finance_journal_entries_updated_at BEFORE UPDATE ON finance_journal_entries
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to validate journal entry balance
CREATE OR REPLACE FUNCTION validate_journal_entry_balance(p_transaction_id INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    total_debit DECIMAL(15,2);
    total_credit DECIMAL(15,2);
BEGIN
    SELECT
        COALESCE(SUM(debit_amount), 0),
        COALESCE(SUM(credit_amount), 0)
    INTO total_debit, total_credit
    FROM finance_journal_entries
    WHERE transaction_id = p_transaction_id;

    -- Update transaction totals
    UPDATE finance_transactions
    SET total_debit = total_debit,
        total_credit = total_credit,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_transaction_id;

    -- Return whether the entry is balanced
    RETURN total_debit = total_credit;
END;
$$ LANGUAGE plpgsql;

-- Function to get account balance
CREATE OR REPLACE FUNCTION get_account_balance(
    p_account_id INTEGER,
    p_start_date DATE DEFAULT NULL,
    p_end_date DATE DEFAULT NULL
)
RETURNS DECIMAL(15,2) AS $$
DECLARE
    balance DECIMAL(15,2) := 0.00;
BEGIN
    -- Get opening balance
    SELECT opening_balance INTO balance
    FROM finance_accounts
    WHERE id = p_account_id;

    -- Add journal entry amounts within date range
    SELECT balance + COALESCE(SUM(
        CASE
            WHEN fa.normal_balance = 'debit' THEN je.debit_amount - je.credit_amount
            ELSE je.credit_amount - je.debit_amount
        END
    ), 0)
    INTO balance
    FROM finance_journal_entries je
    JOIN finance_accounts fa ON je.account_id = fa.id
    JOIN finance_transactions ft ON je.transaction_id = ft.id
    WHERE je.account_id = p_account_id
      AND ft.is_posted = true
      AND (p_start_date IS NULL OR ft.posting_date >= p_start_date)
      AND (p_end_date IS NULL OR ft.posting_date <= p_end_date);

    RETURN balance;
END;
$$ LANGUAGE plpgsql;

-- Function to create journal entry
CREATE OR REPLACE FUNCTION create_journal_entry(
    p_transaction_id INTEGER,
    p_account_id INTEGER,
    p_debit_amount DECIMAL DEFAULT 0,
    p_credit_amount DECIMAL DEFAULT 0,
    p_description TEXT DEFAULT NULL,
    p_line_number INTEGER DEFAULT NULL,
    p_created_by INTEGER DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    new_entry_id INTEGER;
    account_record RECORD;
    line_num INTEGER;
BEGIN
    -- Get account details
    SELECT account_number, account_name INTO account_record
    FROM finance_accounts
    WHERE id = p_account_id;

    -- Get next line number if not provided
    IF p_line_number IS NULL THEN
        SELECT COALESCE(MAX(line_number), 0) + 1 INTO line_num
        FROM finance_journal_entries
        WHERE transaction_id = p_transaction_id;
    ELSE
        line_num := p_line_number;
    END IF;

    -- Insert journal entry
    INSERT INTO finance_journal_entries (
        transaction_id, account_id, account_number, account_name,
        debit_amount, credit_amount, line_number, description,
        created_by
    ) VALUES (
        p_transaction_id, p_account_id, account_record.account_number, account_record.account_name,
        p_debit_amount, p_credit_amount, line_num, p_description,
        p_created_by
    ) RETURNING id INTO new_entry_id;

    -- Validate balance
    PERFORM validate_journal_entry_balance(p_transaction_id);

    RETURN new_entry_id;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE finance_journal_entries IS 'Detailed line items for financial transactions (double-entry bookkeeping)';
COMMENT ON COLUMN finance_journal_entries.debit_amount IS 'Amount debited to this account';
COMMENT ON COLUMN finance_journal_entries.credit_amount IS 'Amount credited to this account';
COMMENT ON COLUMN finance_journal_entries.line_number IS 'Order of entry within the transaction';
COMMENT ON COLUMN finance_journal_entries.metadata IS 'Additional data for analysis and reporting';
