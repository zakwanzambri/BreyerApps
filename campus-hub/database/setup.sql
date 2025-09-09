-- Campus Hub Database Structure
-- Created for AI Vibe Coding Challenge 2025 Enhancement

CREATE DATABASE IF NOT EXISTS campus_hub_db;
USE campus_hub_db;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'staff', 'admin') DEFAULT 'student',
    student_id VARCHAR(20) UNIQUE NULL,
    program_id INT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Diploma programs table
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(10) UNIQUE NOT NULL,
    program_name VARCHAR(100) NOT NULL,
    description TEXT,
    duration_semesters INT DEFAULT 6,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- News and announcements table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    category ENUM('academic', 'events', 'campus', 'urgent') DEFAULT 'campus',
    author_id INT,
    featured BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    publish_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Events and academic calendar
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_type ENUM('academic', 'social', 'administrative', 'holiday') DEFAULT 'academic',
    start_date DATE NOT NULL,
    end_date DATE NULL,
    start_time TIME NULL,
    end_time TIME NULL,
    location VARCHAR(100),
    target_audience ENUM('all', 'students', 'staff', 'specific_program') DEFAULT 'all',
    program_id INT NULL,
    status ENUM('scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL
);

-- Campus services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    category ENUM('dining', 'health', 'library', 'hostel', 'transport', 'administrative') NOT NULL,
    location VARCHAR(100),
    contact_info VARCHAR(200),
    operating_hours TEXT,
    status ENUM('active', 'maintenance', 'inactive') DEFAULT 'active',
    features JSON, -- Store additional service features as JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Course materials and resources
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    program_id INT NOT NULL,
    semester INT NOT NULL,
    description TEXT,
    credit_hours INT DEFAULT 3,
    instructor VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- Course materials/resources
CREATE TABLE course_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    material_type ENUM('syllabus', 'lecture_notes', 'assignment', 'reading_material', 'video', 'other') NOT NULL,
    file_path VARCHAR(500),
    download_url VARCHAR(500),
    access_level ENUM('public', 'enrolled_only', 'restricted') DEFAULT 'enrolled_only',
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- User sessions for authentication
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- System settings and configurations
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert initial diploma programs
INSERT INTO programs (program_code, program_name, description) VALUES
('DCA', 'Diploma in Culinary Arts', 'Comprehensive culinary training program focusing on cooking techniques, food safety, and kitchen management'),
('DCST', 'Diploma in Computer Systems Technology', 'Advanced computer systems program covering networking, hardware, software development, and IT support'),
('DEW', 'Diploma in Electrical Wiring', 'Electrical installation and maintenance program with hands-on training in industrial and residential wiring'),
('DFBM', 'Diploma in Food & Beverage Management', 'Hospitality management program focusing on restaurant operations, customer service, and business management'),
('DAM', 'Diploma in Administrative Management', 'Business administration program covering office management, accounting, human resources, and organizational skills');

