<?php
require_once "../db_connect.php";

$bookId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($bookId <= 0) {
    header("Location: view_books.php?status=invalid");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM library_books WHERE book_id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        header("Location: view_books.php?status=deleted");
        exit;
    }

    $stmt->close();
    header("Location: view_books.php?status=invalid");
    exit;
} catch (mysqli_sql_exception $exception) {
    header("Location: view_books.php?status=error");
    exit;
}
?>
