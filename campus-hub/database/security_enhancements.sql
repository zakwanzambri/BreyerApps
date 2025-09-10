-- Campus Hub Portal - Security Database Enhancements
-- Additional tables for enhanced security features

-- Login attempts tracking
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'Username, email, or IP',
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    success BOOLEAN NOT NULL DEFAULT FALSE,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier (identifier),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Security events logging
CREATE TABLE IF NOT EXISTS security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL COMMENT 'login_failure, suspicious_activity, etc.',
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    request_uri VARCHAR(500) NULL,
    description TEXT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_severity (severity),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Rate limiting tracking
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL COMMENT 'IP address or user ID',
    request_type VARCHAR(50) NOT NULL COMMENT 'api, login, upload, search',
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_type (identifier, request_type),
    INDEX idx_request_time (request_time)
);

-- Session management
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NULL,
    data TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password history (prevent password reuse)
CREATE TABLE IF NOT EXISTS password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Two-factor authentication
CREATE TABLE IF NOT EXISTS two_factor_auth (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    secret_key VARCHAR(32) NOT NULL,
    backup_codes JSON NULL COMMENT 'Array of backup codes',
    enabled BOOLEAN DEFAULT FALSE,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Security settings per user
CREATE TABLE IF NOT EXISTS user_security_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    login_notifications BOOLEAN DEFAULT TRUE,
    suspicious_activity_alerts BOOLEAN DEFAULT TRUE,
    password_expiry_days INT DEFAULT 90,
    session_timeout_minutes INT DEFAULT 30,
    allowed_ips JSON NULL COMMENT 'Array of allowed IP addresses',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- API keys for external integrations
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    permissions JSON NULL COMMENT 'Array of allowed endpoints/actions',
    rate_limit_per_hour INT DEFAULT 1000,
    last_used TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_key_hash (key_hash),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- File upload security tracking
CREATE TABLE IF NOT EXISTS upload_security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    security_scan_result ENUM('safe', 'suspicious', 'malicious') DEFAULT 'safe',
    upload_path VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_security_scan_result (security_scan_result),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Content security policies
CREATE TABLE IF NOT EXISTS content_security_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_pattern VARCHAR(255) NOT NULL COMMENT 'URL pattern or specific page',
    csp_directive TEXT NOT NULL COMMENT 'Content Security Policy directive',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_page_pattern (page_pattern),
    INDEX idx_is_active (is_active)
);

-- Add security-related columns to existing users table
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL AFTER password,
ADD COLUMN IF NOT EXISTS last_login_at TIMESTAMP NULL AFTER password_changed_at,
ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) NULL AFTER last_login_at,
ADD COLUMN IF NOT EXISTS failed_login_attempts INT DEFAULT 0 AFTER last_login_ip,
ADD COLUMN IF NOT EXISTS account_locked_until TIMESTAMP NULL AFTER failed_login_attempts,
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE AFTER account_locked_until,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(64) NULL AFTER email_verified;

-- Add indexes for performance
ALTER TABLE users 
ADD INDEX IF NOT EXISTS idx_email_verified (email_verified),
ADD INDEX IF NOT EXISTS idx_account_locked_until (account_locked_until),
ADD INDEX IF NOT EXISTS idx_last_login_at (last_login_at);

-- Insert default content security policies
INSERT IGNORE INTO content_security_policies (page_pattern, csp_directive, is_active) VALUES
('*', 'default-src ''self''; script-src ''self'' ''unsafe-inline'' https://cdn.jsdelivr.net; style-src ''self'' ''unsafe-inline'' https://fonts.googleapis.com; font-src ''self'' https://fonts.gstatic.com; img-src ''self'' data: https:; connect-src ''self''; frame-ancestors ''none'';', TRUE),
('/admin/*', 'default-src ''self''; script-src ''self''; style-src ''self'' ''unsafe-inline''; img-src ''self'' data:; connect-src ''self''; frame-ancestors ''none'';', TRUE),
('/api/*', 'default-src ''none''; connect-src ''self'';', TRUE);

-- Create triggers for security auditing
DELIMITER //

-- Trigger to log password changes
CREATE TRIGGER IF NOT EXISTS user_password_change_log 
BEFORE UPDATE ON users 
FOR EACH ROW 
BEGIN 
    IF OLD.password != NEW.password THEN
        SET NEW.password_changed_at = CURRENT_TIMESTAMP;
        
        -- Log the password change
        INSERT INTO security_logs (event_type, user_id, ip_address, description, severity)
        VALUES ('password_change', NEW.id, COALESCE(@user_ip, '0.0.0.0'), 'Password changed', 'medium');
        
        -- Store password history
        INSERT INTO password_history (user_id, password_hash)
        VALUES (NEW.id, OLD.password);
    END IF;
