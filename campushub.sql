CREATE DATABASE IF NOT EXISTS campushub_db;
USE campushub_db;

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(100) NOT NULL,
    year VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL
);

CREATE TABLE IF NOT EXISTS library_books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    book_title VARCHAR(150) NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    category VARCHAR(100) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS book_issue (
    issue_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    issue_date DATE NOT NULL,
    return_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_issue_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE,
    CONSTRAINT fk_issue_book FOREIGN KEY (book_id) REFERENCES library_books(book_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS events (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO students (name, email, department, year, phone)
VALUES
    ('Aarav Sharma', 'aarav@example.com', 'BCA', '1st Year', '9876543210'),
    ('Diya Patel', 'diya@example.com', 'BSc IT', '2nd Year', '9123456780')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT IGNORE INTO library_books (book_id, book_title, author_name, category, quantity)
VALUES
    (1, 'Database System Concepts', 'Abraham Silberschatz', 'Academic', 5),
    (2, 'Let Us C', 'Yashavant Kanetkar', 'Programming', 3);

INSERT IGNORE INTO events (event_id, event_title, event_date, venue, description)
VALUES
    (1, 'Tech Fest', '2026-05-15', 'Main Auditorium', 'Annual technology event for students.'),
    (2, 'Library Orientation', '2026-05-20', 'Central Library', 'Introduction to library resources and issue system.');
