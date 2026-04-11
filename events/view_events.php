<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Events";
$pageKey = "events";
$basePath = "../";
$search = trim($_GET["q"] ?? "");
$collegeFilter = is_admin() ? (int) ($_GET["college_id"] ?? 0) : current_college_id();
$colleges = get_colleges($conn);

$where = [];
$types = "";
$params = [];

if ($search !== "") {
    $where[] = "(events.event_title LIKE ? OR events.venue LIKE ?)";
    $types .= "ss";
    $like = "%" . $search . "%";
    array_push($params, $like, $like);
}
if ($collegeFilter > 0) {
    $where[] = "events.college_id = ?";
    $types .= "i";
    $params[] = $collegeFilter;
}

$whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";
$stmt = $conn->prepare("
    SELECT events.*, colleges.college_name
    FROM events
    LEFT JOIN colleges ON colleges.college_id = events.college_id
    " . $whereSql . "
    ORDER BY events.event_date ASC, events.event_id DESC
");
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Event Calendar</h1>
    <p>Browse upcoming events in a card layout with posters, descriptions, and college filters.</p>
</section>

<section class="panel">
    <form class="filters-form" method="get">
        <div class="filter-row">
            <div class="filter-item search-input">
                <label for="q">Search</label>
                <input type="text" id="q" name="q" value="<?php echo e($search); ?>" placeholder="Search event title or venue">
            </div>
            <?php if (is_admin()): ?>
                <div class="filter-item">
                    <label for="college_id">College</label>
                    <select id="college_id" name="college_id">
                        <option value="">All Colleges</option>
                        <?php foreach ($colleges as $college): ?>
                            <option value="<?php echo (int) $college["college_id"]; ?>" <?php echo $collegeFilter === (int) $college["college_id"] ? "selected" : ""; ?>>
                                <?php echo e($college["college_name"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="filter-item action-group">
                <button type="submit">Apply</button>
                <a class="btn-light" href="view_events.php">Reset</a>
                <a class="btn-ghost" href="add_event.php">Create Event</a>
            </div>
        </div>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="event-grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <article class="event-card">
                    <div class="event-card-image">
                        <?php if (!empty($row["poster_image"])): ?>
                            <img src="<?php echo e($basePath . $row["poster_image"]); ?>" alt="<?php echo e($row["event_title"]); ?>">
                        <?php endif; ?>
                    </div>
                    <h2><?php echo e($row["event_title"]); ?></h2>
                    <div class="event-meta">
                        <span><?php echo e(format_date_label($row["event_date"])); ?></span>
                        <span><?php echo e($row["venue"]); ?></span>
                        <?php if (is_admin()): ?><span><?php echo e($row["college_name"]); ?></span><?php endif; ?>
                    </div>
                    <p class="muted"><?php echo e($row["description"]); ?></p>
                    <div class="action-group">
                        <a class="btn-light" href="edit_event.php?id=<?php echo (int) $row["event_id"]; ?>">Edit</a>
                        <a class="btn-danger" href="delete_event.php?id=<?php echo (int) $row["event_id"]; ?>" onclick="return confirm('Delete this event?');">Delete</a>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <?php render_empty_state("No events found", "Create an event to build out the campus calendar."); ?>
    <?php endif; ?>
</section>

<?php
$stmt->close();
require_once "../includes/footer.php";
?>
