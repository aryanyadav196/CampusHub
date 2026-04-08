<?php
require_once "../db_connect.php";

$pageTitle = "View Events";
$basePath = "../";
$result = $conn->query("SELECT * FROM events ORDER BY event_date ASC, event_id DESC");
$successMessage = "";
$errorMessage = "";

if (isset($_GET["status"])) {
    if ($_GET["status"] === "deleted") {
        $successMessage = "Event deleted successfully.";
    } elseif ($_GET["status"] === "updated") {
        $successMessage = "Event updated successfully.";
    } elseif ($_GET["status"] === "error") {
        $errorMessage = "Unable to delete the event.";
    } elseif ($_GET["status"] === "invalid") {
        $errorMessage = "Invalid event selected for deletion.";
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Event Calendar</h1>
    <p>Review scheduled events and their locations across campus.</p>
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
                    <th>Event Name</th>
                    <th>Event Date</th>
                    <th>Location</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo e($row["event_id"] ?? ""); ?></td>
                        <td><?php echo e($row["event_name"] ?? ""); ?></td>
                        <td><?php echo e($row["event_date"] ?? ""); ?></td>
                        <td><?php echo e($row["location"] ?? ""); ?></td>
                        <td>
                            <a
                                class="btn-edit"
                                href="edit_event.php?id=<?php echo urlencode((string) ($row["event_id"] ?? "")); ?>"
                            >
                                Edit
                            </a>
                            <a
                                class="btn-delete"
                                href="delete_event.php?id=<?php echo urlencode((string) ($row["event_id"] ?? "")); ?>"
                                onclick="return confirm('Are you sure you want to delete this event?');"
                            >
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="empty-state">No events are currently scheduled.</p>
    <?php endif; ?>
</section>

<?php require_once "../includes/footer.php"; ?>
