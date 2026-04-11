<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$issueId = (int) ($_GET["id"] ?? 0);
if ($issueId <= 0) {
    set_flash("error", "Invalid issue record.");
    redirect_to("issue_book.php");
}

$selectStmt = $conn->prepare("SELECT college_id, book_id, status FROM book_issue WHERE issue_id = ?");
$selectStmt->bind_param("i", $issueId);
$selectStmt->execute();
$issue = $selectStmt->get_result()->fetch_assoc();
$selectStmt->close();

if (!$issue) {
    set_flash("error", "Issue record not found.");
    redirect_to("issue_book.php");
}

require_college_access((int) $issue["college_id"], "issue_book.php");

$conn->begin_transaction();
try {
    $deleteStmt = $conn->prepare("DELETE FROM book_issue WHERE issue_id = ?");
    $deleteStmt->bind_param("i", $issueId);
    $deleteStmt->execute();
    $deleteStmt->close();

    if ($issue["status"] === "issued") {
        $updateStmt = $conn->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id = ?");
        $updateStmt->bind_param("i", $issue["book_id"]);
        $updateStmt->execute();
        $updateStmt->close();
    }

    sync_book_status($conn, (int) $issue["book_id"]);
    $conn->commit();
    set_flash("success", "Issue record deleted successfully.");
} catch (Throwable $throwable) {
    $conn->rollback();
    set_flash("error", "Unable to delete the issue record.");
}

redirect_to("issue_book.php");
