<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Add Book";
$pageKey = "library";
$basePath = "../";
$successMessage = "";
$errorMessage = "";
$colleges = get_colleges($conn);
$selectedCollegeId = get_selected_college_id($_POST, current_college_id());

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bookName = trim($_POST["book_name"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $totalCopies = (int) ($_POST["total_copies"] ?? 0);
    $selectedCollegeId = get_selected_college_id($_POST, current_college_id());

    if ($bookName === "" || $author === "" || $category === "" || $totalCopies <= 0 || $selectedCollegeId <= 0) {
        $errorMessage = "Please enter valid book details.";
    } else {
        $status = "available";
        $stmt = $conn->prepare("
            INSERT INTO library_books (college_id, book_name, author, category, total_copies, available_copies, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssiis", $selectedCollegeId, $bookName, $author, $category, $totalCopies, $totalCopies, $status);
        $stmt->execute();
        $stmt->close();
        $successMessage = "Book added to the catalog successfully.";
        $_POST = [];
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Add Book</h1>
    <p>Add a library title with total inventory, category, and college ownership.</p>
</section>

<section class="form-card">
    <?php if ($successMessage !== ""): ?><div class="message"><?php echo e($successMessage); ?></div><?php endif; ?>
    <?php if ($errorMessage !== ""): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>

    <form method="post">
        <div class="form-grid">
            <?php render_college_select($colleges, $selectedCollegeId); ?>
            <div>
                <label for="book_name">Book Name</label>
                <input type="text" id="book_name" name="book_name" value="<?php echo e($_POST["book_name"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="author">Author</label>
                <input type="text" id="author" name="author" value="<?php echo e($_POST["author"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo e($_POST["category"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="total_copies">Total Copies</label>
                <input type="number" id="total_copies" name="total_copies" min="1" value="<?php echo e($_POST["total_copies"] ?? ""); ?>" required>
            </div>
        </div>
        <div class="action-group">
            <button type="submit">Add Book</button>
            <a class="btn-light" href="view_books.php">View Catalog</a>
        </div>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
