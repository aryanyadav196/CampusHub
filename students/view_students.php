<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Students";
$pageKey = "students";
$basePath = "../";
$perPage = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$search = trim($_GET['q'] ?? '');
$department = trim($_GET['department'] ?? '');
$collegeFilter = is_admin() ? (int) ($_GET['college_id'] ?? 0) : current_college_id();
$colleges = get_colleges($conn);

$where = [];
$types = '';
$params = [];

if ($search !== '') {
    $where[] = '(students.name LIKE ? OR students.email LIKE ? OR students.phone LIKE ?)';
    $types .= 'sss';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like);
}
if ($department !== '') {
    $where[] = 'students.department = ?';
    $types .= 's';
    $params[] = $department;
}
if ($collegeFilter > 0) {
    $where[] = 'students.college_id = ?';
    $types .= 'i';
    $params[] = $collegeFilter;
}

$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$totalRecords = count_table_rows($conn, 'students', $whereSql, $types, $params);
$totalPages = max(1, (int) ceil($totalRecords / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$dataSql = "SELECT students.*, colleges.college_name FROM students LEFT JOIN colleges ON colleges.college_id = students.college_id {$whereSql} ORDER BY students.student_id DESC LIMIT ? OFFSET ?";
$dataStmt = $conn->prepare($dataSql);
$dataTypes = $types . 'ii';
$dataParams = [...$params, $perPage, $offset];
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$result = $dataStmt->get_result();

$deptWhere = [];
$deptTypes = '';
$deptParams = [];
if ($collegeFilter > 0) {
    $deptWhere[] = 'college_id = ?';
    $deptTypes .= 'i';
    $deptParams[] = $collegeFilter;
} elseif (!is_admin()) {
    $deptWhere[] = 'college_id = ?';
    $deptTypes .= 'i';
    $deptParams[] = current_college_id();
}
$deptSql = 'SELECT DISTINCT department FROM students' . ($deptWhere ? ' WHERE ' . implode(' AND ', $deptWhere) : '') . ' ORDER BY department ASC';
$deptStmt = $conn->prepare($deptSql);
if ($deptTypes !== '') {
    $deptStmt->bind_param($deptTypes, ...$deptParams);
}
$deptStmt->execute();
$departments = $deptStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$deptStmt->close();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportSql = "SELECT students.student_id, students.name, students.email, students.department, students.year_level, students.phone, colleges.college_name FROM students LEFT JOIN colleges ON colleges.college_id = students.college_id {$whereSql} ORDER BY students.student_id DESC";
    $exportStmt = $conn->prepare($exportSql);
    if ($types !== '') {
        $exportStmt->bind_param($types, ...$params);
    }
    $exportStmt->execute();
    $exportResult = $exportStmt->get_result();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=students_export.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Department', 'Year', 'Phone', 'Campus']);
    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    $exportStmt->close();
    exit;
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Student Directory</h1>
        <p>Search, filter, export, and maintain student records with profile images and quick actions.</p>
    </div>
    <div class="toolbar-actions">
        <a class="btn" href="add_student.php">Add Student</a>
        <a class="btn-light" href="?<?php echo e(build_query_string(array_merge($_GET, ['export' => 'csv', 'page' => null]))); ?>">Export CSV</a>
    </div>
</section>

<nav class="module-tabs">
    <a class="module-tab active" href="view_students.php">Directory</a>
    <a class="module-tab" href="add_student.php">Register Student</a>
</nav>

<section class="table-card">
    <form class="filters-form" method="get">
        <div class="filter-row">
            <div class="filter-item search-input">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?php echo e($search); ?>" placeholder="Search name, email, or phone">
            </div>
            <div class="filter-item">
                <label for="department">Department</label>
                <select id="department" name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?php echo e($dept['department']); ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>><?php echo e($dept['department']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (is_admin()): ?>
                <div class="filter-item">
                    <label for="college_id">Campus</label>
                    <select id="college_id" name="college_id">
                        <option value="">All Campuses</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo (int) $college['college_id']; ?>" <?php echo $collegeFilter === (int) $college['college_id'] ? 'selected' : ''; ?>><?php echo e($college['college_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="filter-item action-group">
                <button type="submit">Apply</button>
                <a class="btn-light" href="view_students.php">Reset</a>
            </div>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Phone</th>
                        <?php if (is_admin()): ?><th>Campus</th><?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="profile-cell">
                                    <?php if (!empty($row['profile_photo'])): ?>
                                        <img class="avatar" src="<?php echo e($basePath . $row['profile_photo']); ?>" alt="<?php echo e($row['name']); ?>">
                                    <?php else: ?>
                                        <div class="avatar"></div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?php echo e($row['name']); ?></strong>
                                        <div class="muted"><?php echo e($row['email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo e($row['department']); ?></td>
                            <td><span class="chip"><?php echo e($row['year_level']); ?></span></td>
                            <td><?php echo e($row['phone']); ?></td>
                            <?php if (is_admin()): ?><td><?php echo e($row['college_name']); ?></td><?php endif; ?>
                            <td>
                                <div class="action-group">
                                    <a class="btn-light" href="edit_student.php?id=<?php echo (int) $row['student_id']; ?>">Edit</a>
                                    <a class="btn-danger" href="delete_student.php?id=<?php echo (int) $row['student_id']; ?>" onclick="return confirm('Delete this student record?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php $query = $_GET; $query['page'] = $i; ?>
                    <a class="<?php echo $i === $page ? 'active' : ''; ?>" href="?<?php echo e(build_query_string($query)); ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <?php render_empty_state('No students found', 'Adjust the filters or register a new student to populate the directory.', 'add_student.php', 'Add Student'); ?>
    <?php endif; ?>
</section>

<?php
$dataStmt->close();
require_once "../includes/footer.php";
?>
