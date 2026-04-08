<?php
require_once "../db_connect.php";

$pageTitle = "Add Book";
$basePath = "../";
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bookName = trim($_POST["book_name"] ?? "");
    $author = trim($_POST["author"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $availableCopies = (int) ($_POST["available_copies"] ?? 0);

    if ($bookName === "" || $author === "" || $category === "" || $availableCopies <= 0) {
        $errorMessage = "Please enter valid book details. Available copies must be greater than 0.";
    } else {
        $stmt = $conn->prepare("INSERT INTO library_books (book_name, author, category, available_copies) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $bookName, $author, $category, $availableCopies);

        if ($stmt->execute()) {
            $successMessage = "Book record created successfully.";
            $_POST = array();
        } else {
            $errorMessage = "Unable to add book. " . $stmt->error;
        }

        $stmt->close();
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Add Book</h1>
    <p>Add a title to the library catalog and set the current inventory level.</p>
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
            <input type="text" id="book_name" name="book_name" value="<?php echo e($_POST["book_name"] ?? ""); ?>">
        </div>

        <div>
            <label for="author">Author Name</label>
            <input type="text" id="author" name="author" value="<?php echo e($_POST["author"] ?? ""); ?>">
        </div>

        <div>
            <label for="category">Category</label>
            <input type="text" id="category" name="category" value="<?php echo e($_POST["category"] ?? ""); ?>">
        </div>

        <div>
            <label for="available_copies">Available Copies</label>
            <input type="number" id="available_copies" name="available_copies" min="1" value="<?php echo e($_POST["available_copies"] ?? ""); ?>">
        </div>

        <button type="submit">Add to Catalog</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
