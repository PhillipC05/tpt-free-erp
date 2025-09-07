-- TPT Open ERP - Stored Procedures and Functions
-- Migration: 026
-- Description: Database functions and stored procedures for complex operations

-- Function to calculate project progress based on completed tasks
CREATE OR REPLACE FUNCTION calculate_project_progress(project_uuid UUID)
RETURNS INTEGER AS $$
DECLARE
    total_tasks INTEGER;
    completed_tasks INTEGER;
    progress_percentage INTEGER;
BEGIN
    SELECT COUNT(*) INTO total_tasks FROM tasks WHERE project_id = (SELECT id FROM projects WHERE uuid = project_uuid);
    SELECT COUNT(*) INTO completed_tasks FROM tasks WHERE project_id = (SELECT id FROM projects WHERE uuid = project_uuid) AND status = 'completed';

    IF total_tasks = 0 THEN
        progress_percentage := 0;
    ELSE
        progress_percentage := (completed_tasks * 100) / total_tasks;
    END IF;

    RETURN progress_percentage;
END;
$$ LANGUAGE plpgsql;

-- Function to update project progress
CREATE OR REPLACE FUNCTION update_project_progress()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE projects SET progress_percentage = calculate_project_progress(uuid) WHERE id = NEW.project_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger to automatically update project progress when task status changes
CREATE TRIGGER update_project_progress_trigger
    AFTER INSERT OR UPDATE OF status ON tasks
    FOR EACH ROW
    EXECUTE FUNCTION update_project_progress();

-- Function to calculate total time spent on a project
CREATE OR REPLACE FUNCTION calculate_project_total_time(project_uuid UUID)
RETURNS DECIMAL(10,2) AS $$
DECLARE
    total_hours DECIMAL(10,2);
BEGIN
    SELECT COALESCE(SUM(duration_minutes) / 60.0, 0) INTO total_hours
    FROM time_entries
    WHERE project_id = (SELECT id FROM projects WHERE uuid = project_uuid);

    RETURN total_hours;
END;
$$ LANGUAGE plpgsql;

-- Function to get user's total billable hours for a month
CREATE OR REPLACE FUNCTION get_user_billable_hours(user_uuid UUID, month_date DATE)
RETURNS DECIMAL(10,2) AS $$
DECLARE
    total_hours DECIMAL(10,2);
BEGIN
    SELECT COALESCE(SUM(duration_minutes) / 60.0, 0) INTO total_hours
    FROM time_entries
    WHERE user_id = (SELECT id FROM users WHERE uuid = user_uuid)
    AND billable = true
    AND DATE_TRUNC('month', start_time) = DATE_TRUNC('month', month_date);

    RETURN total_hours;
END;
$$ LANGUAGE plpgsql;

-- Function to calculate inventory turnover ratio
CREATE OR REPLACE FUNCTION calculate_inventory_turnover(product_uuid UUID, period_months INTEGER DEFAULT 12)
RETURNS DECIMAL(10,2) AS $$
DECLARE
    total_sold DECIMAL(10,2);
    average_inventory DECIMAL(10,2);
    turnover_ratio DECIMAL(10,2);
BEGIN
    -- Calculate total sold in the period
    SELECT COALESCE(SUM(quantity), 0) INTO total_sold
    FROM inventory_stock_movements
    WHERE product_id = (SELECT id FROM inventory_products WHERE uuid = product_uuid)
    AND movement_type = 'out'
    AND created_at >= CURRENT_DATE - INTERVAL '1 month' * period_months;

    -- Calculate average inventory (simplified)
    SELECT COALESCE(AVG(quantity), 0) INTO average_inventory
    FROM inventory_stock_movements
    WHERE product_id = (SELECT id FROM inventory_products WHERE uuid = product_uuid)
    AND created_at >= CURRENT_DATE - INTERVAL '1 month' * period_months;

    IF average_inventory = 0 THEN
        turnover_ratio := 0;
    ELSE
        turnover_ratio := total_sold / average_inventory;
    END IF;

    RETURN turnover_ratio;
END;
$$ LANGUAGE plpgsql;

-- Comments
COMMENT ON FUNCTION calculate_project_progress(UUID) IS 'Calculates project completion percentage based on completed tasks';
COMMENT ON FUNCTION calculate_project_total_time(UUID) IS 'Calculates total hours logged on a project';
COMMENT ON FUNCTION get_user_billable_hours(UUID, DATE) IS 'Gets total billable hours for a user in a specific month';
COMMENT ON FUNCTION calculate_inventory_turnover(UUID, INTEGER) IS 'Calculates inventory turnover ratio for a product';
