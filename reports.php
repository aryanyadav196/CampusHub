<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_login();

$pageTitle = "Reports";
$pageKey = "reports";
$basePath = "";
$loadCharts = true;
$scopeWhere = is_admin() ? "" : " WHERE college_id = " . current_college_id();

$studentCount = count_table_rows($conn, "students", $scopeWhere);
$bookCount = count_table_rows($conn, "library_books", $scopeWhere);
$issuedCount = count_table_rows($conn, "book_issue", is_admin() ? " WHERE status = 'issued'" : " WHERE status = 'issued' AND college_id = " . current_college_id());
$returnedCount = count_table_rows($conn, "book_issue", is_admin() ? " WHERE status = 'returned'" : " WHERE status = 'returned' AND college_id = " . current_college_id());
$eventCount = count_table_rows($conn, "events", $scopeWhere);

$topDepartments = $conn->query("SELECT department, COUNT(*) AS total FROM students" . $scopeWhere . " GROUP BY department ORDER BY total DESC, department ASC LIMIT 5");
$bookStatuses = $conn->query("SELECT status, COUNT(*) AS total FROM library_books" . $scopeWhere . " GROUP BY status ORDER BY total DESC");
$eventsByMonth = $conn->query("SELECT DATE_FORMAT(event_date, '%b %Y') AS month_label, COUNT(*) AS total FROM events" . $scopeWhere . " GROUP BY YEAR(event_date), MONTH(event_date) ORDER BY YEAR(event_date), MONTH(event_date) LIMIT 6");
$recentExportData = $conn->query("SELECT students.name, students.department, students.year_level, students.phone, colleges.college_name FROM students LEFT JOIN colleges ON colleges.college_id = students.college_id" . $scopeWhere . " ORDER BY students.student_id DESC LIMIT 8");

$deptLabels = [];
$deptValues = [];
if ($topDepartments) {
    while ($row = $topDepartments->fetch_assoc()) {
        $deptLabels[] = $row['department'];
        $deptValues[] = (int) $row['total'];
    }
}

$statusLabels = [];
$statusValues = [];
if ($bookStatuses) {
    while ($row = $bookStatuses->fetch_assoc()) {
        $statusLabels[] = ucfirst($row['status']);
        $statusValues[] = (int) $row['total'];
    }
}

$monthLabels = [];
$monthValues = [];
if ($eventsByMonth) {
    while ($row = $eventsByMonth->fetch_assoc()) {
        $monthLabels[] = $row['month_label'];
        $monthValues[] = (int) $row['total'];
    }
}

require_once "includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Reports</h1>
        <p>Monitor enrollment, circulation patterns, and event activity from one reporting view.</p>
    </div>
    <div class="toolbar-actions">
        <a class="btn" href="students/view_students.php?export=csv">Export Students</a>
        <a class="btn-light" href="library/view_books.php">Open Library</a>
    </div>
</section>

<section class="summary-grid">
    <article class="summary-card">
        <p class="stat-label">Student Records</p>
        <h2 class="stat-value"><?php echo $studentCount; ?></h2>
        <span class="stat-trend">Enrollment base</span>
    </article>
    <article class="summary-card">
        <p class="stat-label">Books Issued / Returned</p>
        <h2 class="stat-value"><?php echo $issuedCount; ?> / <?php echo $returnedCount; ?></h2>
        <span class="stat-trend">Circulation history</span>
    </article>
    <article class="summary-card">
        <p class="stat-label">Books / Events</p>
        <h2 class="stat-value"><?php echo $bookCount; ?> / <?php echo $eventCount; ?></h2>
        <span class="stat-trend">Operational coverage</span>
    </article>
</section>

<section class="chart-grid" style="margin-top: 24px;">
    <article class="chart-card">
        <h2>Top Departments</h2>
        <p class="muted">Highest-volume academic streams.</p>
        <div class="chart-canvas-wrap"><canvas id="reportDeptChart"></canvas></div>
    </article>
    <article class="chart-card">
        <h2>Book Status Mix</h2>
        <p class="muted">Current availability and circulation mix.</p>
        <div class="chart-canvas-wrap"><canvas id="reportStatusChart"></canvas></div>
    </article>
</section>

<section class="split-layout" style="margin-top: 24px;">
    <article class="chart-card">
        <h2>Events by Month</h2>
        <p class="muted">Calendar activity trend over recent periods.</p>
        <div class="chart-canvas-wrap"><canvas id="reportEventsChart"></canvas></div>
    </article>
    <article class="insight-card">
        <h2>Operational Notes</h2>
        <ul class="insight-list">
            <li>Keep enrollment data current so department summaries remain accurate.</li>
            <li>High issued volume with low available copies signals catalog expansion needs.</li>
            <li>Use event trends to balance academic, cultural, and service activities through the term.</li>
            <li>CSV export is available from the student directory for offline review and audits.</li>
        </ul>
    </article>
</section>

<section class="table-card" style="margin-top: 24px;">
    <div class="page-heading">
        <div>
            <h2>Latest Student Records</h2>
            <p>Quick review list for recent changes.</p>
        </div>
    </div>
    <?php if ($recentExportData && $recentExportData->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Phone</th>
                        <?php if (is_admin()): ?><th>Campus</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $recentExportData->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($row['name']); ?></td>
                            <td><?php echo e($row['department']); ?></td>
                            <td><?php echo e($row['year_level']); ?></td>
                            <td><?php echo e($row['phone']); ?></td>
                            <?php if (is_admin()): ?><td><?php echo e($row['college_name']); ?></td><?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?php render_empty_state("No records available", "Student records will appear here once they are added."); ?>
    <?php endif; ?>
</section>

<script>
const reportDeptCtx = document.getElementById('reportDeptChart');
if (reportDeptCtx && window.Chart) {
    new Chart(reportDeptCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($deptLabels); ?>,
            datasets: [{
                label: 'Students',
                data: <?php echo json_encode($deptValues); ?>,
                backgroundColor: '#1e66f5',
                borderRadius: 12
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
}

const reportStatusCtx = document.getElementById('reportStatusChart');
if (reportStatusCtx && window.Chart) {
    new Chart(reportStatusCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($statusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($statusValues); ?>,
                backgroundColor: ['#0f9f62', '#d08d12', '#1e66f5']
            }]
        },
        options: { maintainAspectRatio: false }
    });
}

const reportEventsCtx = document.getElementById('reportEventsChart');
if (reportEventsCtx && window.Chart) {
    new Chart(reportEventsCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($monthLabels); ?>,
            datasets: [{
                label: 'Events',
                data: <?php echo json_encode($monthValues); ?>,
                fill: true,
                backgroundColor: 'rgba(30, 102, 245, 0.14)',
                borderColor: '#12b0a2',
                tension: 0.35
            }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });
}
</script>

<?php require_once "includes/footer.php"; ?>
