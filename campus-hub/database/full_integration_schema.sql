-- Enhanced Database Schema for Full Integration
-- Campus Hub - Real-Time Data Integration

-- Add new tables for full academic integration
USE campus_hub_db;

-- Courses table
CREATE TABLE IF NOT EXISTS courses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    program_id INT NOT NULL,
    credits INT DEFAULT 3,
    semester INT NOT NULL,
    lecturer_id INT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (lecturer_id) REFERENCES users(id)
);

-- Student Enrollments
CREATE TABLE IF NOT EXISTS enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    enrollment_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    grade VARCHAR(5) NULL,
    gpa_points DECIMAL(3,2) NULL,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (course_id) REFERENCES courses(id),
    UNIQUE KEY unique_enrollment (student_id, course_id)
);

-- Assignments
CREATE TABLE IF NOT EXISTS assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    course_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATETIME NOT NULL,
    total_marks INT DEFAULT 100,
    assignment_type ENUM('quiz', 'project', 'exam', 'homework') DEFAULT 'homework',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Student Assignment Submissions
CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    marks_obtained INT NULL,
    status ENUM('pending', 'submitted', 'graded') DEFAULT 'pending',
    feedback TEXT NULL,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id),
    FOREIGN KEY (student_id) REFERENCES users(id),
    UNIQUE KEY unique_submission (assignment_id, student_id)
);

-- Academic Calendar Events
CREATE TABLE IF NOT EXISTS academic_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NULL,
    event_type ENUM('exam', 'deadline', 'holiday', 'registration', 'orientation') NOT NULL,
    program_id INT NULL,
    course_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('academic', 'administrative', 'social', 'urgent') DEFAULT 'academic',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- User Preferences
CREATE TABLE IF NOT EXISTS user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    theme ENUM('light', 'dark') DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    dashboard_layout JSON,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample courses
INSERT IGNORE INTO courses (course_code, course_name, program_id, semester, description) VALUES
-- IT Program Courses
('IT101', 'Introduction to Programming', 1, 1, 'Basic programming concepts using Python'),
('IT102', 'Database Fundamentals', 1, 1, 'Introduction to database design and SQL'),
('IT201', 'Web Development', 1, 2, 'HTML, CSS, JavaScript and modern frameworks'),
('IT202', 'Data Structures', 1, 2, 'Algorithms and data structure implementation'),
('IT301', 'System Analysis', 1, 3, 'Software engineering and system design'),

-- Business Program Courses  
('BUS101', 'Business Mathematics', 2, 1, 'Mathematical concepts for business applications'),
('BUS102', 'Principles of Management', 2, 1, 'Fundamentals of business management'),
('BUS201', 'Financial Accounting', 2, 2, 'Basic accounting principles and practices'),
('BUS202', 'Marketing Fundamentals', 2, 2, 'Introduction to marketing strategies'),

-- Engineering Courses
('ENG101', 'Engineering Mathematics', 3, 1, 'Advanced mathematics for engineers'),
('ENG102', 'Technical Drawing', 3, 1, 'CAD and technical illustration'),
('ENG201', 'Circuit Analysis', 3, 2, 'Electrical circuit theory and analysis');

-- Insert sample enrollments
INSERT IGNORE INTO enrollments (student_id, course_id) VALUES
-- Student 1 (Ahmad Rahman - IT)
((SELECT id FROM users WHERE username = 'student1'), (SELECT id FROM courses WHERE course_code = 'IT101')),
((SELECT id FROM users WHERE username = 'student1'), (SELECT id FROM courses WHERE course_code = 'IT102')),
((SELECT id FROM users WHERE username = 'student1'), (SELECT id FROM courses WHERE course_code = 'IT201')),

-- Student 2 (Siti Aminah - Business)
((SELECT id FROM users WHERE username = 'student2'), (SELECT id FROM courses WHERE course_code = 'BUS101')),
((SELECT id FROM users WHERE username = 'student2'), (SELECT id FROM courses WHERE course_code = 'BUS102')),

-- Student 3 (Muthu Krishnan - Engineering)
((SELECT id FROM users WHERE username = 'student3'), (SELECT id FROM courses WHERE course_code = 'ENG101')),
((SELECT id FROM users WHERE username = 'student3'), (SELECT id FROM courses WHERE course_code = 'ENG102'));

-- Insert sample assignments
INSERT IGNORE INTO assignments (course_id, title, description, due_date, assignment_type) VALUES
((SELECT id FROM courses WHERE course_code = 'IT101'), 'Python Basic Exercises', 'Complete exercises 1-10 from textbook', '2025-09-20 23:59:00', 'homework'),
((SELECT id FROM courses WHERE course_code = 'IT102'), 'Database Design Project', 'Design a database for a library system', '2025-09-25 23:59:00', 'project'),
((SELECT id FROM courses WHERE course_code = 'IT201'), 'Personal Portfolio Website', 'Create a responsive portfolio website', '2025-10-01 23:59:00', 'project'),
((SELECT id FROM courses WHERE course_code = 'BUS101'), 'Math Quiz 1', 'Business mathematics fundamentals', '2025-09-18 14:00:00', 'quiz'),
((SELECT id FROM courses WHERE course_code = 'ENG101'), 'Calculus Assignment', 'Solve differential equations problems', '2025-09-22 23:59:00', 'homework');

-- Insert academic events
INSERT IGNORE INTO academic_events (title, description, event_date, event_type, program_id) VALUES
('Semester 2 Registration Opens', 'Registration for all diploma programs', '2025-09-16', 'registration', NULL),
('IT Program Orientation', 'Welcome session for new IT students', '2025-09-20', 'orientation', 1),
('Mid-term Exam Week', 'Mid-semester examinations', '2025-10-15', 'exam', NULL),
('Business Career Fair', 'Job opportunities in business sector', '2025-10-20', 'registration', 2),
('Engineering Lab Demo', 'New equipment demonstration', '2025-09-25', 'orientation', 3);

-- Insert sample notifications
INSERT IGNORE INTO notifications (user_id, title, message, type) VALUES
((SELECT id FROM users WHERE username = 'student1'), 'Assignment Due Soon', 'Python Basic Exercises due in 2 days', 'academic'),
((SELECT id FROM users WHERE username = 'student1'), 'New Course Material', 'Web Development slides uploaded', 'academic'),
((SELECT id FROM users WHERE username = 'student2'), 'Registration Reminder', 'Don\'t forget to register for Semester 2', 'administrative'),
((SELECT id FROM users WHERE username = 'student3'), 'Lab Schedule Change', 'Engineering lab moved to Friday', 'academic');

-- Create indexes for better performance
CREATE INDEX idx_enrollments_student ON enrollments(student_id);
CREATE INDEX idx_assignments_course ON assignments(course_id);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_academic_events_date ON academic_events(event_date);
CREATE INDEX idx_assignment_submissions_student ON assignment_submissions(student_id);
