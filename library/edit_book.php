<?php
require_once "../db_connect.php";

$pageTitle = "Edit Book";
$basePath = "../";
$successMessage = "";
$errorMessage = "";
$bookId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$book = null;

if ($bookId <= 0) {
    header("Location: view_books.php?status=invalid");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $bookName = trim($_POST["book_name"] ?? "");
        $author = trim($_POST["author"] ?? "");
        $category = trim($_POST["category"] ?? "");
        $availableCopies = (int) ($_POST["available_copies"] ?? 0);

        if ($bookName === "" || $author === "" || $category === "" || $availableCopies < 0) {
            $errorMessage = "Please enter valid book details.";
        } else {
            $updateStmt = $conn->prepare("UPDATE library_books SET book_name = ?, author = ?, category = ?, available_copies = ? WHERE book_id = ?");
            $updateStmt->bind_param("sssii", $bookName, $author, $category, $availableCopies, $bookId);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: view_books.php?status=updated");
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT book_id, book_name, author, category, available_copies FROM library_books WHERE book_id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) {
        header("Location: view_books.php?status=invalid");
        exit;
    }
} catch (mysqli_sql_exception $exception) {
    $errorMessage = "Unable to load or update the book record.";
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Book</h1>
    <p>Update the catalog details for this library record.</p>
</section>

<section class="form-card">
    <?php if ($successMessage !== ""): ?>
        <div class="message"><?php echo e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label for="book_name">Book Name</label>
            <input type="text" id="book_name" name="book_name" value="<?php echo e($_POST["book_name"] ?? ($book["book_name"] ?? "")); ?>">
        </div>

        <div>
            <label for="author">Author Name</label>
            <input type="text" id="author" name="author" value="<?php echo e($_POST["author"] ?? ($book["author"] ?? "")); ?>">
        </div>

        <div>
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?php echo e($_POST["category"] ?? ($book["category"] ?? "")); ?>">
        </div>

        <div>
            <label for="available_copies">Available Copies</label>
            <input type="number" id="available_copies" name="available_copies" min="0" value="<?php echo e($_POST["available_copies"] ?? ($book["available_copies"] ?? "")); ?>">
        </div>

        <button type="submit">Update Book</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
