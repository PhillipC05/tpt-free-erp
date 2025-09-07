-- TPT Open ERP - Finance Transactions
-- Migration: 009
-- Description: General ledger transactions and journal entries

CREATE TABLE IF NOT EXISTS finance_transactions (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    transaction_date DATE NOT NULL,
    posting_date DATE NOT NULL,

    -- Transaction details
    transaction_type VARCHAR(50) NOT NULL, -- Journal Entry, Invoice, Payment, etc.
    reference_number VARCHAR(100),
    description TEXT NOT NULL,

    -- Financial amounts
    total_debit DECIMAL(15,2) DEFAULT 0.00,
    total_credit DECIMAL(15,2) DEFAULT 0.00,
    currency_code VARCHAR(3) DEFAULT 'USD',
    exchange_rate DECIMAL(10,6) DEFAULT 1.000000,

    -- Status and workflow
    status VARCHAR(20) DEFAULT 'draft', -- draft, pending, posted, voided
    is_posted BOOLEAN DEFAULT false,
    posted_at TIMESTAMP NULL,
    posted_by INTEGER REFERENCES users(id),

    -- Approval workflow
    requires_approval BOOLEAN DEFAULT false,
    approved_by INTEGER REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    approval_notes TEXT,

    -- Source information
    source_module VARCHAR(50), -- finance, sales, inventory, etc.
    source_id INTEGER, -- ID from source module
    source_type VARCHAR(50), -- invoice, payment, adjustment, etc.

    -- Period and fiscal year
    fiscal_year INTEGER,
    accounting_period INTEGER,
    period_name VARCHAR(20),

    -- Reversals and adjustments
    is_reversal BOOLEAN DEFAULT false,
    reversal_of INTEGER REFERENCES finance_transactions(id),
    reversal_date DATE NULL,
    reversal_reason TEXT,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT finance_transactions_status CHECK (status IN ('draft', 'pending', 'posted', 'voided', 'reversed')),
    CONSTRAINT finance_transactions_balanced CHECK (total_debit = total_credit),
    CONSTRAINT finance_transactions_dates CHECK (posting_date >= transaction_date),
    CONSTRAINT finance_transactions_no_self_reversal CHECK (id != reversal_of)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_finance_transactions_number ON finance_transactions(transaction_number);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_date ON finance_transactions(transaction_date);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_posting_date ON finance_transactions(posting_date);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_type ON finance_transactions(transaction_type);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_status ON finance_transactions(status);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_posted ON finance_transactions(is_posted);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_source ON finance_transactions(source_module, source_id);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_fiscal_year ON finance_transactions(fiscal_year);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_period ON finance_transactions(accounting_period);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_finance_transactions_date_status ON finance_transactions(transaction_date, status);
CREATE INDEX IF NOT EXISTS idx_finance_transactions_fiscal_period ON finance_transactions(fiscal_year, accounting_period);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_finance_transactions_unposted ON finance_transactions(is_posted) WHERE is_posted = false;
CREATE INDEX IF NOT EXISTS idx_finance_transactions_pending_approval ON finance_transactions(status, requires_approval) WHERE status = 'pending' AND requires_approval = true;

-- Triggers for updated_at
CREATE TRIGGER update_finance_transactions_updated_at BEFORE UPDATE ON finance_transactions
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate transaction number
CREATE OR REPLACE FUNCTION generate_transaction_number()
RETURNS VARCHAR(50) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    transaction_num VARCHAR(50);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(transaction_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM finance_transactions
    WHERE transaction_number LIKE 'TXN-' || current_year || '-%';

    transaction_num := 'TXN-' || current_year || '-' || LPAD(sequence_number::TEXT, 6, '0');

    RETURN transaction_num;
END;
$$ LANGUAGE plpgsql;

-- Function to post transaction (update account balances)
CREATE OR REPLACE FUNCTION post_transaction(p_transaction_id INTEGER)
RETURNS BOOLEAN AS $$
DECLARE
    trans_record RECORD;
    entry_record RECORD;
BEGIN
    -- Get transaction details
    SELECT * INTO trans_record
    FROM finance_transactions
    WHERE id = p_transaction_id AND status = 'pending';

    IF NOT FOUND THEN
        RETURN false;
    END IF;

    -- Update transaction status
    UPDATE finance_transactions
    SET status = 'posted',
        is_posted = true,
        posted_at = CURRENT_TIMESTAMP,
        posted_by = trans_record.approved_by
    WHERE id = p_transaction_id;

    -- Update account balances for each journal entry
    FOR entry_record IN
        SELECT je.account_id, je.debit_amount, je.credit_amount
        FROM finance_journal_entries je
        WHERE je.transaction_id = p_transaction_id
    LOOP
        UPDATE finance_accounts
        SET current_balance = current_balance + entry_record.debit_amount - entry_record.credit_amount,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = entry_record.account_id;
    END LOOP;

    RETURN true;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE finance_transactions IS 'General ledger transactions and journal entries';
COMMENT ON COLUMN finance_transactions.transaction_number IS 'Unique transaction identifier';
COMMENT ON COLUMN finance_transactions.total_debit IS 'Sum of all debit entries';
COMMENT ON COLUMN finance_transactions.total_credit IS 'Sum of all credit entries';
COMMENT ON COLUMN finance_transactions.is_posted IS 'Whether transaction has been posted to general ledger';
COMMENT ON COLUMN finance_transactions.source_module IS 'Module that created this transaction';
COMMENT ON COLUMN finance_transactions.fiscal_year IS 'Fiscal year for reporting purposes';
