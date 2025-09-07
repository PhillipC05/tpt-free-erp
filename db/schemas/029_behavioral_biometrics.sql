-- TPT Open ERP - Behavioral Biometrics Schema
-- Migration: 029
-- Description: Advanced behavioral biometrics for continuous authentication and threat detection

-- Raw behavioral data collection
CREATE TABLE IF NOT EXISTS behavioral_data (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255),
    behavior_type VARCHAR(50) NOT NULL, -- 'mouse', 'keyboard', 'screen', 'file', 'timing'
    data JSONB NOT NULL, -- Behavioral metrics and patterns
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address INET,
    user_agent TEXT,
    device_fingerprint VARCHAR(255),

    -- Indexes for performance
    INDEX idx_behavioral_user_time (user_id, timestamp),
    INDEX idx_behavioral_type (behavior_type),
    INDEX idx_behavioral_session (session_id)
);

-- Behavioral analysis results
CREATE TABLE IF NOT EXISTS behavioral_analysis (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    session_id VARCHAR(255),
    risk_score DECIMAL(3,2) NOT NULL, -- 0.00 to 1.00
    confidence DECIMAL(3,2) NOT NULL, -- 0.00 to 1.00
    anomalies JSONB, -- Array of detected anomalies
    behavior_data JSONB, -- Original behavior data analyzed
    analysis_details JSONB, -- Detailed analysis breakdown
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_analysis_user_risk (user_id, risk_score),
    INDEX idx_analysis_time (timestamp),
    INDEX idx_analysis_session (session_id)
);

-- User-level behavioral tracking settings
CREATE TABLE IF NOT EXISTS behavioral_settings (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    settings JSONB NOT NULL, -- Granular settings for each user
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT behavioral_settings_unique_user UNIQUE (user_id)
);

-- Team-level behavioral tracking settings
CREATE TABLE IF NOT EXISTS team_behavioral_settings (
    id SERIAL PRIMARY KEY,
    team_id INTEGER NOT NULL, -- References teams table (when implemented)
    settings JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT team_behavioral_settings_unique_team UNIQUE (team_id)
);

-- Company-level behavioral tracking settings
CREATE TABLE IF NOT EXISTS company_behavioral_settings (
    id SERIAL PRIMARY KEY,
    company_id INTEGER NOT NULL, -- References companies table (when implemented)
    settings JSONB NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Constraints
    CONSTRAINT company_behavioral_settings_unique_company UNIQUE (company_id)
);

-- Behavioral profiles cache (for performance)
CREATE TABLE IF NOT EXISTS behavioral_profiles (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    profile_data JSONB NOT NULL, -- Statistical profile
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sample_count INTEGER DEFAULT 0,
    confidence_score DECIMAL(3,2) DEFAULT 0,

    -- Constraints
    CONSTRAINT behavioral_profiles_unique_user UNIQUE (user_id),
    INDEX idx_profiles_updated (last_updated)
);

-- Behavioral alerts and notifications
CREATE TABLE IF NOT EXISTS behavioral_alerts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    alert_type VARCHAR(100) NOT NULL, -- 'high_risk', 'anomaly_detected', 'profile_change'
    severity VARCHAR(20) DEFAULT 'medium', -- 'low', 'medium', 'high', 'critical'
    risk_score DECIMAL(3,2),
    description TEXT,
    alert_data JSONB,
    is_acknowledged BOOLEAN DEFAULT false,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_alerts_user_type (user_id, alert_type),
    INDEX idx_alerts_severity (severity),
    INDEX idx_alerts_created (created_at),
    INDEX idx_alerts_acknowledged (is_acknowledged)
);