-- Insert default admin user (password: admin123 - should be changed!)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
('admin', 'admin@campus.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Insert sample news items
INSERT INTO news (title, content, excerpt, category, author_id, featured, status, publish_date) VALUES
('Welcome to Enhanced Campus Hub!', 'The Campus Hub portal has been upgraded with dynamic content management and user authentication. Students and staff can now access personalized information and real-time updates.', 'Campus Hub portal enhanced with dynamic features', 'campus', 1, TRUE, 'published', NOW()),
('Semester 2 Registration Now Open', 'Registration for Semester 2 courses is now available through the student portal. All diploma programs are included. Deadline: September 30, 2025.', 'Semester 2 registration deadline September 30', 'academic', 1, FALSE, 'published', NOW()),
('Industry Career Fair This Friday', 'Meet with employers from various industries including hospitality, IT, electrical, and business sectors. Main Hall, 10 AM - 4 PM.', 'Career fair featuring multiple industry partners', 'events', 1, FALSE, 'published', NOW());

-- Insert sample events
INSERT INTO events (title, description, event_type, start_date, end_date, location, target_audience) VALUES
('Orientation Week', 'New student orientation and campus tour', 'academic', '2025-09-15', '2025-09-19', 'Main Campus', 'students'),
('Mid-Semester Break', 'Academic break for all programs', 'academic', '2025-11-01', '2025-11-07', 'Campus-wide', 'all'),
('Industry Partnership Day', 'Annual networking event with industry partners', 'academic', '2025-10-25', '2025-10-25', 'Conference Hall', 'all'),
('Merdeka Day Holiday', 'National holiday - campus closed', 'holiday', '2025-08-31', '2025-08-31', 'Campus-wide', 'all');

-- Insert campus services
INSERT INTO services (service_name, description, category, location, contact_info, operating_hours) VALUES
('Main Cafeteria', 'Primary dining facility serving local and international cuisine', 'dining', 'Ground Floor, Main Building', 'ext. 2501', 'Monday-Friday: 7:00 AM - 8:00 PM, Saturday: 8:00 AM - 6:00 PM'),
('Health Center', 'On-campus medical facility with qualified nurses and visiting doctors', 'health', 'Block A, Level 1', 'ext. 2502', 'Monday-Friday: 8:00 AM - 5:00 PM'),
('Library', 'Academic library with study spaces, computer lab, and research resources', 'library', 'Block B, Level 2-4', 'ext. 2503', 'Monday-Friday: 8:00 AM - 10:00 PM, Saturday: 9:00 AM - 6:00 PM'),
('Campus Shuttle', 'Free shuttle service connecting campus to nearby transportation hubs', 'transport', 'Main Entrance', 'ext. 2504', 'Monday-Friday: 7:00 AM - 6:00 PM, Every 30 minutes');

-- Insert sample courses for each program
INSERT INTO courses (course_code, course_name, program_id, semester, description, instructor) VALUES
-- Culinary Arts
('CA101', 'Introduction to Culinary Arts', 1, 1, 'Basic cooking techniques and kitchen safety', 'Chef Ahmad Rahman'),
('CA201', 'Advanced Cooking Methods', 1, 2, 'Advanced culinary techniques and presentation', 'Chef Sarah Lee'),
-- Computer Systems
('CS101', 'Computer Fundamentals', 2, 1, 'Introduction to computer hardware and software', 'Dr. Lim Wei Ming'),
('CS201', 'Network Administration', 2, 2, 'Network setup, configuration, and maintenance', 'Ir. Raj Kumar'),
-- Electrical Wiring
('EW101', 'Basic Electrical Principles', 3, 1, 'Fundamental electrical concepts and safety', 'Eng. Mohd Faiz'),
('EW201', 'Industrial Wiring Systems', 3, 2, 'Commercial and industrial electrical installation', 'Eng. Jennifer Tan'),
-- F&B Management
('FB101', 'Introduction to Hospitality', 4, 1, 'Basics of food and beverage service industry', 'Ms. Priya Sharma'),
('FB201', 'Restaurant Operations', 4, 2, 'Restaurant management and customer service', 'Mr. David Wong'),
-- Administrative Management
('AM101', 'Office Management', 5, 1, 'Administrative procedures and office operations', 'Ms. Fatimah Ali'),
('AM201', 'Business Communication', 5, 2, 'Professional communication and documentation', 'Dr. Chen Li Hua');

-- Insert default system settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Campus Hub Portal', 'string', 'Website title and branding'),
('maintenance_mode', 'false', 'boolean', 'Enable/disable maintenance mode'),
('max_upload_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)'),
('session_timeout', '3600', 'number', 'User session timeout in seconds (1 hour)'),
('contact_email', 'info@campus.edu', 'string', 'Primary contact email address'),
('academic_year', '2025/2026', 'string', 'Current academic year');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_news_category ON news(category);
CREATE INDEX idx_news_status ON news(status);
CREATE INDEX idx_events_date ON events(start_date);
CREATE INDEX idx_events_type ON events(event_type);
CREATE INDEX idx_services_category ON services(category);
CREATE INDEX idx_courses_program ON courses(program_id);
CREATE INDEX idx_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);

-- Grant permissions (adjust as needed for your setup)
-- CREATE USER 'campus_user'@'localhost' IDENTIFIED BY 'campus_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON campus_hub_db.* TO 'campus_user'@'localhost';
-- FLUSH PRIVILEGES;