END//

-- Trigger to log user status changes
CREATE TRIGGER IF NOT EXISTS user_status_change_log 
BEFORE UPDATE ON users 
FOR EACH ROW 
BEGIN 
    IF OLD.status != NEW.status THEN
        INSERT INTO security_logs (event_type, user_id, ip_address, description, severity)
        VALUES ('status_change', NEW.id, COALESCE(@user_ip, '0.0.0.0'), 
                CONCAT('Status changed from ', OLD.status, ' to ', NEW.status), 'medium');
    END IF;
END//

-- Trigger to log role changes
CREATE TRIGGER IF NOT EXISTS user_role_change_log 
BEFORE UPDATE ON users 
FOR EACH ROW 
BEGIN 
    IF OLD.role != NEW.role THEN
        INSERT INTO security_logs (event_type, user_id, ip_address, description, severity)
        VALUES ('role_change', NEW.id, COALESCE(@user_ip, '0.0.0.0'), 
                CONCAT('Role changed from ', OLD.role, ' to ', NEW.role), 'high');
    END IF;
END//

DELIMITER ;

-- Create views for security monitoring
CREATE OR REPLACE VIEW security_dashboard AS
SELECT 
    'Failed Logins (24h)' as metric,
    COUNT(*) as value,
    'count' as type
FROM login_attempts 
WHERE success = FALSE AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)

UNION ALL

SELECT 
    'Successful Logins (24h)' as metric,
    COUNT(*) as value,
    'count' as type
FROM login_attempts 
WHERE success = TRUE AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)

UNION ALL

SELECT 
    'High Severity Events (7d)' as metric,
    COUNT(*) as value,
    'count' as type
FROM security_logs 
WHERE severity = 'high' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)

UNION ALL

SELECT 
    'Active Sessions' as metric,
    COUNT(*) as value,
    'count' as type
FROM sessions 
WHERE expires_at > NOW()

UNION ALL

SELECT 
    'Locked Accounts' as metric,
    COUNT(*) as value,
    'count' as type
FROM users 
WHERE account_locked_until > NOW()

UNION ALL

SELECT 
    'Unverified Emails' as metric,
    COUNT(*) as value,
    'count' as type
FROM users 
WHERE email_verified = FALSE AND status = 'active';

-- Create view for recent security events
CREATE OR REPLACE VIEW recent_security_events AS
SELECT 
    sl.id,
    sl.event_type,
    sl.user_id,
    u.username,
    u.name as user_name,
    sl.ip_address,
    sl.description,
    sl.severity,
    sl.created_at
FROM security_logs sl
LEFT JOIN users u ON sl.user_id = u.id
ORDER BY sl.created_at DESC
LIMIT 100;

-- Create view for suspicious activity monitoring
CREATE OR REPLACE VIEW suspicious_activity AS
SELECT 
    user_id,
    username,
    name as user_name,
    COUNT(*) as event_count,
    GROUP_CONCAT(DISTINCT event_type) as event_types,
    MAX(created_at) as last_event,
    COUNT(DISTINCT ip_address) as unique_ips
FROM security_logs sl
LEFT JOIN users u ON sl.user_id = u.id
WHERE sl.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
AND sl.severity IN ('high', 'critical')
GROUP BY user_id, username, name
HAVING event_count > 3 OR unique_ips > 2
ORDER BY event_count DESC, last_event DESC;

-- Create stored procedures for security operations
DELIMITER //

-- Procedure to clean up old security data
CREATE PROCEDURE IF NOT EXISTS CleanupSecurityData()
BEGIN
    DECLARE deleted_attempts INT DEFAULT 0;
    DECLARE deleted_logs INT DEFAULT 0;
    DECLARE deleted_sessions INT DEFAULT 0;
    
    -- Delete old login attempts (older than 30 days)
    DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
    SET deleted_attempts = ROW_COUNT();
    
    -- Delete old security logs (older than 90 days, except critical)
    DELETE FROM security_logs 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
    AND severity != 'critical';
    SET deleted_logs = ROW_COUNT();
    
    -- Delete expired sessions
    DELETE FROM sessions WHERE expires_at < NOW();
    SET deleted_sessions = ROW_COUNT();
    
    -- Delete old password history (keep last 5 per user)
    DELETE ph1 FROM password_history ph1
    INNER JOIN (
        SELECT user_id, id
        FROM (
            SELECT user_id, id,
                   ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY created_at DESC) as rn
            FROM password_history
        ) ranked
        WHERE rn > 5
    ) ph2 ON ph1.id = ph2.id;
    
    -- Clean up old rate limit entries
    DELETE FROM rate_limits WHERE request_time < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    SELECT deleted_attempts as login_attempts_deleted,
           deleted_logs as security_logs_deleted,
           deleted_sessions as sessions_deleted;
