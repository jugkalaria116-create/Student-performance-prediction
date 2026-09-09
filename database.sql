-- =====================================================
-- Student Performance Predictor & Analytics System
-- Database Schema + Sample Data
-- Import this file through phpMyAdmin (XAMPP)
-- =====================================================

CREATE DATABASE IF NOT EXISTS student_predictor
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

USE student_predictor;

-- -----------------------------------------------------
-- Table: courses
-- -----------------------------------------------------
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(50) NOT NULL UNIQUE
);

-- -----------------------------------------------------
-- Table: students
-- -----------------------------------------------------
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    course_id INT,
    semester INT CHECK (semester BETWEEN 1 AND 12),

    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- -----------------------------------------------------
-- Table: student_performance
-- Note: multiple prediction rows per student/semester are
-- allowed on purpose, so prediction history is preserved.
-- -----------------------------------------------------
CREATE TABLE student_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,

    student_id INT NOT NULL,
    semester INT NOT NULL,

    study_hours_per_day DECIMAL(4,2)
        CHECK (study_hours_per_day BETWEEN 0 AND 24),

    attendance DECIMAL(5,2)
        CHECK (attendance BETWEEN 0 AND 100),

    previous_marks DECIMAL(5,2)
        CHECK (previous_marks BETWEEN 0 AND 100),

    assignment_marks DECIMAL(5,2)
        CHECK (assignment_marks BETWEEN 0 AND 100),

    internal_marks DECIMAL(5,2)
        CHECK (internal_marks BETWEEN 0 AND 100),

    predicted_marks DECIMAL(5,2)
        CHECK (predicted_marks BETWEEN 0 AND 100),

    performance ENUM('Excellent', 'Good', 'Average', 'Poor') DEFAULT NULL,

    result ENUM('Pass', 'Fail') DEFAULT NULL,

    model_version VARCHAR(20) DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,

    INDEX idx_performance (performance),
    INDEX idx_result (result),
    INDEX idx_student_semester (student_id, semester),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Courses
INSERT INTO courses (course_name) VALUES
('BCA'),
('MCA'),
('BSc Computer Science'),
('BBA'),
('MBA');

-- Students (10 total)
-- Note: emails are all unique; leave NULL (not empty string) if not supplied.
INSERT INTO students (name, email, course_id, semester) VALUES
('Rahul Patel',        'rahul.patel@example.com',        2, 3),  -- MCA
('Priya Sharma',       'priya.sharma@example.com',       1, 2),  -- BCA
('Aman Verma',         'aman.verma@example.com',         3, 4),  -- BSc CS
('Sneha Reddy',        'sneha.reddy@example.com',        1, 1),  -- BCA
('Karan Mehta',        'karan.mehta@example.com',        4, 3),  -- BBA
('Divya Nair',         'divya.nair@example.com',         5, 2),  -- MBA
('Rohan Joshi',        'rohan.joshi@example.com',        2, 1),  -- MCA
('Ananya Gupta',       'ananya.gupta@example.com',       3, 2),  -- BSc CS
('Vikram Singh',       'vikram.singh@example.com',       1, 3),  -- BCA
('Isha Kapoor',        'isha.kapoor@example.com',        4, 1);  -- BBA

-- Performance / prediction history
-- Rahul (student_id 1) - 3 semesters, improving trend
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(1, 1, 3.50, 72.00, 58.00, 65.00, 60.00, 62.35, 'Good', 'Pass', 'v1.0', '2025-08-10 10:00:00'),
(1, 2, 4.00, 78.00, 62.00, 70.00, 68.00, 67.60, 'Good', 'Pass', 'v1.0', '2026-01-12 10:00:00'),
(1, 3, 5.00, 85.00, 68.00, 75.00, 72.00, 73.60, 'Good', 'Pass', 'v1.0', '2026-06-15 10:00:00');

-- Priya (student_id 2) - 2 semesters
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(2, 1, 6.00, 90.00, 80.00, 88.00, 85.00, 84.55, 'Excellent', 'Pass', 'v1.0', '2025-08-11 09:30:00'),
(2, 2, 6.50, 92.00, 83.00, 90.00, 88.00, 86.65, 'Excellent', 'Pass', 'v1.0', '2026-01-14 09:30:00');

-- Aman (student_id 3) - 3 semesters, declining trend
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(3, 1, 5.00, 88.00, 75.00, 80.00, 78.00, 78.10, 'Good', 'Pass', 'v1.0', '2025-08-09 11:00:00'),
(3, 2, 3.00, 70.00, 60.00, 62.00, 58.00, 58.60, 'Average', 'Pass', 'v1.0', '2026-01-10 11:00:00'),
(3, 3, 1.50, 55.00, 42.00, 45.00, 40.00, 40.30, 'Average', 'Pass', 'v1.0', '2026-06-12 11:00:00');

-- Sneha (student_id 4) - 1 semester, weak performance
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(4, 1, 1.00, 45.00, 35.00, 38.00, 30.00, 33.70, 'Poor', 'Fail', 'v1.0', '2025-08-13 14:00:00');

-- Karan (student_id 5) - 2 semesters
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(5, 1, 4.50, 80.00, 65.00, 70.00, 66.00, 67.90, 'Good', 'Pass', 'v1.0', '2025-08-14 12:00:00'),
(5, 2, 2.00, 60.00, 50.00, 52.00, 48.00, 47.20, 'Average', 'Pass', 'v1.0', '2026-01-16 12:00:00');

-- Divya (student_id 6) - 2 semesters, two predictions in same semester (history demo)
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(6, 1, 3.00, 68.00, 55.00, 58.00, 52.00, 55.70, 'Average', 'Pass', 'v1.0', '2025-08-15 09:00:00'),
(6, 1, 4.00, 75.00, 60.00, 64.00, 60.00, 62.60, 'Good', 'Pass', 'v1.0', '2025-09-02 09:00:00'),
(6, 2, 5.00, 82.00, 70.00, 72.00, 68.00, 71.90, 'Good', 'Pass', 'v1.0', '2026-01-18 09:00:00');

-- Rohan (student_id 7) - 1 semester
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(7, 1, 0.50, 40.00, 30.00, 32.00, 28.00, 29.40, 'Poor', 'Fail', 'v1.0', '2025-08-16 15:30:00');

-- Ananya (student_id 8) - no performance records yet (tests empty state)

-- Vikram (student_id 9) - 1 semester
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(9, 1, 6.00, 95.00, 88.00, 92.00, 90.00, 90.60, 'Excellent', 'Pass', 'v1.0', '2025-08-17 10:15:00');

-- Isha (student_id 10) - 1 semester, borderline pass/fail
INSERT INTO student_performance
(student_id, semester, study_hours_per_day, attendance, previous_marks, assignment_marks, internal_marks, predicted_marks, performance, result, model_version, created_at) VALUES
(10, 1, 2.00, 42.00, 39.00, 40.00, 38.00, 39.90, 'Poor', 'Fail', 'v1.0', '2025-08-18 13:45:00');
