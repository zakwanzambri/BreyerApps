-- Campus Hub Enhanced Database Schema
-- Complete database structure with all necessary tables

-- Create database
CREATE DATABASE IF NOT EXISTS campus_hub;
USE campus_hub;

-- Users table (enhanced)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff', 'student') NOT NULL DEFAULT 'student',
    student_id VARCHAR(20) NULL,
    program_id VARCHAR(10) NULL,
    year_of_study INT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    date_of_birth DATE NULL,
    emergency_contact TEXT NULL,
    avatar_url VARCHAR(255) NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
);

-- News table (enhanced)
CREATE TABLE IF NOT EXISTS news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    summary TEXT NULL,
    content TEXT NOT NULL,
    category VARCHAR(50) NOT NULL,
    author_id INT NOT NULL,
    image_url VARCHAR(255) NULL,
    is_featured BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_created (created_at),
    FULLTEXT idx_search (title, content, summary)
);

-- Events table (enhanced)
CREATE TABLE IF NOT EXISTS events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(200) NOT NULL,
    max_participants INT DEFAULT 0,
    organizer_id INT NOT NULL,
    image_url VARCHAR(255) NULL,
    status ENUM('pending', 'active', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_dates (start_date, end_date),
    INDEX idx_type (event_type),
    INDEX idx_status (status),
    FULLTEXT idx_search (title, description, location)
);

-- Event registrations table
CREATE TABLE IF NOT EXISTS event_registrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('registered', 'cancelled', 'attended') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, user_id),
    INDEX idx_status (status)
);

-- Programs table
CREATE TABLE IF NOT EXISTS programs (
    id VARCHAR(10) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    faculty VARCHAR(100) NOT NULL,
    duration_years INT DEFAULT 4,
    description TEXT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Academic years table
CREATE TABLE IF NOT EXISTS academic_years (
    id INT PRIMARY KEY AUTO_INCREMENT,
    year VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    type ENUM('general', 'urgent', 'academic', 'event') DEFAULT 'general',
    target_audience ENUM('all', 'students', 'staff', 'admin') DEFAULT 'all',
    author_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at)
);

-- File uploads table
CREATE TABLE IF NOT EXISTS file_uploads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    category ENUM('avatar', 'news', 'event', 'document') NOT NULL,
    reference_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_category (category),
    INDEX idx_reference (reference_id)
);

-- Activity logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50) NULL,
    record_id INT NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
);

-- Sessions table for better session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    data TEXT NOT NULL,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);

-- Settings table for application configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NULL,
    description TEXT NULL,
    type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_key (key_name),
    INDEX idx_public (is_public)
);

-- Insert sample programs
INSERT INTO programs (id, name, faculty, duration_years, description) VALUES
('CS', 'Computer Science', 'Faculty of Computing', 4, 'Bachelor of Computer Science program'),
('IT', 'Information Technology', 'Faculty of Computing', 4, 'Bachelor of Information Technology program'),
('SE', 'Software Engineering', 'Faculty of Computing', 4, 'Bachelor of Software Engineering program'),
('IS', 'Information Systems', 'Faculty of Computing', 4, 'Bachelor of Information Systems program'),
('CE', 'Computer Engineering', 'Faculty of Engineering', 4, 'Bachelor of Computer Engineering program'),
('EE', 'Electrical Engineering', 'Faculty of Engineering', 4, 'Bachelor of Electrical Engineering program'),
('ME', 'Mechanical Engineering', 'Faculty of Engineering', 4, 'Bachelor of Mechanical Engineering program'),
('BM', 'Business Management', 'Faculty of Business', 3, 'Bachelor of Business Management program'),
('ACC', 'Accounting', 'Faculty of Business', 3, 'Bachelor of Accounting program'),
('MKT', 'Marketing', 'Faculty of Business', 3, 'Bachelor of Marketing program');

-- Insert current academic year
INSERT INTO academic_years (year, start_date, end_date, is_current) VALUES
('2024/2025', '2024-09-01', '2025-08-31', TRUE);

