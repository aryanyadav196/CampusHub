<?php
$host = "localhost";
$username = "root";
$password = "password";
$database = getenv("DB_NAME") ?: "campushub_db";

if ($password === false) {
    $password = "";
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $exception) {
    die("Database connection failed. Please check MySQL settings in db_connect.php.");
}

if (!function_exists("e")) {
    function e($value): string
    {
        return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
    }
}
