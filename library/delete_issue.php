<?php
require_once "../db_connect.php";

$issueId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($issueId <= 0) {
    header("Location: issue_book.php?status=invalid");
    exit;
}

try {
    $conn->begin_transaction();

    $selectStmt = $conn->prepare("SELECT book_id FROM book_issue WHERE issue_id = ?");
    $selectStmt->bind_param("i", $issueId);
    $selectStmt->execute();
    $selectStmt->bind_result($bookId);
    $issueFound = $selectStmt->fetch();
    $selectStmt->close();

    if (!$issueFound) {
        $conn->rollback();
        header("Location: issue_book.php?status=invalid");
        exit;
    }

    $deleteStmt = $conn->prepare("DELETE FROM book_issue WHERE issue_id = ?");
    $deleteStmt->bind_param("i", $issueId);
    $deleteStmt->execute();

    if ($deleteStmt->affected_rows <= 0) {
        $deleteStmt->close();
        $conn->rollback();
        header("Location: issue_book.php?status=invalid");
        exit;
    }

    $deleteStmt->close();

    $updateStmt = $conn->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE book_id = ?");
    $updateStmt->bind_param("i", $bookId);
    $updateStmt->execute();
    $updateStmt->close();

    $conn->commit();
    header("Location: issue_book.php?status=deleted");
    exit;
} catch (mysqli_sql_exception $exception) {
    if ($conn->errno) {
        $conn->rollback();
    }

    header("Location: issue_book.php?status=error");
    exit;
}
?>
