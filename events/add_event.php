<?php
require_once "../db_connect.php";

$pageTitle = "Add Event";
$basePath = "../";
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventName = trim($_POST["event_name"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $location = trim($_POST["location"] ?? "");

    if ($eventName === "" || $eventDate === "" || $location === "") {
        $errorMessage = "Please fill in all event details.";
    } else {
        $stmt = $conn->prepare("INSERT INTO events (event_name, event_date, location) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $eventName, $eventDate, $location);

        if ($stmt->execute()) {
            $successMessage = "Event created successfully.";
            $_POST = array();
        } else {
            $errorMessage = "Unable to add event. " . $stmt->error;
        }

        $stmt->close();
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Create Event</h1>
    <p>Schedule an event and publish it to the campus calendar.</p>
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
            <label for="event_name">Event Name</label>
            <input type="text" id="event_name" name="event_name" value="<?php echo e($_POST["event_name"] ?? ""); ?>">
        </div>

        <div>
            <label for="event_date">Event Date</label>
            <input type="date" id="event_date" name="event_date" value="<?php echo e($_POST["event_date"] ?? ""); ?>">
        </div>

        <div>
            <label for="location">Location</label>
            <input type="text" id="location" name="location" value="<?php echo e($_POST["location"] ?? ""); ?>">
        </div>

        <button type="submit">Create Event</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
