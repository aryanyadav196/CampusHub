<?php
require_once "../db_connect.php";

$studentId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($studentId <= 0) {
    header("Location: view_students.php?status=invalid");
    exit;
}

try {
    $checkStmt = $conn->prepare("SELECT COUNT(*) FROM book_issue WHERE student_id = ?");
    $checkStmt->bind_param("i", $studentId);
    $checkStmt->execute();
    $checkStmt->bind_result($issueCount);
    $checkStmt->fetch();
    $checkStmt->close();

    if ((int) $issueCount > 0) {
        header("Location: view_students.php?status=linked");
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: view_students.php?status=deleted");
        exit;
    }

    $stmt->close();
    header("Location: view_students.php?status=invalid");
    exit;
} catch (mysqli_sql_exception $exception) {
    header("Location: view_students.php?status=error");
    exit;
}
?>
