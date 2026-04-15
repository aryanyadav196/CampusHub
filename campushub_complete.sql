CREATE DATABASE IF NOT EXISTS campushub_db;
USE campushub_db;

DROP TABLE IF EXISTS book_issue;
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS library_books;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS colleges;

CREATE TABLE colleges (
    college_id INT AUTO_INCREMENT PRIMARY KEY,
    college_name VARCHAR(150) NOT NULL,
    college_code VARCHAR(20) NOT NULL UNIQUE,
    city VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'college_admin') NOT NULL DEFAULT 'college_admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_college FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE SET NULL
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    year_level VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_college FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

CREATE TABLE library_books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    book_name VARCHAR(150) NOT NULL,
    author VARCHAR(120) NOT NULL,
    category VARCHAR(100) NOT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    status ENUM('available', 'issued', 'returned') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_books_college FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

CREATE TABLE events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    event_title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    poster_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_events_college FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE
);

CREATE TABLE book_issue (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    college_id INT NOT NULL,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL,
    expected_return_date DATE NOT NULL,
    actual_return_date DATE DEFAULT NULL,
    status ENUM('issued', 'returned') NOT NULL DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_issue_college FOREIGN KEY (college_id) REFERENCES colleges(college_id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_book FOREIGN KEY (book_id) REFERENCES library_books(book_id) ON DELETE CASCADE
);

INSERT INTO colleges (college_id, college_name, college_code, city) VALUES
    (1, 'North Valley College', 'NVC', 'Delhi'),
    (2, 'Westbridge Institute', 'WBI', 'Mumbai');

INSERT INTO users (college_id, name, email, password_hash, role) VALUES
    (NULL, 'System Admin', 'admin@campushub.com', '$2y$12$fznS8zGrAKVI8nXo7EQLpevnca96TQWiRYBCIQbaP/vvI9ZI8alZ.', 'admin'),
    (1, 'North Valley Admin', 'north@campushub.com', '$2y$12$IkXti5.yVzxCmS4YeXGz9Ox1h4xaiqxdUJi6hfuvOmbxJjGWl.PA2', 'college_admin'),
    (2, 'Westbridge Admin', 'west@campushub.com', '$2y$12$IkXti5.yVzxCmS4YeXGz9Ox1h4xaiqxdUJi6hfuvOmbxJjGWl.PA2', 'college_admin');

INSERT INTO students (college_id, name, email, department, year_level, phone) VALUES
    (1, 'Aarav Sharma', 'aarav@example.com', 'BCA', '1st Year', '9876543210'),
    (1, 'Diya Patel', 'diya@example.com', 'BSc IT', '2nd Year', '9123456780'),
    (2, 'Rohan Kapoor', 'rohan@example.com', 'MBA', '1st Year', '9988776655'),
    (2, 'Sneha Rao', 'sneha@example.com', 'BCom', '3rd Year', '9345678901');

INSERT INTO library_books (college_id, book_name, author, category, total_copies, available_copies, status) VALUES
    (1, 'Database System Concepts', 'Abraham Silberschatz', 'Academic', 5, 4, 'issued'),
    (1, 'Let Us C', 'Yashavant Kanetkar', 'Programming', 3, 3, 'available'),
    (2, 'Principles of Management', 'Harold Koontz', 'Management', 4, 4, 'returned'),
    (2, 'Financial Accounting', 'T. S. Grewal', 'Commerce', 6, 6, 'available');

INSERT INTO events (college_id, event_title, event_date, venue, description) VALUES
    (1, 'Tech Fest 2026', '2026-05-15', 'Main Auditorium', 'Annual technology showcase featuring coding contests, innovation showcases, and keynote sessions.'),
    (1, 'Library Orientation', '2026-05-20', 'Central Library', 'Introductory campus library walkthrough for first-year students.'),
    (2, 'Management Conclave', '2026-05-25', 'Seminar Hall A', 'Panel discussions and workshops on business strategy and entrepreneurship.'),
    (2, 'Cultural Evening', '2026-06-02', 'Open Air Theatre', 'Music, dance, and inter-college performances with student clubs.');

INSERT INTO book_issue (college_id, student_id, book_id, issue_date, expected_return_date, actual_return_date, status) VALUES
    (1, 1, 1, '2026-04-08', '2026-04-18', NULL, 'issued'),
    (2, 3, 3, '2026-04-01', '2026-04-10', '2026-04-09', 'returned');
