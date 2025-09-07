-- TPT Open ERP - IoT & Device Integration Schema
-- Migration: 034
-- Description: IoT tables for device management, sensor data collection, and monitoring

-- IoT Devices Table
CREATE TABLE IF NOT EXISTS iot_devices (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    device_id VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    device_type VARCHAR(100) NOT NULL,
    manufacturer VARCHAR(255),
    model VARCHAR(255),
    serial_number VARCHAR(255),
    firmware_version VARCHAR(50),
    ip_address INET,
    mac_address MACADDR,
    location VARCHAR(255),
    zone VARCHAR(100),
    status VARCHAR(50) DEFAULT 'offline',
    last_seen TIMESTAMP NULL,
    battery_level DECIMAL(5,2),
    signal_strength DECIMAL(5,2),
    configuration JSONB,
    capabilities TEXT[],
    tags TEXT[],

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT iot_devices_status_check CHECK (status IN ('online', 'offline', 'maintenance', 'error', 'inactive')),
    CONSTRAINT iot_devices_battery_check CHECK (battery_level >= 0 AND battery_level <= 100),
    CONSTRAINT iot_devices_signal_check CHECK (signal_strength >= 0 AND signal_strength <= 100)
);

-- IoT Sensors Table
CREATE TABLE IF NOT EXISTS iot_sensors (
    id SERIAL PRIMARY KEY,
    device_id INTEGER NOT NULL REFERENCES iot_devices(id) ON DELETE CASCADE,
    sensor_id VARCHAR(100) NOT NULL,
    sensor_type VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    unit VARCHAR(50),
    min_value DECIMAL(15,6),
    max_value DECIMAL(15,6),
    precision_digits INTEGER DEFAULT 2,
    sampling_rate INTEGER, -- in seconds
    is_active BOOLEAN DEFAULT TRUE,
    calibration_date DATE,
    calibration_due DATE,
    configuration JSONB,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    UNIQUE(device_id, sensor_id)
);

-- IoT Sensor Readings Table
CREATE TABLE IF NOT EXISTS iot_sensor_readings (
    id SERIAL PRIMARY KEY,
    sensor_id INTEGER NOT NULL REFERENCES iot_sensors(id) ON DELETE CASCADE,
    reading_value DECIMAL(15,6) NOT NULL,
    reading_unit VARCHAR(50),
    quality_score DECIMAL(3,2), -- 0.00 to 1.00
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metadata JSONB,

    -- Partitioning consideration: This table will grow rapidly
    -- Consider partitioning by date ranges
);

