<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_login();

$pageTitle = "Dashboard";
$pageKey = "dashboard";
$basePath = "";
$loadCharts = true;
$scopeClause = is_admin() ? "" : " WHERE college_id = " . current_college_id();
$issueClause = is_admin() ? " WHERE status = 'issued'" : " WHERE college_id = " . current_college_id() . " AND status = 'issued'";
$eventClause = is_admin() ? " WHERE event_date >= CURDATE()" : " WHERE college_id = " . current_college_id() . " AND event_date >= CURDATE()";

$studentCount = (int) (($conn->query("SELECT COUNT(*) AS total FROM students" . $scopeClause)?->fetch_assoc()["total"]) ?? 0);
$bookCount = (int) (($conn->query("SELECT COUNT(*) AS total FROM library_books" . $scopeClause)?->fetch_assoc()["total"]) ?? 0);
$issueCount = (int) (($conn->query("SELECT COUNT(*) AS total FROM book_issue" . $issueClause)?->fetch_assoc()["total"]) ?? 0);
$upcomingEventCount = (int) (($conn->query("SELECT COUNT(*) AS total FROM events" . $eventClause)?->fetch_assoc()["total"]) ?? 0);

$departmentResult = $conn->query("
    SELECT department, COUNT(*) AS total
    FROM students
    " . $scopeClause . "
    GROUP BY department
    ORDER BY total DESC, department ASC
    LIMIT 6
");
$bookCategoryResult = $conn->query("
    SELECT category, COUNT(*) AS total
    FROM library_books
    " . $scopeClause . "
    GROUP BY category
    ORDER BY total DESC, category ASC
    LIMIT 6
");

$studentChartLabels = [];
$studentChartData = [];
if ($departmentResult) {
    while ($row = $departmentResult->fetch_assoc()) {
        $studentChartLabels[] = $row["department"];
        $studentChartData[] = (int) $row["total"];
    }
}

$bookChartLabels = [];
$bookChartData = [];
if ($bookCategoryResult) {
    while ($row = $bookCategoryResult->fetch_assoc()) {
        $bookChartLabels[] = $row["category"];
        $bookChartData[] = (int) $row["total"];
    }
}

$recentEvents = $conn->query("
    SELECT event_title, event_date, venue
    FROM events
    " . (is_admin() ? "" : " WHERE college_id = " . current_college_id()) . "
    ORDER BY event_date ASC
    LIMIT 4
");

require_once "includes/header.php";
?>

<section class="hero-banner">
    <p class="hero-kicker">Modern Campus Command Center</p>
    <h2>Run students, library services, and events from one professional multi-college workspace.</h2>
    <p>
        CampusHub now supports college-based data isolation, role-based access, dark mode, analytics, and cleaner workflows designed for practical demos and real SaaS-style presentation.
    </p>
    <div class="hero-actions">
        <a class="btn" href="students/add_student.php">Add Student</a>
        <a class="btn-light" href="library/issue_book.php">Issue Book</a>
        <a class="btn-ghost" href="events/add_event.php">Create Event</a>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <p class="stat-label">Total Students</p>
        <h3 class="stat-value"><?php echo $studentCount; ?></h3>
        <span class="stat-trend">Student records</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Total Books</p>
        <h3 class="stat-value"><?php echo $bookCount; ?></h3>
        <span class="stat-trend">Catalog inventory</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Issued Books</p>
        <h3 class="stat-value"><?php echo $issueCount; ?></h3>
        <span class="stat-trend">Active circulation</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Upcoming Events</p>
        <h3 class="stat-value"><?php echo $upcomingEventCount; ?></h3>
        <span class="stat-trend">Scheduled soon</span>
    </article>
</section>

<section class="chart-grid section-heading">
    <article class="chart-card">
        <h2>Students by Department</h2>
        <p class="muted">Live distribution of registered students.</p>
        <div class="chart-canvas-wrap">
            <canvas id="studentsChart"></canvas>
        </div>
    </article>
    <article class="chart-card">
        <h2>Books by Category</h2>
        <p class="muted">Library inventory grouped by category.</p>
        <div class="chart-canvas-wrap">
            <canvas id="booksChart"></canvas>
        </div>
    </article>
</section>

<section class="split-layout section-heading">
    <article class="panel">
        <h2>Quick Access</h2>
        <div class="card-grid">
            <article class="metric-card">
                <h3 class="card-title">Students</h3>
                <p class="muted">Manage registrations, photos, search, export, and edits.</p>
                <div class="card-actions">
                    <a class="btn-light" href="students/view_students.php">View</a>
                    <a class="btn-ghost" href="students/add_student.php">Add</a>
                </div>
            </article>
            <article class="metric-card">
                <h3 class="card-title">Library</h3>
                <p class="muted">Track books, availability, issue status, and returns.</p>
                <div class="card-actions">
                    <a class="btn-light" href="library/view_books.php">Catalog</a>
                    <a class="btn-ghost" href="library/issue_book.php">Circulation</a>
                </div>
            </article>
            <article class="metric-card">
                <h3 class="card-title">Events</h3>
                <p class="muted">Publish event cards, posters, venues, and schedules.</p>
                <div class="card-actions">
                    <a class="btn-light" href="events/view_events.php">Calendar</a>
                    <a class="btn-ghost" href="events/add_event.php">Create</a>
                </div>
            </article>
        </div>
    </article>

    <article class="panel">
        <h2>Upcoming Schedule</h2>
        <?php if ($recentEvents && $recentEvents->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Venue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $recentEvents->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($event["event_title"]); ?></td>
                            <td><?php echo e(format_date_label($event["event_date"])); ?></td>
                            <td><?php echo e($event["venue"]); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <?php render_empty_state("No upcoming events", "Create an event to populate the campus calendar."); ?>
        <?php endif; ?>
    </article>
</section>

<script>
const studentsChartCtx = document.getElementById('studentsChart');
if (studentsChartCtx && window.Chart) {
    new Chart(studentsChartCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($studentChartLabels); ?>,
            datasets: [{
                label: 'Students',
                data: <?php echo json_encode($studentChartData); ?>,
                backgroundColor: ['#2563eb', '#0ea5e9', '#14b8a6', '#f59e0b', '#ec4899', '#8b5cf6'],
                borderRadius: 10
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}

const booksChartCtx = document.getElementById('booksChart');
if (booksChartCtx && window.Chart) {
    new Chart(booksChartCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($bookChartLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($bookChartData); ?>,
                backgroundColor: ['#1d4ed8', '#0284c7', '#0f766e', '#ca8a04', '#be185d', '#6d28d9']
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}
</script>

<?php require_once "includes/footer.php"; ?>
