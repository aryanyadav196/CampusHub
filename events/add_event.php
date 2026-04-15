<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Create Event";
$pageKey = "events";
$basePath = "../";
$errorMessage = "";
$colleges = get_colleges($conn);
$selectedCollegeId = get_selected_college_id($_POST, current_college_id());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eventTitle = trim($_POST['event_title'] ?? '');
    $eventDate = $_POST['event_date'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $selectedCollegeId = get_selected_college_id($_POST, current_college_id());
    $upload = upload_image('poster_image', 'events');

    if ($eventTitle === '' || $eventDate === '' || $venue === '' || $description === '' || $selectedCollegeId <= 0) {
        $errorMessage = 'Please fill in all event details.';
    } elseif ($upload['error'] !== '') {
        $errorMessage = $upload['error'];
    } else {
        $posterPath = $upload['path'];
        $stmt = $conn->prepare('INSERT INTO events (college_id, event_title, event_date, venue, description, poster_image) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssss', $selectedCollegeId, $eventTitle, $eventDate, $venue, $description, $posterPath);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Event created successfully.');
        redirect_to('view_events.php');
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Create Event</h1>
        <p>Publish campus programs with strong visual presentation and complete schedule details.</p>
    </div>
</section>

<nav class="module-tabs">
    <a class="module-tab" href="view_events.php">Event Board</a>
    <a class="module-tab active" href="add_event.php">Create Event</a>
</nav>

<section class="split-layout">
    <article class="form-card">
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <?php render_college_select($colleges, $selectedCollegeId); ?>
                <div>
                    <label for="event_title">Event Title</label>
                    <input type="text" id="event_title" name="event_title" value="<?php echo e($_POST['event_title'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="event_date">Event Date</label>
                    <input type="date" id="event_date" name="event_date" value="<?php echo e($_POST['event_date'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" value="<?php echo e($_POST['venue'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="poster_image">Poster Image</label>
                    <input type="file" id="poster_image" name="poster_image" accept="image/*">
                    <p class="help-text">Optional poster for event cards and previews.</p>
                </div>
                <div style="grid-column: 1 / -1;">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Publish Event</button>
                <a class="btn-light" href="view_events.php">Back to Board</a>
            </div>
        </form>
    </article>

    <article class="insight-card">
        <h2>Publishing Tips</h2>
        <ul class="insight-list">
            <li>Use concise titles and clear venue names for better discoverability.</li>
            <li>Poster images help the event board feel polished and easier to scan.</li>
            <li>Descriptions should include format, audience, and notable highlights.</li>
        </ul>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