-- Insert default settings
INSERT INTO settings (key_name, value, description, type, is_public) VALUES
('site_name', 'Campus Hub Portal', 'Application name', 'string', TRUE),
('site_description', 'Comprehensive campus management system', 'Application description', 'string', TRUE),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)', 'number', FALSE),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)', 'number', FALSE),
('enable_registration', 'true', 'Allow new user registration', 'boolean', TRUE),
('maintenance_mode', 'false', 'Enable maintenance mode', 'boolean', FALSE),
('email_notifications', 'true', 'Enable email notifications', 'boolean', FALSE),
('default_user_role', 'student', 'Default role for new users', 'string', FALSE);

-- Update existing users with enhanced fields (if they exist)
-- This will only run if users table has data
UPDATE users SET 
    phone = NULL,
    address = NULL,
    date_of_birth = NULL,
    emergency_contact = NULL,
    avatar_url = NULL
WHERE phone IS NULL;

-- Add some sample news categories (insert into existing news if any)
-- These are just for reference - actual news insertion should be done via API

-- Add some sample event types
-- These are just for reference - actual events should be created via API

-- Create views for common queries
CREATE OR REPLACE VIEW active_users AS
SELECT 
    id, username, name, email, role, student_id, program_id, 
    last_login, created_at
FROM users 
WHERE status = 'active';

CREATE OR REPLACE VIEW published_news AS
SELECT 
    n.id, n.title, n.summary, n.category, n.is_featured, 
    n.views, n.created_at, u.name as author_name
FROM news n
JOIN users u ON n.author_id = u.id
WHERE n.status = 'published'
ORDER BY n.is_featured DESC, n.created_at DESC;

CREATE OR REPLACE VIEW upcoming_events AS
SELECT 
    e.id, e.title, e.event_type, e.start_date, e.start_time,
    e.location, e.max_participants, u.name as organizer_name,
    COUNT(r.id) as registered_count
FROM events e
JOIN users u ON e.organizer_id = u.id
LEFT JOIN event_registrations r ON e.id = r.event_id AND r.status = 'registered'
WHERE e.status = 'active' AND e.start_date >= CURDATE()
GROUP BY e.id
ORDER BY e.start_date ASC, e.start_time ASC;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE GetUserStats()
BEGIN
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
        SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as student_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as active_last_30_days
    FROM users;
END //

CREATE PROCEDURE GetContentStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM news WHERE status = 'published') as published_news,
        (SELECT COUNT(*) FROM events WHERE status = 'active') as active_events,
        (SELECT COUNT(*) FROM event_registrations WHERE status = 'registered') as total_registrations,
        (SELECT COUNT(*) FROM announcements WHERE is_active = TRUE) as active_announcements;
END //

DELIMITER ;

-- Create triggers for audit logging
DELIMITER //

CREATE TRIGGER user_update_log AFTER UPDATE ON users
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, details)
    VALUES (NEW.id, 'user_updated', 'users', NEW.id, JSON_OBJECT('old_email', OLD.email, 'new_email', NEW.email));
END //

CREATE TRIGGER news_create_log AFTER INSERT ON news
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, details)
    VALUES (NEW.author_id, 'news_created', 'news', NEW.id, JSON_OBJECT('title', NEW.title, 'category', NEW.category));
END //

CREATE TRIGGER event_create_log AFTER INSERT ON events
FOR EACH ROW BEGIN
    INSERT INTO activity_logs (user_id, action, table_name, record_id, details)
    VALUES (NEW.organizer_id, 'event_created', 'events', NEW.id, JSON_OBJECT('title', NEW.title, 'type', NEW.event_type));
END //

DELIMITER ;

-- Insert sample notification for admin
INSERT INTO notifications (user_id, title, message, type) VALUES
(1, 'Welcome to Campus Hub', 'Your enhanced campus management system is ready!', 'success');

COMMIT;