-- IoT Device Groups Table
CREATE TABLE IF NOT EXISTS iot_device_groups (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    group_type VARCHAR(100) NOT NULL,
    parent_group_id INTEGER REFERENCES iot_device_groups(id),
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- IoT Device Group Members Table
CREATE TABLE IF NOT EXISTS iot_device_group_members (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL REFERENCES iot_device_groups(id) ON DELETE CASCADE,
    device_id INTEGER NOT NULL REFERENCES iot_devices(id) ON DELETE CASCADE,

    -- Audit Fields
    added_by INTEGER REFERENCES users(id),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    UNIQUE(group_id, device_id)
);

-- IoT Alerts Table
CREATE TABLE IF NOT EXISTS iot_alerts (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    device_id INTEGER REFERENCES iot_devices(id),
    sensor_id INTEGER REFERENCES iot_sensors(id),
    alert_type VARCHAR(100) NOT NULL,
    severity VARCHAR(20) DEFAULT 'medium',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    threshold_value DECIMAL(15,6),
    actual_value DECIMAL(15,6),
    status VARCHAR(50) DEFAULT 'active',
    acknowledged_by INTEGER REFERENCES users(id),
    acknowledged_at TIMESTAMP NULL,
    resolved_at TIMESTAMP NULL,
    auto_resolve BOOLEAN DEFAULT FALSE,
    notification_sent BOOLEAN DEFAULT FALSE,

    -- Audit Fields
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT iot_alerts_severity_check CHECK (severity IN ('low', 'medium', 'high', 'critical')),
    CONSTRAINT iot_alerts_status_check CHECK (status IN ('active', 'acknowledged', 'resolved', 'dismissed'))
);

-- IoT Commands Table
CREATE TABLE IF NOT EXISTS iot_commands (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    device_id INTEGER NOT NULL REFERENCES iot_devices(id),
    command_type VARCHAR(100) NOT NULL,
    command_data JSONB,
    status VARCHAR(50) DEFAULT 'pending',
    scheduled_at TIMESTAMP NULL,
    executed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    result TEXT,
    error_message TEXT,
    retry_count INTEGER DEFAULT 0,
    max_retries INTEGER DEFAULT 3,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT iot_commands_status_check CHECK (status IN ('pending', 'scheduled', 'executing', 'completed', 'failed', 'cancelled'))
);

-- IoT Maintenance Schedules Table
CREATE TABLE IF NOT EXISTS iot_maintenance_schedules (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    device_id INTEGER NOT NULL REFERENCES iot_devices(id),
    maintenance_type VARCHAR(100) NOT NULL,
    description TEXT,
    schedule_type VARCHAR(50) DEFAULT 'interval',
    interval_days INTEGER,
    interval_hours INTEGER,
    cron_expression VARCHAR(100),
    next_due TIMESTAMP,
    last_completed TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    auto_schedule BOOLEAN DEFAULT FALSE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT iot_maintenance_schedule_type_check CHECK (schedule_type IN ('interval', 'cron', 'manual'))
);

-- IoT Firmware Updates Table
CREATE TABLE IF NOT EXISTS iot_firmware_updates (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    device_type VARCHAR(100) NOT NULL,
    version VARCHAR(50) NOT NULL,
    release_notes TEXT,
    file_path TEXT NOT NULL,
    file_hash VARCHAR(128),
    file_size INTEGER,
    is_mandatory BOOLEAN DEFAULT FALSE,
    rollout_strategy VARCHAR(50) DEFAULT 'immediate',
    status VARCHAR(50) DEFAULT 'draft',

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT iot_firmware_updates_status_check CHECK (status IN ('draft', 'testing', 'released', 'deprecated')),
    CONSTRAINT iot_firmware_updates_rollout_check CHECK (rollout_strategy IN ('immediate', 'staged', 'manual'))
);

-- IoT Device Firmware History Table
CREATE TABLE IF NOT EXISTS iot_device_firmware_history (
    id SERIAL PRIMARY KEY,
    device_id INTEGER NOT NULL REFERENCES iot_devices(id),
    firmware_update_id INTEGER NOT NULL REFERENCES iot_firmware_updates(id),
    previous_version VARCHAR(50),
    new_version VARCHAR(50),
    update_status VARCHAR(50) DEFAULT 'pending',
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    error_message TEXT,

    -- Audit Fields
    initiated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT iot_device_firmware_status_check CHECK (update_status IN ('pending', 'downloading', 'installing', 'completed', 'failed', 'rollback'))
);

-- IoT Data Aggregation Rules Table
CREATE TABLE IF NOT EXISTS iot_data_aggregation_rules (
    id SERIAL PRIMARY KEY,
    uuid UUID NOT NULL DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sensor_type VARCHAR(100) NOT NULL,
    aggregation_type VARCHAR(50) NOT NULL,
    time_window INTERVAL,
    conditions JSONB,
    output_table VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,

    -- Audit Fields
    created_by INTEGER REFERENCES users(id),
    updated_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    -- Constraints
    CONSTRAINT iot_data_aggregation_type_check CHECK (aggregation_type IN ('average', 'sum', 'min', 'max', 'count', 'median'))
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_iot_devices_uuid ON iot_devices(uuid);
CREATE INDEX IF NOT EXISTS idx_iot_devices_id ON iot_devices(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_devices_type ON iot_devices(device_type);
CREATE INDEX IF NOT EXISTS idx_iot_devices_status ON iot_devices(status);
CREATE INDEX IF NOT EXISTS idx_iot_devices_last_seen ON iot_devices(last_seen);

CREATE INDEX IF NOT EXISTS idx_iot_sensors_device ON iot_sensors(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_sensors_type ON iot_sensors(sensor_type);

CREATE INDEX IF NOT EXISTS idx_iot_sensor_readings_sensor ON iot_sensor_readings(sensor_id);
CREATE INDEX IF NOT EXISTS idx_iot_sensor_readings_recorded ON iot_sensor_readings(recorded_at);

CREATE INDEX IF NOT EXISTS idx_iot_device_groups_parent ON iot_device_groups(parent_group_id);

CREATE INDEX IF NOT EXISTS idx_iot_device_group_members_group ON iot_device_group_members(group_id);
CREATE INDEX IF NOT EXISTS idx_iot_device_group_members_device ON iot_device_group_members(device_id);

CREATE INDEX IF NOT EXISTS idx_iot_alerts_device ON iot_alerts(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_alerts_sensor ON iot_alerts(sensor_id);
CREATE INDEX IF NOT EXISTS idx_iot_alerts_status ON iot_alerts(status);
CREATE INDEX IF NOT EXISTS idx_iot_alerts_severity ON iot_alerts(severity);

CREATE INDEX IF NOT EXISTS idx_iot_commands_device ON iot_commands(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_commands_status ON iot_commands(status);
CREATE INDEX IF NOT EXISTS idx_iot_commands_scheduled ON iot_commands(scheduled_at);

CREATE INDEX IF NOT EXISTS idx_iot_maintenance_schedules_device ON iot_maintenance_schedules(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_maintenance_schedules_next_due ON iot_maintenance_schedules(next_due);

CREATE INDEX IF NOT EXISTS idx_iot_firmware_updates_device_type ON iot_firmware_updates(device_type);
CREATE INDEX IF NOT EXISTS idx_iot_firmware_updates_status ON iot_firmware_updates(status);

CREATE INDEX IF NOT EXISTS idx_iot_device_firmware_device ON iot_device_firmware_history(device_id);
CREATE INDEX IF NOT EXISTS idx_iot_device_firmware_update ON iot_device_firmware_history(firmware_update_id);

CREATE INDEX IF NOT EXISTS idx_iot_data_aggregation_sensor_type ON iot_data_aggregation_rules(sensor_type);

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_iot_devices_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_devices_updated_at BEFORE UPDATE ON iot_devices
    FOR EACH ROW EXECUTE FUNCTION update_iot_devices_updated_at();

CREATE OR REPLACE FUNCTION update_iot_sensors_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_sensors_updated_at BEFORE UPDATE ON iot_sensors
    FOR EACH ROW EXECUTE FUNCTION update_iot_sensors_updated_at();

CREATE OR REPLACE FUNCTION update_iot_device_groups_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_device_groups_updated_at BEFORE UPDATE ON iot_device_groups
    FOR EACH ROW EXECUTE FUNCTION update_iot_device_groups_updated_at();

CREATE OR REPLACE FUNCTION update_iot_alerts_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_alerts_updated_at BEFORE UPDATE ON iot_alerts
    FOR EACH ROW EXECUTE FUNCTION update_iot_alerts_updated_at();

CREATE OR REPLACE FUNCTION update_iot_commands_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_commands_updated_at BEFORE UPDATE ON iot_commands
    FOR EACH ROW EXECUTE FUNCTION update_iot_commands_updated_at();

CREATE OR REPLACE FUNCTION update_iot_maintenance_schedules_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_maintenance_schedules_updated_at BEFORE UPDATE ON iot_maintenance_schedules
    FOR EACH ROW EXECUTE FUNCTION update_iot_maintenance_schedules_updated_at();

CREATE OR REPLACE FUNCTION update_iot_firmware_updates_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_firmware_updates_updated_at BEFORE UPDATE ON iot_firmware_updates
    FOR EACH ROW EXECUTE FUNCTION update_iot_firmware_updates_updated_at();

CREATE OR REPLACE FUNCTION update_iot_device_firmware_history_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_device_firmware_history_updated_at BEFORE UPDATE ON iot_device_firmware_history
    FOR EACH ROW EXECUTE FUNCTION update_iot_device_firmware_history_updated_at();

CREATE OR REPLACE FUNCTION update_iot_data_aggregation_rules_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_iot_data_aggregation_rules_updated_at BEFORE UPDATE ON iot_data_aggregation_rules
    FOR EACH ROW EXECUTE FUNCTION update_iot_data_aggregation_rules_updated_at();

-- Comments
COMMENT ON TABLE iot_devices IS 'IoT devices registered in the system';
COMMENT ON TABLE iot_sensors IS 'Sensors attached to IoT devices';
COMMENT ON TABLE iot_sensor_readings IS 'Sensor data readings (consider partitioning by date)';
COMMENT ON TABLE iot_device_groups IS 'Groups for organizing IoT devices';
COMMENT ON TABLE iot_device_group_members IS 'Device membership in groups';
COMMENT ON TABLE iot_alerts IS 'Alerts generated by IoT devices and sensors';
COMMENT ON TABLE iot_commands IS 'Commands sent to IoT devices';
COMMENT ON TABLE iot_maintenance_schedules IS 'Maintenance schedules for IoT devices';
COMMENT ON TABLE iot_firmware_updates IS 'Firmware updates available for devices';
COMMENT ON TABLE iot_device_firmware_history IS 'Firmware update history for devices';
COMMENT ON TABLE iot_data_aggregation_rules IS 'Rules for aggregating sensor data';
