<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$studentId = (int) ($_GET["id"] ?? 0);

if ($studentId <= 0) {
    set_flash("error", "Invalid student record.");
    redirect_to("view_students.php");
}

$selectStmt = $conn->prepare("SELECT college_id, profile_photo FROM students WHERE student_id = ?");
$selectStmt->bind_param("i", $studentId);
$selectStmt->execute();
$student = $selectStmt->get_result()->fetch_assoc();
$selectStmt->close();

if (!$student) {
    set_flash("error", "Student record not found.");
    redirect_to("view_students.php");
}

require_college_access((int) $student["college_id"], "view_students.php");

$checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM book_issue WHERE student_id = ? AND status = 'issued'");
$checkStmt->bind_param("i", $studentId);
$checkStmt->execute();
$issueCount = (int) ($checkStmt->get_result()->fetch_assoc()["total"] ?? 0);
$checkStmt->close();

if ($issueCount > 0) {
    set_flash("error", "This student cannot be deleted while books are still issued.");
    redirect_to("view_students.php");
}

$deleteStmt = $conn->prepare("DELETE FROM students WHERE student_id = ?");
$deleteStmt->bind_param("i", $studentId);
$deleteStmt->execute();
$deleted = $deleteStmt->affected_rows > 0;
$deleteStmt->close();

if ($deleted) {
    delete_uploaded_file($student["profile_photo"] ?? null);
    set_flash("success", "Student record deleted successfully.");
} else {
    set_flash("error", "Unable to delete the student record.");
}

redirect_to("view_students.php");
