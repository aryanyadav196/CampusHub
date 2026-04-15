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

function env_value(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($value === false || $value === null || $value === "") {
        return $default;
    }

    return (string) $value;
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
    if ($path === "" || str_contains($path, "://") || str_starts_with($path, "//")) {
        return $fallback;
    }

    if (!str_contains($path, ".php")) {
        return $fallback;
    }

    return ltrim($path, "/");
}

function build_query_string(array $params): string
{
    $filtered = array_filter(
        $params,
        static fn ($value) => $value !== null && $value !== ""
    );

    return http_build_query($filtered);
}

function is_valid_email_address(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function is_valid_phone_number(string $phone): bool
{
    return preg_match('/^[0-9+\-\s()]{8,20}$/', $phone) === 1;
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

function render_college_select(array $colleges, int $selectedCollegeId, string $label = "Campus"): void
{
    if (!is_admin()) {
        return;
    }
    ?>
    <div>
        <label for="college_id"><?php echo e($label); ?></label>
        <select id="college_id" name="college_id" required>
            <option value="">Select Campus</option>
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
            "error" => "Unable to upload the selected image.",
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
            "error" => "Only JPG, PNG, WEBP, and GIF files are allowed.",
        ];
    }

    $extension = CAMPUSHUB_UPLOAD_IMAGE_TYPES[$mimeType];
    $fileName = uniqid($folder . "_", true) . "." . $extension;
    $directory = app_upload_directory($folder);
    $destination = $directory . "/" . $fileName;

    if (!move_uploaded_file($file["tmp_name"], $destination)) {
        return [
            "path" => $existingFile,
            "error" => "Failed to store the uploaded file.",
        ];
    }

    $relativePath = "uploads/" . trim($folder, "/") . "/" . $fileName;
    if ($existingFile && $existingFile !== $relativePath) {
        delete_uploaded_file($existingFile);
    }

    return [
        "path" => $relativePath,
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

function format_datetime_label(?string $dateTime): string
{
    if (!$dateTime) {
        return "-";
    }

    $timestamp = strtotime($dateTime);
    return $timestamp ? date("d M Y, h:i A", $timestamp) : $dateTime;
}

function get_status_badge_class(string $status): string
{
    return match ($status) {
        "issued", "active" => "badge badge-warning",
        "returned", "completed", "available" => "badge badge-success",
        default => "badge badge-info",
    };
}

function render_empty_state(string $title, string $text, string $actionHref = "", string $actionLabel = ""): void
{
    ?>
    <div class="empty-panel">
        <h3><?php echo e($title); ?></h3>
        <p><?php echo e($text); ?></p>
        <?php if ($actionHref !== "" && $actionLabel !== ""): ?>
            <a class="btn-light" href="<?php echo e($actionHref); ?>"><?php echo e($actionLabel); ?></a>
        <?php endif; ?>
    </div>
    <?php
}

function sync_book_status(mysqli $conn, int $bookId): void
{
    $stmt = $conn->prepare("SELECT total_copies, available_copies FROM library_books WHERE book_id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $book = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$book) {
        return;
    }

    $status = "available";
    if ((int) $book["available_copies"] < (int) $book["total_copies"]) {
        $status = "issued";
    }

    $historyStmt = $conn->prepare("SELECT COUNT(*) AS total FROM book_issue WHERE book_id = ? AND status = 'returned'");
    $historyStmt->bind_param("i", $bookId);
    $historyStmt->execute();
    $returnedCount = (int) ($historyStmt->get_result()->fetch_assoc()["total"] ?? 0);
    $historyStmt->close();

    if ($returnedCount > 0 && (int) $book["available_copies"] === (int) $book["total_copies"]) {
        $status = "returned";
    }

    $updateStmt = $conn->prepare("UPDATE library_books SET status = ? WHERE book_id = ?");
    $updateStmt->bind_param("si", $status, $bookId);
    $updateStmt->execute();
    $updateStmt->close();
}

function count_table_rows(mysqli $conn, string $table, string $whereSql = "", string $types = "", array $params = []): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM {$table}{$whereSql}");
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);
    $stmt->close();
    return $total;
}

function openai_is_configured(): bool
{
    return env_value("OPENAI_API_KEY") !== null;
}

function call_openai_with_context(string $question, array $context): array
{
    $apiKey = env_value("OPENAI_API_KEY");
    if (!$apiKey) {
        return [
            "ok" => false,
            "message" => "Assistant access is not configured.",
        ];
    }

    if (!function_exists("curl_init")) {
        return [
            "ok" => false,
            "message" => "cURL is required for assistant responses.",
        ];
    }

    $model = env_value("OPENAI_MODEL", "gpt-4.1-mini");
    $payload = [
        "model" => $model,
        "input" => [
            [
                "role" => "system",
                "content" => [
                    [
                        "type" => "input_text",
                        "text" => "You are a concise campus operations assistant. Answer only using the supplied structured context. If context is missing, say so directly.",
                    ],
                ],
            ],
            [
                "role" => "user",
                "content" => [
                    [
                        "type" => "input_text",
                        "text" => "Question: {$question}\n\nContext:\n" . json_encode($context, JSON_PRETTY_PRINT),
                    ],
                ],
            ],
        ],
    ];

    $ch = curl_init("https://api.openai.com/v1/responses");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode >= 400) {
        return [
            "ok" => false,
            "message" => $curlError !== "" ? $curlError : "Assistant service returned an error.",
        ];
    }

    $decoded = json_decode($response, true);
    $outputText = trim((string) ($decoded["output_text"] ?? ""));

    if ($outputText === "" && isset($decoded["output"]) && is_array($decoded["output"])) {
        foreach ($decoded["output"] as $item) {
            foreach (($item["content"] ?? []) as $content) {
                if (($content["type"] ?? "") === "output_text" && !empty($content["text"])) {
                    $outputText .= ($outputText !== "" ? "\n" : "") . $content["text"];
                }
            }
        }
    }

    if ($outputText === "") {
        return [
            "ok" => false,
            "message" => "Assistant response was empty.",
        ];
    }

    return [
        "ok" => true,
        "message" => $outputText,
    ];
}
