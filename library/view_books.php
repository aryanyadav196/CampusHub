<?php
require_once "../db_connect.php";

$pageTitle = "View Books";
$basePath = "../";
$result = $conn->query("SELECT * FROM library_books ORDER BY book_id DESC");
$successMessage = "";
$errorMessage = "";

if (isset($_GET["status"])) {
    if ($_GET["status"] === "deleted") {
        $successMessage = "Book record deleted successfully.";
    } elseif ($_GET["status"] === "updated") {
        $successMessage = "Book record updated successfully.";
    } elseif ($_GET["status"] === "error") {
        $errorMessage = "Unable to delete the book record.";
    } elseif ($_GET["status"] === "invalid") {
        $errorMessage = "Invalid book record selected for deletion.";
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Library Catalog</h1>
    <p>Browse the collection and review current availability across the catalog.</p>
</section>

<section class="table-card">
    <?php if ($successMessage !== ""): ?>
        <div class="message"><?php echo e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Book Name</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Available Copies</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row["book_id"] ?? ""); ?></td>
                        <td><?php echo e($row["book_name"] ?? ""); ?></td>
                        <td><?php echo e($row["author"] ?? ""); ?></td>
                        <td><?php echo e($row["category"] ?? ""); ?></td>
                        <td><?php echo e($row["available_copies"] ?? ""); ?></td>
                        <td>
                            <a
                                class="btn-edit"
                                href="edit_book.php?id=<?php echo urlencode((string) ($row["book_id"] ?? "")); ?>"
                            >
                                Edit
                            </a>
                            <a
                                class="btn-delete"
                                href="delete_book.php?id=<?php echo urlencode((string) ($row["book_id"] ?? "")); ?>"
                                onclick="return confirm('Are you sure you want to delete this book record?');"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No books are currently listed in the catalog.</p>
    <?php endif; ?>
</section>

<?php require_once "../includes/footer.php"; ?>
