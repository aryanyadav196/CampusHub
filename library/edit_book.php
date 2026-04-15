<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Edit Book";
$pageKey = "library";
$basePath = "../";
$errorMessage = "";
$bookId = (int) ($_GET['id'] ?? 0);
$colleges = get_colleges($conn);

if ($bookId <= 0) {
    set_flash('error', 'Invalid book record.');
    redirect_to('view_books.php');
}

$stmt = $conn->prepare('SELECT * FROM library_books WHERE book_id = ?');
$stmt->bind_param('i', $bookId);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    set_flash('error', 'Book record not found.');
    redirect_to('view_books.php');
}

require_college_access((int) $book['college_id'], 'view_books.php');
$selectedCollegeId = get_selected_college_id($_POST, (int) $book['college_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookName = trim($_POST['book_name'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $totalCopies = (int) ($_POST['total_copies'] ?? 0);
    $availableCopies = (int) ($_POST['available_copies'] ?? 0);
    $selectedCollegeId = get_selected_college_id($_POST, (int) $book['college_id']);

    if ($bookName === '' || $author === '' || $category === '' || $totalCopies <= 0 || $availableCopies < 0 || $availableCopies > $totalCopies || $selectedCollegeId <= 0) {
        $errorMessage = 'Please provide valid catalog details. Available copies cannot exceed total copies.';
    } else {
        $updateStmt = $conn->prepare('UPDATE library_books SET college_id = ?, book_name = ?, author = ?, category = ?, total_copies = ?, available_copies = ? WHERE book_id = ?');
        $updateStmt->bind_param('isssiii', $selectedCollegeId, $bookName, $author, $category, $totalCopies, $availableCopies, $bookId);
        $updateStmt->execute();
        $updateStmt->close();
        sync_book_status($conn, $bookId);
        set_flash('success', 'Book record updated successfully.');
        redirect_to('view_books.php');
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Edit Book</h1>
        <p>Adjust title details and inventory levels without breaking circulation history.</p>
    </div>
</section>

<section class="split-layout">
    <article class="form-card">
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post">
            <div class="form-grid">
                <?php render_college_select($colleges, $selectedCollegeId); ?>
                <div>
                    <label for="book_name">Book Title</label>
                    <input type="text" id="book_name" name="book_name" value="<?php echo e($_POST['book_name'] ?? $book['book_name']); ?>" required>
                </div>
                <div>
                    <label for="author">Author</label>
                    <input type="text" id="author" name="author" value="<?php echo e($_POST['author'] ?? $book['author']); ?>" required>
                </div>
                <div>
                    <label for="category">Category</label>
                    <input type="text" id="category" name="category" value="<?php echo e($_POST['category'] ?? $book['category']); ?>" required>
                </div>
                <div>
                    <label for="total_copies">Total Copies</label>
                    <input type="number" id="total_copies" name="total_copies" min="1" value="<?php echo e($_POST['total_copies'] ?? $book['total_copies']); ?>" required>
                </div>
                <div>
                    <label for="available_copies">Available Copies</label>
                    <input type="number" id="available_copies" name="available_copies" min="0" value="<?php echo e($_POST['available_copies'] ?? $book['available_copies']); ?>" required>
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Save Changes</button>
                <a class="btn-light" href="view_books.php">Back</a>
            </div>
        </form>
    </article>

    <article class="insight-card">
        <h2>Availability Snapshot</h2>
        <ul class="insight-list">
            <li>Status: <?php echo e(ucfirst($book['status'])); ?></li>
            <li>Total copies: <?php echo e($book['total_copies']); ?></li>
            <li>Available now: <?php echo e($book['available_copies']); ?></li>
        </ul>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
