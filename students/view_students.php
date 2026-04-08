<?php
require_once "../db_connect.php";

$pageTitle = "View Students";
$basePath = "../";
$result = $conn->query("SELECT * FROM students ORDER BY student_id DESC");
$successMessage = "";
$errorMessage = "";

if (isset($_GET["status"])) {
    if ($_GET["status"] === "deleted") {
        $successMessage = "Student record deleted successfully.";
    } elseif ($_GET["status"] === "updated") {
        $successMessage = "Student record updated successfully.";
    } elseif ($_GET["status"] === "error") {
        $errorMessage = "Unable to delete the student record.";
    } elseif ($_GET["status"] === "invalid") {
        $errorMessage = "Invalid student record selected for deletion.";
    } elseif ($_GET["status"] === "linked") {
        $errorMessage = "This student record cannot be deleted because related book issue records exist.";
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Student Directory</h1>
    <p>View and review student records across departments and year groups.</p>
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
                    <th>Name</th>
                    <th>Email</th>
                    <th>Department</th>
                    <th>Year</th>
                    <th>Phone</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row["student_id"] ?? ""); ?></td>
                        <td><?php echo e($row["name"] ?? ""); ?></td>
                        <td><?php echo e($row["email"] ?? ""); ?></td>
                        <td><?php echo e($row["department"] ?? ""); ?></td>
                        <td><?php echo e($row["year"] ?? ""); ?></td>
                        <td><?php echo e($row["phone"] ?? ""); ?></td>
                        <td>
                            <a
                                class="btn-edit"
                                href="edit_student.php?id=<?php echo urlencode((string) ($row["student_id"] ?? "")); ?>"
                            >
                                Edit
                            </a>
                            <a
                                class="btn-delete"
                                href="delete_student.php?id=<?php echo urlencode((string) ($row["student_id"] ?? "")); ?>"
                                onclick="return confirm('Are you sure you want to delete this student record?');"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No student records are available.</p>
    <?php endif; ?>
</section>

<?php require_once "../includes/footer.php"; ?>
