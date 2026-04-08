<?php
require_once "../db_connect.php";

$pageTitle = "Edit Event";
$basePath = "../";
$errorMessage = "";
$eventId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$event = null;

if ($eventId <= 0) {
    header("Location: view_events.php?status=invalid");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $eventName = trim($_POST["event_name"] ?? "");
        $eventDate = $_POST["event_date"] ?? "";
        $location = trim($_POST["location"] ?? "");

        if ($eventName === "" || $eventDate === "" || $location === "") {
            $errorMessage = "Please fill in all event details.";
        } else {
            $updateStmt = $conn->prepare("UPDATE events SET event_name = ?, event_date = ?, location = ? WHERE event_id = ?");
            $updateStmt->bind_param("sssi", $eventName, $eventDate, $location, $eventId);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: view_events.php?status=updated");
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT event_id, event_name, event_date, location FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();

    if (!$event) {
        header("Location: view_events.php?status=invalid");
        exit;
    }
} catch (mysqli_sql_exception $exception) {
    $errorMessage = "Unable to load or update the event.";
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Event</h1>
    <p>Update the event schedule and location details.</p>
</section>

<section class="form-card">
    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label for="event_name">Event Name</label>
            <input type="text" id="event_name" name="event_name" value="<?php echo e($_POST["event_name"] ?? ($event["event_name"] ?? "")); ?>">
        </div>

        <div>
            <label for="event_date">Event Date</label>
            <input type="date" id="event_date" name="event_date" value="<?php echo e($_POST["event_date"] ?? ($event["event_date"] ?? "")); ?>">
        </div>

        <div>
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo e($_POST["location"] ?? ($event["location"] ?? "")); ?>">
        </div>

        <button type="submit">Update Event</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
