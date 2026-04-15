# CampusHub

CampusHub is a web-based campus management system built using PHP, MySQL, HTML, and CSS.

## Modules

- Student Management
- Library Management
- Event Management

## Folder Structure

```text
campushub/
|-- index.php
|-- db_connect.php
|-- campushub.sql
|-- README.md
|-- css/
|   `-- style.css
|-- includes/
|   |-- header.php
|   `-- footer.php
|-- students/
|   |-- add_student.php
|   `-- view_students.php
|-- library/
|   |-- add_book.php
|   |-- view_books.php
|   `-- issue_book.php
`-- events/
    |-- add_event.php
    `-- view_events.php
```

## Database Tables

- `users`
- `colleges`
- `students`
- `library_books`
- `book_issue`
- `events`

## Setup Steps

1. Copy the `campushub` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open phpMyAdmin.
4. Create or select the database `campushub_db`.
5. Import the file `campushub.sql` or `campushub_complete.sql`.
6. Open `http://localhost/campushub/` in your browser.

## Database Configuration

The application reads these values from `.env`:

- Host: `localhost`
- Username: `root`
- Password: value of `DB_PASS`
- Database: `campushub_db`

Change these values if your MySQL setup is different.

## Technical Notes

- `users`, `colleges`, `students`, `library_books`, `book_issue`, and `events` are required tables.
- Primary keys are used in each table.
- Foreign keys are used between campus, student, library, event, and issue records.
- Prepared statements are used for insert operations.
- The login system requires the `users.password_hash` column and uses `password_verify()`.
- The issue system updates the available book quantity and book status.