-- Behavioral learning data (for ML model training)
CREATE TABLE IF NOT EXISTS behavioral_learning_data (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    behavior_sequence JSONB, -- Sequence of behaviors
    classification VARCHAR(50), -- 'normal', 'suspicious', 'malicious'
    confidence DECIMAL(3,2),
    feedback_given BOOLEAN DEFAULT false,
    feedback_user_id INTEGER REFERENCES users(id),
    feedback_timestamp TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Indexes
    INDEX idx_learning_user (user_id),
    INDEX idx_learning_classification (classification),
    INDEX idx_learning_feedback (feedback_given)
);

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_behavioral_data_user_type_time ON behavioral_data(user_id, behavior_type, timestamp DESC);
CREATE INDEX IF NOT EXISTS idx_behavioral_analysis_user_score ON behavioral_analysis(user_id, risk_score DESC);
CREATE INDEX IF NOT EXISTS idx_behavioral_alerts_user_unread ON behavioral_alerts(user_id, is_acknowledged) WHERE is_acknowledged = false;

-- Triggers for updated_at
CREATE OR REPLACE FUNCTION update_behavioral_settings_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_behavioral_settings_updated_at
    BEFORE UPDATE ON behavioral_settings
    FOR EACH ROW EXECUTE FUNCTION update_behavioral_settings_updated_at();

CREATE OR REPLACE FUNCTION update_team_behavioral_settings_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_team_behavioral_settings_updated_at
    BEFORE UPDATE ON team_behavioral_settings
    FOR EACH ROW EXECUTE FUNCTION update_team_behavioral_settings_updated_at();

CREATE OR REPLACE FUNCTION update_company_behavioral_settings_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_company_behavioral_settings_updated_at
    BEFORE UPDATE ON company_behavioral_settings
    FOR EACH ROW EXECUTE FUNCTION update_company_behavioral_settings_updated_at();

-- Update behavioral profile on new data
CREATE OR REPLACE FUNCTION update_behavioral_profile_on_insert()
RETURNS TRIGGER AS $$
BEGIN
    -- Increment sample count
    UPDATE behavioral_profiles
    SET sample_count = sample_count + 1,
        last_updated = CURRENT_TIMESTAMP
    WHERE user_id = NEW.user_id;

    -- Insert if profile doesn't exist
    INSERT INTO behavioral_profiles (user_id, profile_data, sample_count, last_updated)
    VALUES (NEW.user_id, '{}'::jsonb, 1, CURRENT_TIMESTAMP)
    ON CONFLICT (user_id) DO NOTHING;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER update_behavioral_profile_on_insert
    AFTER INSERT ON behavioral_data
    FOR EACH ROW EXECUTE FUNCTION update_behavioral_profile_on_insert();

-- Comments
COMMENT ON TABLE behavioral_data IS 'Raw behavioral data collected from user interactions';
COMMENT ON TABLE behavioral_analysis IS 'Risk analysis results for behavioral patterns';
COMMENT ON TABLE behavioral_settings IS 'User-specific behavioral tracking settings';
COMMENT ON TABLE team_behavioral_settings IS 'Team-level behavioral tracking settings';
COMMENT ON TABLE company_behavioral_settings IS 'Company-wide behavioral tracking settings';
COMMENT ON TABLE behavioral_profiles IS 'Cached statistical profiles for performance';
COMMENT ON TABLE behavioral_alerts IS 'Security alerts for behavioral anomalies';
COMMENT ON TABLE behavioral_learning_data IS 'Data for training behavioral analysis models';

-- Sample settings structure (for documentation)
-- behavioral_settings.settings structure:
-- {
--   "enabled": true,
--   "sensitivity": "medium", // low, medium, high
--   "alert_threshold": 0.7,
--   "learning_mode": true,
--   "track_mouse": true,
--   "track_keyboard": true,
--   "track_screen": true,
--   "track_files": true,
--   "continuous_auth": true,
--   "risk_actions": {
--     "high": "require_2fa",
--     "critical": "lock_account"
--   }
-- }

-- behavioral_data.data structure examples:
-- Mouse: {"velocity": 1250.5, "acceleration": 450.2, "path_complexity": 0.85}
-- Keyboard: {"wpm": 85, "pause_avg": 0.25, "error_rate": 0.02}
-- Screen: {"switch_count": 12, "avg_duration": 45.5, "focus_changes": 8}
-- File: {"operations_per_minute": 25, "drag_distance_avg": 150, "file_types": ["pdf","docx"]}
