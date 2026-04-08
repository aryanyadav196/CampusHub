<?php
require_once "db_connect.php";

$pageTitle = "CampusHub Dashboard";
$basePath = "";

$studentCount = 0;
$bookCount = 0;
$issueCount = 0;
$eventCount = 0;

$studentResult = $conn->query("SELECT COUNT(*) AS total FROM students");
$bookResult = $conn->query("SELECT COUNT(*) AS total FROM library_books");
$issueResult = $conn->query("SELECT COUNT(*) AS total FROM book_issue");
$eventResult = $conn->query("SELECT COUNT(*) AS total FROM events");

if ($studentResult) {
    $studentCount = (int) $studentResult->fetch_assoc()["total"];
}

if ($bookResult) {
    $bookCount = (int) $bookResult->fetch_assoc()["total"];
}

if ($issueResult) {
    $issueCount = (int) $issueResult->fetch_assoc()["total"];
}

if ($eventResult) {
    $eventCount = (int) $eventResult->fetch_assoc()["total"];
}

require_once "includes/header.php";
?>

<section class="hero">
    <div>
        <p class="eyebrow">CampusHub</p>
        <h1>Unified campus operations for students, library services, and events.</h1>
        <p class="hero-text">
            CampusHub centralizes student administration, library circulation, and event scheduling in a single operational workspace.
        </p>
        <div class="hero-actions">
            <a class="btn" href="students/add_student.php">Register Student</a>
            <a class="btn btn-light" href="library/issue_book.php">Manage Circulation</a>
        </div>
    </div>
</section>

<section class="dashboard-grid">
    <article class="card stat-card">
        <h2><?php echo $studentCount; ?></h2>
        <p>Total Students</p>
    </article>

    <article class="card stat-card">
        <h2><?php echo $bookCount; ?></h2>
        <p>Total Books</p>
    </article>

    <article class="card stat-card">
        <h2><?php echo $issueCount; ?></h2>
        <p>Books Issued</p>
    </article>

    <article class="card stat-card">
        <h2><?php echo $eventCount; ?></h2>
        <p>Total Events</p>
    </article>
    </section>

<section class="dashboard-grid">
    <article class="card">
        <h2>Student Records</h2>
        <p>Maintain student profiles, academic details, and contact information.</p>
        <a href="students/add_student.php">Register Student</a>
        <a href="students/view_students.php">Student Directory</a>
    </article>

    <article class="card">
        <h2>Library Services</h2>
        <p>Manage catalog entries, monitor inventory, and process book circulation.</p>
        <a href="library/add_book.php">Add Book</a>
        <a href="library/view_books.php">Library Catalog</a>
        <a href="library/issue_book.php">Issue Book</a>
    </article>

    <article class="card">
        <h2>Event Scheduling</h2>
        <p>Coordinate campus events and publish schedules across the institution.</p>
        <a href="events/add_event.php">Create Event</a>
        <a href="events/view_events.php">Event Calendar</a>
    </article>
</section>

<?php require_once "includes/footer.php"; ?>
