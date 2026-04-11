<?php
require_once __DIR__ . "/../db_connect.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set("Asia/Calcutta");

const CAMPUSHUB_UPLOAD_IMAGE_TYPES = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp",
    "image/gif" => "gif",
];

function app_root(): string
{
    return dirname(__DIR__);
}

function redirect_to(string $path): void
{
    header("Location: " . $path);
    exit;
}

function set_flash(string $type, string $message): void
{
    $_SESSION["flash_messages"][] = [
        "type" => $type,
        "message" => $message,
    ];
}

function get_flash_messages(): array
{
    $messages = $_SESSION["flash_messages"] ?? [];
    unset($_SESSION["flash_messages"]);
    return is_array($messages) ? $messages : [];
}

function current_user(): ?array
{
    return $_SESSION["user"] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    return (current_user()["role"] ?? "") === "admin";
}

function current_college_id(): int
{
    return (int) (current_user()["college_id"] ?? 0);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $redirect = urlencode($_SERVER["REQUEST_URI"] ?? "/index.php");
        redirect_to((defined("APP_BASE_PATH") ? APP_BASE_PATH : "") . "login.php?redirect=" . $redirect);
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect_to((defined("APP_BASE_PATH") ? APP_BASE_PATH : "") . "index.php");
    }
}

function normalize_redirect_path(string $path, string $fallback = "index.php"): string
{
    if ($path === "" || str_contains($path, "://")) {
        return $fallback;
    }

    if (!str_starts_with($path, "/") && !str_ends_with($path, ".php") && !str_contains($path, ".php?")) {
        return $fallback;
    }

    return ltrim($path, "/");
}

function get_colleges(mysqli $conn): array
{
    $result = $conn->query("SELECT college_id, college_name, college_code, city FROM colleges ORDER BY college_name ASC");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function get_selected_college_id(array $source, int $defaultCollegeId = 0): int
{
    if (is_admin()) {
        return (int) ($source["college_id"] ?? $defaultCollegeId);
    }

    return current_college_id() ?: $defaultCollegeId;
}

function can_access_college(int $collegeId): bool
{
    return is_admin() || $collegeId === current_college_id();
}

function require_college_access(int $collegeId, string $redirectPath): void
{
    if (!can_access_college($collegeId)) {
        set_flash("error", "You do not have access to that record.");
        redirect_to($redirectPath);
    }
}

function render_college_select(array $colleges, int $selectedCollegeId, string $label = "College"): void
{
    if (!is_admin()) {
        return;
    }
    ?>
    <div>
        <label for="college_id"><?php echo e($label); ?></label>
        <select id="college_id" name="college_id" required>
            <option value="">Select College</option>
            <?php foreach ($colleges as $college): ?>
                <option value="<?php echo (int) $college["college_id"]; ?>" <?php echo ((int) $college["college_id"] === $selectedCollegeId) ? "selected" : ""; ?>>
                    <?php echo e($college["college_name"] . " (" . $college["college_code"] . ")"); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php
}

function app_upload_directory(string $folder): string
{
    $path = app_root() . "/uploads/" . trim($folder, "/");
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    return $path;
}

function delete_uploaded_file(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    $fullPath = app_root() . "/" . ltrim($relativePath, "/");
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

function upload_image(string $fieldName, string $folder, ?string $existingFile = null): array
{
    if (!isset($_FILES[$fieldName]) || (int) $_FILES[$fieldName]["error"] === UPLOAD_ERR_NO_FILE) {
        return [
            "path" => $existingFile,
            "error" => "",
        ];
    }

    $file = $_FILES[$fieldName];

    if ((int) $file["error"] !== UPLOAD_ERR_OK) {
        return [
            "path" => $existingFile,
            "error" => "Unable to upload file.",
        ];
    }

    if ((int) $file["size"] > 2 * 1024 * 1024) {
        return [
            "path" => $existingFile,
            "error" => "Image size must be under 2 MB.",
        ];
    }

    $mimeType = mime_content_type($file["tmp_name"]) ?: "";
    if (!isset(CAMPUSHUB_UPLOAD_IMAGE_TYPES[$mimeType])) {
        return [
            "path" => $existingFile,
            "error" => "Only JPG, PNG, WEBP, and GIF images are allowed.",
        ];
    }

    $extension = CAMPUSHUB_UPLOAD_IMAGE_TYPES[$mimeType];
    $fileName = uniqid($folder . "_", true) . "." . $extension;
    $directory = app_upload_directory($folder);
    $destination = $directory . "/" . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        return [
            "path" => $existingFile,
            "error" => "Failed to store the uploaded image.",
        ];
    }

    if ($existingFile && $existingFile !== "uploads/" . trim($folder, "/") . "/" . $fileName) {
        delete_uploaded_file($existingFile);
    }

    return [
        "path" => "uploads/" . trim($folder, "/") . "/" . $fileName,
        "error" => "",
    ];
}

function format_date_label(?string $date): string
{
    if (!$date) {
        return "-";
    }

    $timestamp = strtotime($date);
    return $timestamp ? date("d M Y", $timestamp) : $date;
}

function sync_book_status(mysqli $conn, int $bookId): void
{
    $stmt = $conn->prepare("
        SELECT
            total_copies,
            available_copies,
            (
                SELECT COUNT(*)
                FROM book_issue
                WHERE book_id = ? AND status = 'issued'
            ) AS active_issues,
            (
                SELECT COUNT(*)
                FROM book_issue
                WHERE book_id = ? AND status = 'returned'
            ) AS returned_issues
        FROM library_books
        WHERE book_id = ?
    ");
    $stmt->bind_param("iii", $bookId, $bookId, $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) {
        return;
    }

    $status = "available";
    if ((int) $book["active_issues"] > 0 && (int) $book["available_copies"] < (int) $book["total_copies"]) {
        $status = "issued";
    } elseif ((int) $book["returned_issues"] > 0) {
        $status = "returned";
    }

    $updateStmt = $conn->prepare("UPDATE library_books SET status = ? WHERE book_id = ?");
    $updateStmt->bind_param("si", $status, $bookId);
    $updateStmt->execute();
    $updateStmt->close();
}

function get_status_badge_class(string $status): string
{
    return match ($status) {
        "issued" => "badge badge-warning",
        "returned" => "badge badge-success",
        default => "badge badge-info",
    };
}

function render_empty_state(string $title, string $text): void
{
    ?>
    <div class="empty-panel">
        <h3><?php echo e($title); ?></h3>
        <p><?php echo e($text); ?></p>
    </div>
    <?php
}
