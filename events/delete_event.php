<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$eventId = (int) ($_GET["id"] ?? 0);
if ($eventId <= 0) {
    set_flash("error", "Invalid event record.");
    redirect_to("view_events.php");
}

$selectStmt = $conn->prepare("SELECT college_id, poster_image FROM events WHERE event_id = ?");
$selectStmt->bind_param("i", $eventId);
$selectStmt->execute();
$event = $selectStmt->get_result()->fetch_assoc();
$selectStmt->close();

if (!$event) {
    set_flash("error", "Event not found.");
    redirect_to("view_events.php");
}

require_college_access((int) $event["college_id"], "view_events.php");

$deleteStmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
$deleteStmt->bind_param("i", $eventId);
$deleteStmt->execute();
$deleted = $deleteStmt->affected_rows > 0;
$deleteStmt->close();

if ($deleted) {
    delete_uploaded_file($event["poster_image"] ?? null);
}

set_flash($deleted ? "success" : "error", $deleted ? "Event deleted successfully." : "Unable to delete the event.");
redirect_to("view_events.php");
