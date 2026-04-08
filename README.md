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

- `students`
- `library_books`
- `book_issue`
- `events`

## Setup Steps

1. Copy the `campushub` folder into `C:\xampp\htdocs\`.
2. Start Apache and MySQL from XAMPP Control Panel.
3. Open phpMyAdmin.
4. Import the file `campushub.sql`.
5. Open `http://localhost/campushub/` in your browser.

## Database Configuration

The default database connection inside `db_connect.php` is:

- Host: `localhost`
- Username: `root`
- Password: empty
- Database: `campushub`

Change these values if your MySQL setup is different.

## Technical Notes

- `students`, `library_books`, `book_issue`, and `events` are separate tables.
- Primary keys are used in each table.
- Foreign keys are used in `book_issue`.
- Prepared statements are used for insert operations.
- A `JOIN` query is used in the book issue module to display student and book data together.
- The issue system also updates the available book quantity.
