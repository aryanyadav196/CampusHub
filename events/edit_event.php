<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Edit Event";
$pageKey = "events";
$basePath = "../";
$errorMessage = "";
$eventId = (int) ($_GET['id'] ?? 0);
$colleges = get_colleges($conn);

if ($eventId <= 0) {
    set_flash('error', 'Invalid event record.');
    redirect_to('view_events.php');
}

$stmt = $conn->prepare('SELECT * FROM events WHERE event_id = ?');
$stmt->bind_param('i', $eventId);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    set_flash('error', 'Event not found.');
    redirect_to('view_events.php');
}

require_college_access((int) $event['college_id'], 'view_events.php');
$selectedCollegeId = get_selected_college_id($_POST, (int) $event['college_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventTitle = trim($_POST['event_title'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selectedCollegeId = get_selected_college_id($_POST, (int) $event['college_id']);
    $upload = upload_image('poster_image', 'events', $event['poster_image'] ?? null);

    if ($eventTitle === '' || $eventDate === '' || $venue === '' || $description === '' || $selectedCollegeId <= 0) {
        $errorMessage = 'Please fill in all event details.';
    } elseif ($upload['error'] !== '') {
        $errorMessage = $upload['error'];
    } else {
        $posterPath = $upload['path'];
        $updateStmt = $conn->prepare('UPDATE events SET college_id = ?, event_title = ?, event_date = ?, venue = ?, description = ?, poster_image = ? WHERE event_id = ?');
        $updateStmt->bind_param('isssssi', $selectedCollegeId, $eventTitle, $eventDate, $venue, $description, $posterPath, $eventId);
        $updateStmt->execute();
        $updateStmt->close();
        set_flash('success', 'Event updated successfully.');
        redirect_to('view_events.php');
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Edit Event</h1>
        <p>Refine the title, poster, venue, and description for this scheduled event.</p>
    </div>
</section>

<section class="split-layout">
    <article class="form-card">
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <?php render_college_select($colleges, $selectedCollegeId); ?>
                <div>
                    <label for="event_title">Event Title</label>
                    <input type="text" id="event_title" name="event_title" value="<?php echo e($_POST['event_title'] ?? $event['event_title']); ?>" required>
                </div>
                <div>
                    <label for="event_date">Event Date</label>
                    <input type="date" id="event_date" name="event_date" value="<?php echo e($_POST['event_date'] ?? $event['event_date']); ?>" required>
                </div>
                <div>
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" value="<?php echo e($_POST['venue'] ?? $event['venue']); ?>" required>
                </div>
                <div>
                    <label for="poster_image">Poster Image</label>
                    <input type="file" id="poster_image" name="poster_image" accept="image/*">
                    <p class="help-text">Uploading a new poster replaces the current one.</p>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo e($_POST['description'] ?? $event['description']); ?></textarea>
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Save Changes</button>
                <a class="btn-light" href="view_events.php">Back</a>
            </div>
        </form>
    </article>

    <article class="insight-card">
        <h2>Current Schedule</h2>
        <ul class="insight-list">
            <li>Date: <?php echo e(format_date_label($event['event_date'])); ?></li>
            <li>Venue: <?php echo e($event['venue']); ?></li>
            <li>Poster attached: <?php echo !empty($event['poster_image']) ? 'Yes' : 'No'; ?></li>
        </ul>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
