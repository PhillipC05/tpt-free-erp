-- TPT Open ERP - HR Employees
-- Migration: 016
-- Description: Employee management and HR data

CREATE TABLE IF NOT EXISTS hr_employees (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    employee_number VARCHAR(20) NOT NULL UNIQUE,
    user_id INTEGER REFERENCES users(id), -- Link to user account if exists

    -- Personal Information
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    preferred_name VARCHAR(100),
    display_name VARCHAR(200),

    -- Contact Information
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    mobile VARCHAR(20),
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),

    -- Address Information
    address JSONB DEFAULT '{}',
    mailing_address JSONB DEFAULT '{}',
    same_address BOOLEAN DEFAULT true,

    -- Employment Details
    hire_date DATE NOT NULL,
    termination_date DATE NULL,
    employment_status VARCHAR(20) DEFAULT 'active', -- active, terminated, on_leave, suspended
    employee_type VARCHAR(20) DEFAULT 'full_time', -- full_time, part_time, contract, intern

    -- Job Information
    job_title VARCHAR(100),
    department_id INTEGER,
    manager_id INTEGER REFERENCES hr_employees(id),
    reports_to INTEGER REFERENCES hr_employees(id), -- Same as manager_id, for clarity

    -- Compensation
    salary DECIMAL(15,2),
    hourly_rate DECIMAL(8,2),
    pay_frequency VARCHAR(20) DEFAULT 'monthly', -- monthly, biweekly, weekly, hourly
    currency_code VARCHAR(3) DEFAULT 'USD',
    pay_grade VARCHAR(20),

    -- Work Schedule
    work_schedule JSONB DEFAULT '{}', -- working hours, days, etc.
    standard_hours_per_week DECIMAL(5,2) DEFAULT 40.00,

    -- Benefits and Insurance
    benefits_package VARCHAR(100),
    health_insurance_provider VARCHAR(100),
    health_insurance_id VARCHAR(50),
    dental_insurance BOOLEAN DEFAULT false,
    vision_insurance BOOLEAN DEFAULT false,

    -- Government and Tax Information
    ssn VARCHAR(20), -- Social Security Number or equivalent
    tax_id VARCHAR(50),
    work_permit_status VARCHAR(50),
    work_permit_expiry DATE,

    -- Education and Skills
    education_level VARCHAR(50),
    certifications TEXT[],
    skills JSONB DEFAULT '[]',
    languages JSONB DEFAULT '[]',

    -- Performance and Development
    performance_rating DECIMAL(3,1), -- 1.0 to 5.0 scale
    last_review_date DATE,
    next_review_date DATE,
    development_plan TEXT,

    -- Attendance and Time Tracking
    vacation_days_accrued DECIMAL(8,2) DEFAULT 0.00,
    vacation_days_used DECIMAL(8,2) DEFAULT 0.00,
    sick_days_accrued DECIMAL(8,2) DEFAULT 0.00,
    sick_days_used DECIMAL(8,2) DEFAULT 0.00,

    -- Equipment and Assets
    company_assets JSONB DEFAULT '[]', -- laptops, phones, etc.
    office_location VARCHAR(100),
    cubicle_number VARCHAR(20),

    -- Security and Access
    security_clearance_level VARCHAR(20) DEFAULT 'standard',
    access_card_number VARCHAR(50),
    system_access_level VARCHAR(20) DEFAULT 'employee',

    -- Additional Information
    notes TEXT,
    custom_fields JSONB DEFAULT '{}',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT hr_employees_email_format CHECK (email IS NULL OR email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$'),
    CONSTRAINT hr_employees_status CHECK (employment_status IN ('active', 'terminated', 'on_leave', 'suspended', 'retired')),
    CONSTRAINT hr_employees_type CHECK (employee_type IN ('full_time', 'part_time', 'contract', 'intern', 'consultant')),
    CONSTRAINT hr_employees_pay_frequency CHECK (pay_frequency IN ('monthly', 'biweekly', 'weekly', 'hourly')),
    CONSTRAINT hr_employees_performance_rating CHECK (performance_rating IS NULL OR (performance_rating >= 1.0 AND performance_rating <= 5.0)),
    CONSTRAINT hr_employees_salary_positive CHECK (salary IS NULL OR salary >= 0),
    CONSTRAINT hr_employees_hourly_rate_positive CHECK (hourly_rate IS NULL OR hourly_rate >= 0),
    CONSTRAINT hr_employees_hire_date_past CHECK (hire_date <= CURRENT_DATE),
    CONSTRAINT hr_employees_termination_after_hire CHECK (termination_date IS NULL OR termination_date >= hire_date)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_hr_employees_number ON hr_employees(employee_number);
CREATE INDEX IF NOT EXISTS idx_hr_employees_user_id ON hr_employees(user_id);
CREATE INDEX IF NOT EXISTS idx_hr_employees_email ON hr_employees(email);
CREATE INDEX IF NOT EXISTS idx_hr_employees_department ON hr_employees(department_id);
CREATE INDEX IF NOT EXISTS idx_hr_employees_manager ON hr_employees(manager_id);
CREATE INDEX IF NOT EXISTS idx_hr_employees_status ON hr_employees(employment_status);
CREATE INDEX IF NOT EXISTS idx_hr_employees_type ON hr_employees(employee_type);
CREATE INDEX IF NOT EXISTS idx_hr_employees_hire_date ON hr_employees(hire_date);
CREATE INDEX IF NOT EXISTS idx_hr_employees_performance ON hr_employees(performance_rating);

-- Composite indexes
CREATE INDEX IF NOT EXISTS idx_hr_employees_dept_status ON hr_employees(department_id, employment_status);
CREATE INDEX IF NOT EXISTS idx_hr_employees_manager_status ON hr_employees(manager_id, employment_status);

-- Partial indexes
CREATE INDEX IF NOT EXISTS idx_hr_employees_active ON hr_employees(id, display_name)
    WHERE employment_status = 'active';
CREATE INDEX IF NOT EXISTS idx_hr_employees_on_leave ON hr_employees(id, hire_date)
    WHERE employment_status = 'on_leave';
CREATE INDEX IF NOT EXISTS idx_hr_employees_high_performers ON hr_employees(id, performance_rating)
    WHERE performance_rating >= 4.0 AND employment_status = 'active';

-- Triggers for updated_at
CREATE TRIGGER update_hr_employees_updated_at BEFORE UPDATE ON hr_employees
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Function to generate employee number
CREATE OR REPLACE FUNCTION generate_employee_number()
RETURNS VARCHAR(20) AS $$
DECLARE
    current_year INTEGER;
    sequence_number INTEGER;
    employee_num VARCHAR(20);
BEGIN
    current_year := EXTRACT(YEAR FROM CURRENT_DATE);

    -- Get next sequence number for the year
    SELECT COALESCE(MAX(CAST(SUBSTRING(employee_number FROM '[0-9]+$') AS INTEGER)), 0) + 1
    INTO sequence_number
    FROM hr_employees
    WHERE employee_number LIKE 'EMP-' || current_year || '-%';

    employee_num := 'EMP-' || current_year || '-' || LPAD(sequence_number::TEXT, 4, '0');

    RETURN employee_num;
END;
$$ LANGUAGE plpgsql;

-- Function to get employee hierarchy
CREATE OR REPLACE FUNCTION get_employee_hierarchy(p_employee_id INTEGER)
RETURNS TABLE (
    employee_id INTEGER,
    employee_name VARCHAR(200),
    job_title VARCHAR(100),
    level INTEGER,
    manager_id INTEGER,
    manager_name VARCHAR(200)
) AS $$
WITH RECURSIVE employee_tree AS (
    -- Base case: the specified employee
    SELECT
        id,
        display_name,
        job_title,
        0 as level,
        manager_id,
        (SELECT display_name FROM hr_employees WHERE id = e.manager_id) as manager_name
    FROM hr_employees e
    WHERE id = p_employee_id

    UNION ALL

    -- Recursive case: direct reports
    SELECT
        e.id,
        e.display_name,
        e.job_title,
        et.level + 1,
        e.manager_id,
        et.employee_name
    FROM hr_employees e
    JOIN employee_tree et ON e.manager_id = et.employee_id
)
SELECT * FROM employee_tree ORDER BY level, employee_name;
$$ LANGUAGE plpgsql;

-- Function to calculate employee tenure
CREATE OR REPLACE FUNCTION calculate_employee_tenure(p_employee_id INTEGER)
RETURNS INTERVAL AS $$
DECLARE
    hire_date DATE;
    termination_date DATE;
BEGIN
    SELECT e.hire_date, e.termination_date
    INTO hire_date, termination_date
    FROM hr_employees e
    WHERE e.id = p_employee_id;

    IF termination_date IS NOT NULL THEN
        RETURN termination_date - hire_date;
    ELSE
        RETURN CURRENT_DATE - hire_date;
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Function to get department headcount
CREATE OR REPLACE FUNCTION get_department_headcount(p_department_id INTEGER DEFAULT NULL)
RETURNS TABLE (
    department_id INTEGER,
    department_name VARCHAR(100),
    total_employees INTEGER,
    active_employees INTEGER,
    on_leave_employees INTEGER,
    terminated_employees INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT
        d.id,
        d.name,
        COUNT(e.id)::INTEGER as total_employees,
        COUNT(CASE WHEN e.employment_status = 'active' THEN 1 END)::INTEGER as active_employees,
        COUNT(CASE WHEN e.employment_status = 'on_leave' THEN 1 END)::INTEGER as on_leave_employees,
        COUNT(CASE WHEN e.employment_status = 'terminated' THEN 1 END)::INTEGER as terminated_employees
    FROM hr_departments d
    LEFT JOIN hr_employees e ON d.id = e.department_id
    WHERE (p_department_id IS NULL OR d.id = p_department_id)
      AND d.deleted_at IS NULL
    GROUP BY d.id, d.name
    ORDER BY d.name;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON TABLE hr_employees IS 'Employee master data with comprehensive HR information';
COMMENT ON COLUMN hr_employees.employee_number IS 'Unique employee identifier';
COMMENT ON COLUMN hr_employees.user_id IS 'Link to system user account if employee has login access';
COMMENT ON COLUMN hr_employees.employment_status IS 'Current employment status';
COMMENT ON COLUMN hr_employees.employee_type IS 'Type of employment relationship';
COMMENT ON COLUMN hr_employees.work_schedule IS 'JSON object defining work hours and patterns';
COMMENT ON COLUMN hr_employees.benefits_package IS 'Reference to benefits package assigned to employee';
COMMENT ON COLUMN hr_employees.performance_rating IS 'Employee performance rating on 1-5 scale';
COMMENT ON COLUMN hr_employees.company_assets IS 'JSON array of company assets assigned to employee';
COMMENT ON COLUMN hr_employees.security_clearance_level IS 'Employee security clearance level';
