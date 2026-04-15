<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Circulation";
$pageKey = "library";
$basePath = "../";
$errorMessage = "";
$successMessage = "";
$collegeFilter = is_admin() ? (int) ($_GET['college_id'] ?? 0) : current_college_id();
$scopeCondition = $collegeFilter > 0 ? ' WHERE college_id = ' . $collegeFilter : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentId = (int) ($_POST['student_id'] ?? 0);
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $issueDate = $_POST['issue_date'] ?? date('Y-m-d');
    $returnDate = $_POST['return_date'] ?? '';

    if ($studentId <= 0 || $bookId <= 0 || $issueDate === '' || $returnDate === '') {
        $errorMessage = 'Please select a student, a book, and valid dates.';
    } elseif ($returnDate < $issueDate) {
        $errorMessage = 'Expected return date cannot be earlier than issue date.';
    } else {
        $bookStmt = $conn->prepare('SELECT college_id, available_copies FROM library_books WHERE book_id = ?');
        $bookStmt->bind_param('i', $bookId);
        $bookStmt->execute();
        $book = $bookStmt->get_result()->fetch_assoc();
        $bookStmt->close();

        $studentStmt = $conn->prepare('SELECT college_id FROM students WHERE student_id = ?');
        $studentStmt->bind_param('i', $studentId);
        $studentStmt->execute();
        $student = $studentStmt->get_result()->fetch_assoc();
        $studentStmt->close();

        if (!$book || !$student) {
            $errorMessage = 'Selected student or book no longer exists.';
        } elseif ((int) $book['college_id'] !== (int) $student['college_id'] || !can_access_college((int) $book['college_id'])) {
            $errorMessage = 'Student and book must belong to the same accessible campus.';
        } elseif ((int) $book['available_copies'] <= 0) {
            $errorMessage = 'This title is currently unavailable.';
        } else {
            $conn->begin_transaction();
            try {
                $collegeId = (int) $book['college_id'];
                $status = 'issued';
                $insertStmt = $conn->prepare('INSERT INTO book_issue (college_id, student_id, book_id, issue_date, expected_return_date, status) VALUES (?, ?, ?, ?, ?, ?)');
                $insertStmt->bind_param('iiisss', $collegeId, $studentId, $bookId, $issueDate, $returnDate, $status);
                $insertStmt->execute();
                $insertStmt->close();

                $updateStmt = $conn->prepare('UPDATE library_books SET available_copies = available_copies - 1 WHERE book_id = ?');
                $updateStmt->bind_param('i', $bookId);
                $updateStmt->execute();
                $updateStmt->close();

                sync_book_status($conn, $bookId);
                $conn->commit();
                $successMessage = 'Book issued successfully.';
                $_POST = [];
            } catch (Throwable $throwable) {
                $conn->rollback();
                $errorMessage = 'Unable to issue the book right now.';
            }
        }
    }
}

$students = $conn->query('SELECT student_id, name, department FROM students' . $scopeCondition . ' ORDER BY name ASC');
$books = $conn->query('SELECT book_id, book_name, available_copies FROM library_books' . $scopeCondition . ' ORDER BY book_name ASC');
$issueList = $conn->query('SELECT book_issue.issue_id, book_issue.status, book_issue.issue_date, book_issue.expected_return_date, book_issue.actual_return_date, students.name, students.department, library_books.book_name, library_books.author FROM book_issue INNER JOIN students ON students.student_id = book_issue.student_id INNER JOIN library_books ON library_books.book_id = book_issue.book_id' . ($scopeCondition ? ' WHERE book_issue.college_id = ' . $collegeFilter : '') . ' ORDER BY book_issue.issue_id DESC');

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Circulation</h1>
        <p>Create issue records, monitor due dates, and complete returns from one screen.</p>
    </div>
</section>

<nav class="module-tabs">
    <a class="module-tab" href="view_books.php">Catalog</a>
    <a class="module-tab" href="add_book.php">Add Book</a>
    <a class="module-tab active" href="issue_book.php">Circulation</a>
</nav>

<section class="split-layout">
    <article class="form-card">
        <h2>Issue a Book</h2>
        <?php if ($successMessage !== ''): ?><div class="message"><?php echo e($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post">
            <div>
                <label for="student_id">Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <option value="<?php echo (int) $student['student_id']; ?>" <?php echo ((int) ($_POST['student_id'] ?? 0) === (int) $student['student_id']) ? 'selected' : ''; ?>><?php echo e($student['name'] . ' - ' . $student['department']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label for="book_id">Book</label>
                <select id="book_id" name="book_id" required>
                    <option value="">Select Book</option>
                    <?php while ($book = $books->fetch_assoc()): ?>
                        <option value="<?php echo (int) $book['book_id']; ?>" <?php echo ((int) ($_POST['book_id'] ?? 0) === (int) $book['book_id']) ? 'selected' : ''; ?>><?php echo e($book['book_name'] . ' (Available: ' . $book['available_copies'] . ')'); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-grid">
                <div>
                    <label for="issue_date">Issue Date</label>
                    <input type="date" id="issue_date" name="issue_date" value="<?php echo e($_POST['issue_date'] ?? date('Y-m-d')); ?>" required>
                </div>
                <div>
                    <label for="return_date">Expected Return</label>
                    <input type="date" id="return_date" name="return_date" value="<?php echo e($_POST['return_date'] ?? ''); ?>" required>
                </div>
            </div>
            <button type="submit">Issue Book</button>
        </form>
    </article>

    <article class="insight-card">
        <h2>Workflow Notes</h2>
        <ul class="insight-list">
            <li>Only available titles can be issued.</li>
            <li>Students and books must belong to the same accessible campus.</li>
            <li>Returned books can be marked from the edit action in the history list.</li>
        </ul>
    </article>
</section>

<section class="table-card" style="margin-top: 24px;">
    <div class="page-heading">
        <div>
            <h2>Issue History</h2>
            <p>Track due dates, returns, and active circulation records.</p>
        </div>
    </div>
    <?php if ($issueList && $issueList->num_rows > 0): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Book</th>
                        <th>Dates</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($issue = $issueList->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($issue['name']); ?></strong>
                                <div class="muted"><?php echo e($issue['department']); ?></div>
                            </td>
                            <td>
                                <strong><?php echo e($issue['book_name']); ?></strong>
                                <div class="muted"><?php echo e($issue['author']); ?></div>
                            </td>
                            <td>
                                <div>Issued: <?php echo e(format_date_label($issue['issue_date'])); ?></div>
                                <div>Due: <?php echo e(format_date_label($issue['expected_return_date'])); ?></div>
                                <div>Returned: <?php echo e(format_date_label($issue['actual_return_date'])); ?></div>
                            </td>
                            <td><span class="<?php echo e(get_status_badge_class($issue['status'])); ?>"><?php echo e(ucfirst($issue['status'])); ?></span></td>
                            <td>
                                <div class="action-group">
                                    <a class="btn-light" href="edit_issue.php?id=<?php echo (int) $issue['issue_id']; ?>">Edit</a>
                                    <a class="btn-danger" href="delete_issue.php?id=<?php echo (int) $issue['issue_id']; ?>" onclick="return confirm('Delete this issue record?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?php render_empty_state('No circulation records', 'Issue a book to create the first circulation entry.'); ?>
    <?php endif; ?>
</section>

<?php require_once "../includes/footer.php"; ?>
