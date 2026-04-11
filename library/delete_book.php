<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$bookId = (int) ($_GET["id"] ?? 0);
if ($bookId <= 0) {
    set_flash("error", "Invalid book record.");
    redirect_to("view_books.php");
}

$selectStmt = $conn->prepare("SELECT college_id FROM library_books WHERE book_id = ?");
$selectStmt->bind_param("i", $bookId);
$selectStmt->execute();
$book = $selectStmt->get_result()->fetch_assoc();
$selectStmt->close();

if (!$book) {
    set_flash("error", "Book record not found.");
    redirect_to("view_books.php");
}

require_college_access((int) $book["college_id"], "view_books.php");

$checkStmt = $conn->prepare("SELECT COUNT(*) AS total FROM book_issue WHERE book_id = ? AND status = 'issued'");
$checkStmt->bind_param("i", $bookId);
$checkStmt->execute();
$activeIssues = (int) ($checkStmt->get_result()->fetch_assoc()["total"] ?? 0);
$checkStmt->close();

if ($activeIssues > 0) {
    set_flash("error", "This book cannot be deleted while active issue records exist.");
    redirect_to("view_books.php");
}

$deleteStmt = $conn->prepare("DELETE FROM library_books WHERE book_id = ?");
$deleteStmt->bind_param("i", $bookId);
$deleteStmt->execute();
$deleted = $deleteStmt->affected_rows > 0;
$deleteStmt->close();

set_flash($deleted ? "success" : "error", $deleted ? "Book deleted successfully." : "Unable to delete the book record.");
redirect_to("view_books.php");
