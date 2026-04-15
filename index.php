<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_login();

$pageTitle = "Dashboard";
$pageKey = "dashboard";
$basePath = "";
$loadCharts = true;
$scopeWhere = is_admin() ? "" : " WHERE college_id = " . current_college_id();
$scopeAnd = is_admin() ? "" : " AND college_id = " . current_college_id();

$studentCount = count_table_rows($conn, "students", $scopeWhere);
$bookCount = count_table_rows($conn, "library_books", $scopeWhere);
$issueCount = count_table_rows($conn, "book_issue", is_admin() ? " WHERE status = 'issued'" : " WHERE status = 'issued'" . $scopeAnd);
$upcomingEventCount = count_table_rows($conn, "events", is_admin() ? " WHERE event_date >= CURDATE()" : " WHERE event_date >= CURDATE()" . $scopeAnd);

$departmentResult = $conn->query("SELECT department, COUNT(*) AS total FROM students" . $scopeWhere . " GROUP BY department ORDER BY total DESC, department ASC LIMIT 6");
$bookCategoryResult = $conn->query("SELECT category, COUNT(*) AS total FROM library_books" . $scopeWhere . " GROUP BY category ORDER BY total DESC, category ASC LIMIT 6");
$recentStudents = $conn->query("SELECT name, department, year, profile_photo FROM students" . $scopeWhere . " ORDER BY student_id DESC LIMIT 4");
$recentEvents = $conn->query("SELECT event_name, event_date, venue, poster_image FROM events" . (is_admin() ? "" : " WHERE college_id = " . current_college_id()) . " ORDER BY event_date ASC, event_id DESC LIMIT 3");
$circulationSnapshot = $conn->query("SELECT book_issue.status, students.name, library_books.book_name, book_issue.expected_return_date FROM book_issue INNER JOIN students ON students.student_id = book_issue.student_id INNER JOIN library_books ON library_books.book_id = book_issue.book_id" . (is_admin() ? "" : " WHERE book_issue.college_id = " . current_college_id()) . " ORDER BY book_issue.issue_id DESC LIMIT 4");

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

require_once "includes/header.php";
?>

<section class="hero-banner">
    <p class="hero-kicker">Operational Overview</p>
    <h2>Keep student services, library circulation, and campus events in sync.</h2>
    <p>
        Monitor daily activity, move between records quickly, and keep every campus unit operating from one clear interface.
    </p>
    <div class="hero-actions">
        <a class="btn" href="students/add_student.php">New Student</a>
        <a class="btn-light" href="library/issue_book.php">Issue Book</a>
        <a class="btn-ghost" href="events/add_event.php">Schedule Event</a>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card">
        <p class="stat-label">Total Students</p>
        <h3 class="stat-value"><?php echo $studentCount; ?></h3>
        <span class="stat-trend">Active profiles</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Books Cataloged</p>
        <h3 class="stat-value"><?php echo $bookCount; ?></h3>
        <span class="stat-trend">Library inventory</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Books Issued</p>
        <h3 class="stat-value"><?php echo $issueCount; ?></h3>
        <span class="stat-trend">Circulation in progress</span>
    </article>
    <article class="stat-card">
        <p class="stat-label">Upcoming Events</p>
        <h3 class="stat-value"><?php echo $upcomingEventCount; ?></h3>
        <span class="stat-trend">Calendar items ahead</span>
    </article>
</section>

<section class="chart-grid" style="margin-top: 24px;">
    <article class="chart-card">
        <h2>Student Distribution</h2>
        <p class="muted">Enrollment spread across departments.</p>
        <div class="chart-canvas-wrap">
            <canvas id="studentsChart"></canvas>
        </div>
    </article>
    <article class="chart-card">
        <h2>Library Categories</h2>
        <p class="muted">Book inventory grouped by category.</p>
        <div class="chart-canvas-wrap">
            <canvas id="booksChart"></canvas>
        </div>
    </article>
</section>