END//

-- Procedure to generate security report
CREATE PROCEDURE IF NOT EXISTS GenerateSecurityReport(IN days_back INT)
BEGIN
    SELECT 'Security Report' as report_title, 
           DATE_SUB(NOW(), INTERVAL days_back DAY) as report_start_date,
           NOW() as report_end_date;
    
    -- Login statistics
    SELECT 'Login Statistics' as section;
    SELECT 
        DATE(attempt_time) as date,
        COUNT(*) as total_attempts,
        SUM(CASE WHEN success = TRUE THEN 1 ELSE 0 END) as successful_logins,
        SUM(CASE WHEN success = FALSE THEN 1 ELSE 0 END) as failed_logins,
        COUNT(DISTINCT ip_address) as unique_ips
    FROM login_attempts 
    WHERE attempt_time > DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY DATE(attempt_time)
    ORDER BY date DESC;
    
    -- Security events by severity
    SELECT 'Security Events by Severity' as section;
    SELECT 
        severity,
        COUNT(*) as event_count
    FROM security_logs 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY severity
    ORDER BY FIELD(severity, 'critical', 'high', 'medium', 'low');
    
    -- Top event types
    SELECT 'Top Security Event Types' as section;
    SELECT 
        event_type,
        COUNT(*) as event_count,
        severity
    FROM security_logs 
    WHERE created_at > DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY event_type, severity
    ORDER BY event_count DESC
    LIMIT 10;
    
    -- Top IP addresses by failed logins
    SELECT 'Top IPs by Failed Logins' as section;
    SELECT 
        ip_address,
        COUNT(*) as failed_attempts,
        MIN(attempt_time) as first_attempt,
        MAX(attempt_time) as last_attempt
    FROM login_attempts 
    WHERE success = FALSE 
    AND attempt_time > DATE_SUB(NOW(), INTERVAL days_back DAY)
    GROUP BY ip_address
    HAVING failed_attempts > 5
    ORDER BY failed_attempts DESC
    LIMIT 20;
END//

DELIMITER ;

-- Insert some sample security policies (can be customized)
INSERT IGNORE INTO user_security_settings (user_id, two_factor_enabled, password_expiry_days, session_timeout_minutes)
SELECT id, FALSE, 90, 30 FROM users WHERE id NOT IN (SELECT user_id FROM user_security_settings);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_security_logs_composite ON security_logs(event_type, severity, created_at);
CREATE INDEX IF NOT EXISTS idx_login_attempts_composite ON login_attempts(identifier, success, attempt_time);
CREATE INDEX IF NOT EXISTS idx_rate_limits_composite ON rate_limits(identifier, request_type, request_time);

-- Create events for automatic cleanup (MySQL 5.7+)
-- Note: Events require SUPER privilege and event_scheduler = ON

/*
CREATE EVENT IF NOT EXISTS cleanup_security_data
ON SCHEDULE EVERY 1 DAY
STARTS '2024-01-01 02:00:00'
DO
  CALL CleanupSecurityData();

CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
  DELETE FROM sessions WHERE expires_at < NOW();
*/

-- Grant necessary permissions for security operations
-- Note: Adjust these based on your user privilege requirements

/*
GRANT SELECT, INSERT, UPDATE, DELETE ON login_attempts TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON security_logs TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON rate_limits TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON sessions TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON password_history TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON two_factor_auth TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON user_security_settings TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON api_keys TO 'campus_user'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON upload_security_logs TO 'campus_user'@'localhost';
GRANT SELECT ON security_dashboard TO 'campus_user'@'localhost';
GRANT SELECT ON recent_security_events TO 'campus_user'@'localhost';
GRANT SELECT ON suspicious_activity TO 'campus_user'@'localhost';
GRANT EXECUTE ON PROCEDURE CleanupSecurityData TO 'campus_user'@'localhost';
GRANT EXECUTE ON PROCEDURE GenerateSecurityReport TO 'campus_user'@'localhost';
*/
