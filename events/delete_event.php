<?php
require_once "../db_connect.php";

$eventId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($eventId <= 0) {
    header("Location: view_events.php?status=invalid");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM events WHERE event_id = ?");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: view_events.php?status=deleted");
        exit;
    }

    $stmt->close();
    header("Location: view_events.php?status=invalid");
    exit;
} catch (mysqli_sql_exception $exception) {
    header("Location: view_events.php?status=error");
    exit;
}
?>
