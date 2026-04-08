<?php
require_once "../db_connect.php";

$pageTitle = "Issue Book";
$basePath = "../";
$successMessage = "";
$errorMessage = "";

if (isset($_GET["status"])) {
    if ($_GET["status"] === "deleted") {
        $successMessage = "Issue record deleted successfully.";
    } elseif ($_GET["status"] === "updated") {
        $successMessage = "Issue record updated successfully.";
    } elseif ($_GET["status"] === "invalid") {
        $errorMessage = "Invalid issue record selected.";
    } elseif ($_GET["status"] === "error") {
        $errorMessage = "Unable to process the issue record.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentId = (int) ($_POST["student_id"] ?? 0);
    $bookId = (int) ($_POST["book_id"] ?? 0);
    $issueDate = $_POST["issue_date"] ?? date("Y-m-d");
    $returnDate = $_POST["return_date"] ?? "";

    if ($studentId <= 0 || $bookId <= 0 || $issueDate === "" || $returnDate === "") {
        $errorMessage = "Please select a student, a book, and both dates.";
    } elseif ($returnDate < $issueDate) {
        $errorMessage = "Return date cannot be earlier than issue date.";
    } else {
        $bookCheckStmt = $conn->prepare("SELECT available_copies FROM library_books WHERE book_id = ?");
        $bookCheckStmt->bind_param("i", $bookId);
        $bookCheckStmt->execute();
        $bookCheckStmt->bind_result($availableCopies);
        $bookFound = $bookCheckStmt->fetch();
        $bookCheckStmt->close();

        if (!$bookFound) {
            $errorMessage = "Selected book does not exist.";
        } elseif ((int) $availableCopies <= 0) {
            $errorMessage = "This book is out of stock.";
        } else {
            $conn->begin_transaction();

            try {
                $issueStmt = $conn->prepare("INSERT INTO book_issue (student_id, book_id, issue_date, return_date) VALUES (?, ?, ?, ?)");
                $issueStmt->bind_param("iiss", $studentId, $bookId, $issueDate, $returnDate);
                $issueStmt->execute();
                $issueStmt->close();

                $updateStmt = $conn->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id = ?");
                $updateStmt->bind_param("i", $bookId);
                $updateStmt->execute();
                $updateStmt->close();

                $conn->commit();
                $successMessage = "Book issued successfully.";
                $_POST = array();
            } catch (Exception $exception) {
                $conn->rollback();
                $errorMessage = "Unable to issue book. " . $exception->getMessage();
            }
        }
    }
}

$students = $conn->query("SELECT student_id, name, department FROM students ORDER BY name ASC");
$books = $conn->query("SELECT book_id, book_name, available_copies FROM library_books ORDER BY book_name ASC");

// JOIN is used here to combine student details and book details in one result.
$issueListQuery = "
    SELECT
        book_issue.issue_id,
        students.name,
        students.department,
        library_books.book_name,
        library_books.author,
        book_issue.issue_date,
        book_issue.return_date
    FROM book_issue
    INNER JOIN students ON book_issue.student_id = students.student_id
    INNER JOIN library_books ON book_issue.book_id = library_books.book_id
    ORDER BY book_issue.issue_id DESC
";
$issueList = $conn->query($issueListQuery);

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Issue Book</h1>
    <p>Create circulation records and track issued materials across the library.</p>
</section>

<section class="two-column issue-layout">
    <div class="form-card issue-panel">
        <h2>New Issue Record</h2>

        <?php if ($successMessage !== ""): ?>
            <div class="message"><?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ""): ?>
            <div class="error"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post">
            <div>
                <label for="student_id">Select Student</label>
                <select id="student_id" name="student_id">
                    <option value="">Select Student</option>
                    <?php if ($students && $students->num_rows > 0): ?>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo $student["student_id"]; ?>" <?php echo ((string) ($student["student_id"]) === ($_POST["student_id"] ?? "")) ? "selected" : ""; ?>>
                                <?php echo e(($student["name"] ?? "") . " - " . ($student["department"] ?? "")); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="book_id">Select Book</label>
                <select id="book_id" name="book_id">
                    <option value="">Select Book</option>
                    <?php if ($books && $books->num_rows > 0): ?>
                        <?php while ($book = $books->fetch_assoc()): ?>
                            <option value="<?php echo $book["book_id"]; ?>" <?php echo ((string) ($book["book_id"]) === ($_POST["book_id"] ?? "")) ? "selected" : ""; ?>>
                                <?php echo e(($book["book_name"] ?? "") . " (Available: " . ($book["available_copies"] ?? "") . ")"); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div>
                <label for="issue_date">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" value="<?php echo e($_POST["issue_date"] ?? date("Y-m-d")); ?>">
            </div>

            <div>
                <label for="return_date">Return Date</label>
                <input type="date" id="return_date" name="return_date" value="<?php echo e($_POST["return_date"] ?? ""); ?>">
            </div>

            <button type="submit">Create Issue Record</button>
        </form>
    </div>

    <div class="table-card issue-panel issue-table-panel">
        <h2>Issued Books</h2>

        <?php if ($issueList && $issueList->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Issue ID</th>
                        <th>Student</th>
                        <th>Department</th>
                        <th>Book</th>
                        <th>Author</th>
                        <th>Issue Date</th>
                        <th>Return Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($issue = $issueList->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo e($issue["issue_id"] ?? ""); ?></td>
                            <td><?php echo e($issue["name"] ?? ""); ?></td>
                            <td><?php echo e($issue["department"] ?? ""); ?></td>
                            <td><?php echo e($issue["book_name"] ?? ""); ?></td>
                            <td><?php echo e($issue["author"] ?? ""); ?></td>
                            <td><?php echo e($issue["issue_date"] ?? ""); ?></td>
                            <td><?php echo e($issue["return_date"] ?? ""); ?></td>
                            <td>
                                <div class="action-group">
                                    <a
                                        class="btn-edit"
                                        href="edit_issue.php?id=<?php echo urlencode((string) ($issue["issue_id"] ?? "")); ?>"
                                    >
                                        Edit
                                    </a>
                                    <a
                                        class="btn-delete"
                                        href="delete_issue.php?id=<?php echo urlencode((string) ($issue["issue_id"] ?? "")); ?>"
                                        onclick="return confirm('Are you sure you want to delete this issue record?');"
                                    >
                                        Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-state">No circulation records are available.</p>
        <?php endif; ?>
    </div>
</section>

<?php require_once "../includes/footer.php"; ?>
