<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Edit Issue";
$pageKey = "library";
$basePath = "../";
$errorMessage = "";
$issueId = (int) ($_GET["id"] ?? 0);

if ($issueId <= 0) {
    set_flash("error", "Invalid issue record.");
    redirect_to("issue_book.php");
}

$stmt = $conn->prepare("
    SELECT
        book_issue.*,
        students.name,
        students.department,
        library_books.book_name,
        library_books.author
    FROM book_issue
    INNER JOIN students ON students.student_id = book_issue.student_id
    INNER JOIN library_books ON library_books.book_id = book_issue.book_id
    WHERE book_issue.issue_id = ?
");
$stmt->bind_param("i", $issueId);
$stmt->execute();
$issue = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$issue) {
    set_flash("error", "Issue record not found.");
    redirect_to("issue_book.php");
}

require_college_access((int) $issue["college_id"], "issue_book.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $issueDate = $_POST["issue_date"] ?? "";
    $expectedReturnDate = $_POST["expected_return_date"] ?? "";
    $status = $_POST["status"] ?? "issued";
    $actualReturnDate = $_POST["actual_return_date"] ?? null;
    $actualReturnDate = $actualReturnDate === "" ? null : $actualReturnDate;

    if ($issueDate === "" || $expectedReturnDate === "") {
        $errorMessage = "Issue date and expected return date are required.";
    } elseif ($expectedReturnDate < $issueDate) {
        $errorMessage = "Expected return date cannot be earlier than issue date.";
    } elseif ($status === "returned" && (!$actualReturnDate || $actualReturnDate < $issueDate)) {
        $errorMessage = "Returned books need a valid return date.";
    } else {
        $conn->begin_transaction();
        try {
            $wasIssued = $issue["status"] === "issued";
            $isNowReturned = $status === "returned";
            $isNowIssued = $status === "issued";
            $bookId = (int) $issue["book_id"];

            $updateStmt = $conn->prepare("
                UPDATE book_issue
                SET issue_date = ?, expected_return_date = ?, actual_return_date = ?, status = ?
                WHERE issue_id = ?
            ");
            $updateStmt->bind_param("ssssi", $issueDate, $expectedReturnDate, $actualReturnDate, $status, $issueId);
            $updateStmt->execute();
            $updateStmt->close();

            if ($wasIssued && $isNowReturned) {
                $bookUpdate = $conn->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id = ?");
                $bookUpdate->bind_param("i", $bookId);
                $bookUpdate->execute();
                $bookUpdate->close();
            } elseif (!$wasIssued && $isNowIssued) {
                $checkBook = $conn->prepare("SELECT available_copies FROM library_books WHERE book_id = ?");
                $checkBook->bind_param("i", $bookId);
                $checkBook->execute();
                $bookState = $checkBook->get_result()->fetch_assoc();
                $checkBook->close();
                if ((int) ($bookState["available_copies"] ?? 0) <= 0) {
                    throw new RuntimeException("No copies are available to mark this issue as active.");
                }
                $bookUpdate = $conn->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id = ?");
                $bookUpdate->bind_param("i", $bookId);
                $bookUpdate->execute();
                $bookUpdate->close();
            }

            sync_book_status($conn, $bookId);
            $conn->commit();
            set_flash("success", "Issue record updated successfully.");
            redirect_to("issue_book.php");
        } catch (Throwable $throwable) {
            $conn->rollback();
            $errorMessage = $throwable->getMessage() ?: "Unable to update this issue record.";
        }
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Issue Record</h1>
    <p>Update due dates and switch issue status between issued and returned.</p>
</section>

<section class="split-layout">
    <article class="table-card">
        <h2>Issue Details</h2>
        <table>
            <tbody>
                <tr><th>Student</th><td><?php echo e($issue["name"]); ?></td></tr>
                <tr><th>Department</th><td><?php echo e($issue["department"]); ?></td></tr>
                <tr><th>Book</th><td><?php echo e($issue["book_name"]); ?></td></tr>
                <tr><th>Author</th><td><?php echo e($issue["author"]); ?></td></tr>
            </tbody>
        </table>
    </article>
    <article class="form-card">
        <?php if ($errorMessage !== ""): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post">
            <div class="form-grid">
                <div>
                    <label for="issue_date">Issue Date</label>
                    <input type="date" id="issue_date" name="issue_date" value="<?php echo e($_POST["issue_date"] ?? $issue["issue_date"]); ?>" required>
                </div>
                <div>
                    <label for="expected_return_date">Expected Return Date</label>
                    <input type="date" id="expected_return_date" name="expected_return_date" value="<?php echo e($_POST["expected_return_date"] ?? $issue["expected_return_date"]); ?>" required>
                </div>
                <div>
                    <label for="status">Status</label>
                    <?php $selectedStatus = $_POST["status"] ?? $issue["status"]; ?>
                    <select id="status" name="status">
                        <option value="issued" <?php echo $selectedStatus === "issued" ? "selected" : ""; ?>>Issued</option>
                        <option value="returned" <?php echo $selectedStatus === "returned" ? "selected" : ""; ?>>Returned</option>
                    </select>
                </div>
                <div>
                    <label for="actual_return_date">Actual Return Date</label>
                    <input type="date" id="actual_return_date" name="actual_return_date" value="<?php echo e($_POST["actual_return_date"] ?? ($issue["actual_return_date"] ?? "")); ?>">
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Update Issue</button>
                <a class="btn-light" href="issue_book.php">Back</a>
            </div>
        </form>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
