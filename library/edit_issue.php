<?php
require_once "../db_connect.php";

$pageTitle = "Edit Issue";
$basePath = "../";
$errorMessage = "";
$issueId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$issue = null;

if ($issueId <= 0) {
    header("Location: issue_book.php?status=invalid");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $issueDate = $_POST["issue_date"] ?? "";
        $returnDate = $_POST["return_date"] ?? "";

        if ($issueDate === "" || $returnDate === "") {
            $errorMessage = "Please provide both issue and return dates.";
        } elseif ($returnDate < $issueDate) {
            $errorMessage = "Return date cannot be earlier than issue date.";
        } else {
            $updateStmt = $conn->prepare("UPDATE book_issue SET issue_date = ?, return_date = ? WHERE issue_id = ?");
            $updateStmt->bind_param("ssi", $issueDate, $returnDate, $issueId);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: issue_book.php?status=updated");
            exit;
        }
    }

    $stmt = $conn->prepare("
        SELECT
            book_issue.issue_id,
            book_issue.issue_date,
            book_issue.return_date,
            students.name,
            students.department,
            library_books.book_name,
            library_books.author
        FROM book_issue
        INNER JOIN students ON book_issue.student_id = students.student_id
        INNER JOIN library_books ON book_issue.book_id = library_books.book_id
        WHERE book_issue.issue_id = ?
    ");
    $stmt->bind_param("i", $issueId);
    $stmt->execute();
    $result = $stmt->get_result();
    $issue = $result->fetch_assoc();
    $stmt->close();

    if (!$issue) {
        header("Location: issue_book.php?status=invalid");
        exit;
    }
} catch (mysqli_sql_exception $exception) {
    $errorMessage = "Unable to load or update the issue record.";
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Issue Record</h1>
    <p>Update circulation dates for the selected book issue record.</p>
</section>

<section class="two-column issue-layout">
    <div class="table-card issue-panel">
        <h2>Issue Details</h2>
        <table>
            <tbody>
                <tr>
                    <th>Student</th>
                    <td><?php echo e($issue["name"] ?? ""); ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?php echo e($issue["department"] ?? ""); ?></td>
                </tr>
                <tr>
                    <th>Book</th>
                    <td><?php echo e($issue["book_name"] ?? ""); ?></td>
                </tr>
                <tr>
                    <th>Author</th>
                    <td><?php echo e($issue["author"] ?? ""); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="form-card issue-panel">
        <h2>Update Dates</h2>

        <?php if ($errorMessage !== ""): ?>
            <div class="error"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post">
            <div>
                <label for="issue_date">Issue Date</label>
                <input type="date" id="issue_date" name="issue_date" value="<?php echo e($_POST["issue_date"] ?? ($issue["issue_date"] ?? "")); ?>">
            </div>

            <div>
                <label for="return_date">Return Date</label>
                <input type="date" id="return_date" name="return_date" value="<?php echo e($_POST["return_date"] ?? ($issue["return_date"] ?? "")); ?>">
            </div>

            <button type="submit">Update Issue Record</button>
        </form>
    </div>
</section>

<?php require_once "../includes/footer.php"; ?>