<section class="split-layout" style="margin-top: 24px;">
    <article class="panel">
        <div class="page-heading">
            <div>
                <h2>Recent Student Activity</h2>
                <p>Recently added records and their academic track.</p>
            </div>
            <a class="btn-light" href="students/view_students.php">Open Directory</a>
        </div>
        <div class="card-grid">
            <?php if ($recentStudents && $recentStudents->num_rows > 0): ?>
                <?php while ($student = $recentStudents->fetch_assoc()): ?>
                    <article class="metric-card">
                        <div class="profile-cell">
                            <?php if (!empty($student["profile_photo"])): ?>
                                <img class="avatar large" src="<?php echo e($basePath . $student["profile_photo"]); ?>" alt="<?php echo e($student["name"]); ?>">
                            <?php else: ?>
                                <div class="avatar large"></div>
                            <?php endif; ?>
                            <div>
                                <strong><?php echo e($student["name"]); ?></strong>
                                <div class="muted"><?php echo e($student["department"]); ?></div>
                            </div>
                        </div>
                        <span class="chip" style="margin-top: 16px;"><?php echo e($student["year_level"]); ?></span>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <?php render_empty_state("No student records yet", "Create the first student profile to populate the dashboard.", "students/add_student.php", "Add Student"); ?>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="page-heading">
            <div>
                <h2>Calendar Preview</h2>
                <p>Next events scheduled across the campus calendar.</p>
            </div>
            <a class="btn-light" href="events/view_events.php">View Calendar</a>
        </div>
        <div class="event-grid" style="grid-template-columns: 1fr;">
            <?php if ($recentEvents && $recentEvents->num_rows > 0): ?>
                <?php while ($event = $recentEvents->fetch_assoc()): ?>
                    <article class="event-card">
                        <div class="event-card-image">
                            <?php if (!empty($event["poster_image"])): ?>
                                <img src="<?php echo e($basePath . $event["poster_image"]); ?>" alt="<?php echo e($event["event_title"]); ?>">
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><?php echo e($event["event_title"]); ?></h2>
                            <div class="event-meta">
                                <span class="chip"><?php echo e(format_date_label($event["event_date"])); ?></span>
                                <span class="chip"><?php echo e($event["venue"]); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <?php render_empty_state("No upcoming events", "Add an event to surface it here.", "events/add_event.php", "Create Event"); ?>
            <?php endif; ?>
        </div>
    </article>
</section>

<section class="split-layout" style="margin-top: 24px;">
    <article class="table-card">
        <div class="page-heading">
            <div>
                <h2>Circulation Snapshot</h2>
                <p>Current issue records and due dates.</p>
            </div>
            <a class="btn-light" href="library/issue_book.php">Open Circulation</a>
        </div>
        <?php if ($circulationSnapshot && $circulationSnapshot->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Book</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $circulationSnapshot->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo e($item["name"]); ?></td>
                                <td><?php echo e($item["book_name"]); ?></td>
                                <td><?php echo e(format_date_label($item["expected_return_date"])); ?></td>
                                <td><span class="<?php echo e(get_status_badge_class($item["status"])); ?>"><?php echo e(ucfirst($item["status"])); ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php render_empty_state("No circulation records", "Issue a book to see recent activity.", "library/issue_book.php", "Issue Book"); ?>
        <?php endif; ?>
    </article>

    <article class="insight-card">
        <h2>Operations Focus</h2>
        <ul class="insight-list">
            <li>Use the student directory to search, edit, export, and manage profile images.</li>
            <li>Track inventory from the library catalog and mark circulation records as returned.</li>
            <li>Publish events with posters so the schedule feels editorial rather than administrative.</li>
            <li>Open the assistant from the bottom-right corner for quick questions about live data.</li>
        </ul>
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
                backgroundColor: ['#1e66f5', '#12b0a2', '#4f46e5', '#e8882e', '#d9475c', '#0f9f62'],
                borderRadius: 12
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            },
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
                backgroundColor: ['#1e66f5', '#12b0a2', '#4f46e5', '#e8882e', '#d9475c', '#0f9f62']
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
