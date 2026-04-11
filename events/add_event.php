<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Create Event";
$pageKey = "events";
$basePath = "../";
$successMessage = "";
$errorMessage = "";
$colleges = get_colleges($conn);
$selectedCollegeId = get_selected_college_id($_POST, current_college_id());

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $eventTitle = trim($_POST["event_title"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $venue = trim($_POST["venue"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $selectedCollegeId = get_selected_college_id($_POST, current_college_id());
    $upload = upload_image("poster_image", "events");

    if ($eventTitle === "" || $eventDate === "" || $venue === "" || $description === "" || $selectedCollegeId <= 0) {
        $errorMessage = "Please fill in all event details.";
    } elseif ($upload["error"] !== "") {
        $errorMessage = $upload["error"];
    } else {
        $posterPath = $upload["path"];
        $stmt = $conn->prepare("
            INSERT INTO events (college_id, event_title, event_date, venue, description, poster_image)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $selectedCollegeId, $eventTitle, $eventDate, $venue, $description, $posterPath);
        $stmt->execute();
        $stmt->close();
        $successMessage = "Event created successfully.";
        $_POST = [];
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Create Event</h1>
    <p>Publish upcoming campus events with date, venue, description, and poster image.</p>
</section>

<section class="form-card">
    <?php if ($successMessage !== ""): ?><div class="message"><?php echo e($successMessage); ?></div><?php endif; ?>
    <?php if ($errorMessage !== ""): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <?php render_college_select($colleges, $selectedCollegeId); ?>
            <div>
                <label for="event_title">Event Title</label>
                <input type="text" id="event_title" name="event_title" value="<?php echo e($_POST["event_title"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="event_date">Event Date</label>
                <input type="date" id="event_date" name="event_date" value="<?php echo e($_POST["event_date"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="venue">Venue</label>
                <input type="text" id="venue" name="venue" value="<?php echo e($_POST["venue"] ?? ""); ?>" required>
            </div>
            <div>
                <label for="poster_image">Poster Image</label>
                <input type="file" id="poster_image" name="poster_image" accept="image/*">
            </div>
            <div style="grid-column: 1 / -1;">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo e($_POST["description"] ?? ""); ?></textarea>
            </div>
        </div>
        <div class="action-group">
            <button type="submit">Create Event</button>
            <a class="btn-light" href="view_events.php">View Events</a>
        </div>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
