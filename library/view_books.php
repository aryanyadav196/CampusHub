<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Library Catalog";
$pageKey = "library";
$basePath = "../";
$search = trim($_GET["q"] ?? "");
$category = trim($_GET["category"] ?? "");
$status = trim($_GET["status"] ?? "");
$collegeFilter = is_admin() ? (int) ($_GET["college_id"] ?? 0) : current_college_id();
$colleges = get_colleges($conn);

$where = [];
$types = "";
$params = [];

if ($search !== "") {
    $where[] = "(library_books.book_name LIKE ? OR library_books.author LIKE ?)";
    $types .= "ss";
    $like = "%" . $search . "%";
    array_push($params, $like, $like);
}
if ($category !== "") {
    $where[] = "library_books.category = ?";
    $types .= "s";
    $params[] = $category;
}
if ($status !== "") {
    $where[] = "library_books.status = ?";
    $types .= "s";
    $params[] = $status;
}
if ($collegeFilter > 0) {
    $where[] = "library_books.college_id = ?";
    $types .= "i";
    $params[] = $collegeFilter;
}

$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";
$sql = "
    SELECT library_books.*, colleges.college_name
    FROM library_books
    LEFT JOIN colleges ON colleges.college_id = library_books.college_id
    " . $whereSql . "
    ORDER BY library_books.book_id DESC
";
$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$categoryResult = $conn->query("SELECT DISTINCT category FROM library_books ORDER BY category ASC");
$categories = $categoryResult ? $categoryResult->fetch_all(MYSQLI_ASSOC) : [];

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Library Catalog</h1>
    <p>Search, filter, and review inventory, availability, and book status across colleges.</p>
</section>

<section class="table-card">
    <form class="filters-form" method="get">
        <div class="filter-row">
            <div class="filter-item search-input">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?php echo e($search); ?>" placeholder="Search title or author">
            </div>
            <div class="filter-item">
                <label for="category">Category</label>
                <select id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $row): ?>
                        <option value="<?php echo e($row["category"]); ?>" <?php echo $category === $row["category"] ? "selected" : ""; ?>>
                            <?php echo e($row["category"]); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-item">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="available" <?php echo $status === "available" ? "selected" : ""; ?>>Available</option>
                    <option value="issued" <?php echo $status === "issued" ? "selected" : ""; ?>>Issued</option>
                    <option value="returned" <?php echo $status === "returned" ? "selected" : ""; ?>>Returned</option>
                </select>
            </div>
            <?php if (is_admin()): ?>
                <div class="filter-item">
                    <label for="college_id">College</label>
                    <select id="college_id" name="college_id">
                        <option value="">All Colleges</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo (int) $college["college_id"]; ?>" <?php echo $collegeFilter === (int) $college["college_id"] ? "selected" : ""; ?>>
                                <?php echo e($college["college_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="filter-item action-group">
                <button type="submit">Apply</button>
                <a class="btn-light" href="view_books.php">Reset</a>
                <a class="btn-ghost" href="add_book.php">Add Book</a>
            </div>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>Category</th>
                        <th>Inventory</th>
                        <th>Status</th>
                        <?php if (is_admin()): ?><th>College</th><?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($row["book_name"]); ?></strong>
                                <div class="muted"><?php echo e($row["author"]); ?></div>
                            </td>
                            <td><?php echo e($row["category"]); ?></td>
                            <td><?php echo e($row["available_copies"]); ?> / <?php echo e($row["total_copies"]); ?> available</td>
                            <td><span class="<?php echo e(get_status_badge_class($row["status"])); ?>"><?php echo e(ucfirst($row["status"])); ?></span></td>
                            <?php if (is_admin()): ?><td><?php echo e($row["college_name"]); ?></td><?php endif; ?>
                            <td>
                                <div class="action-group">
                                    <a class="btn-light" href="edit_book.php?id=<?php echo (int) $row["book_id"]; ?>">Edit</a>
                                    <a class="btn-danger" href="delete_book.php?id=<?php echo (int) $row["book_id"]; ?>" onclick="return confirm('Delete this book record?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?php render_empty_state("No books found", "Try a different search or add a new library title."); ?>
    <?php endif; ?>
</section>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
