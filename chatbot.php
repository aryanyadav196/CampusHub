<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$message = trim((string) ($input['message'] ?? ''));
if ($message === '') {
    http_response_code(422);
    echo json_encode(['message' => 'Enter a question for the assistant.']);
    exit;
}

$scopeWhere = is_admin() ? '' : ' WHERE college_id = ' . current_college_id();
$scopeAnd = is_admin() ? '' : ' AND college_id = ' . current_college_id();
$lowerMessage = strtolower($message);

$context = [
    'question' => $message,
    'scope' => is_admin() ? 'all campuses' : (current_user()['college_name'] ?? 'assigned campus'),
    'summary' => [
        'students' => count_table_rows($conn, 'students', $scopeWhere),
        'books' => count_table_rows($conn, 'library_books', $scopeWhere),
        'issued_books' => count_table_rows($conn, 'book_issue', is_admin() ? " WHERE status = 'issued'" : " WHERE status = 'issued' AND college_id = " . current_college_id()),
        'events' => count_table_rows($conn, 'events', $scopeWhere),
    ],
    'records' => [],
];

if (str_contains($lowerMessage, 'student')) {
    $studentRows = $conn->query("SELECT name, department, year_level, phone FROM students" . $scopeWhere . " ORDER BY student_id DESC LIMIT 5");
    $context['records']['students'] = $studentRows ? $studentRows->fetch_all(MYSQLI_ASSOC) : [];
}

if (str_contains($lowerMessage, 'book') || str_contains($lowerMessage, 'library') || str_contains($lowerMessage, 'issue')) {
    $bookRows = $conn->query("SELECT book_name, author, category, available_copies, total_copies, status FROM library_books" . $scopeWhere . " ORDER BY book_id DESC LIMIT 5");
    $issueRows = $conn->query("SELECT students.name, library_books.book_name, book_issue.status, book_issue.expected_return_date FROM book_issue INNER JOIN students ON students.student_id = book_issue.student_id INNER JOIN library_books ON library_books.book_id = book_issue.book_id" . (is_admin() ? '' : ' WHERE book_issue.college_id = ' . current_college_id()) . " ORDER BY book_issue.issue_id DESC LIMIT 5");
    $context['records']['books'] = $bookRows ? $bookRows->fetch_all(MYSQLI_ASSOC) : [];
    $context['records']['issues'] = $issueRows ? $issueRows->fetch_all(MYSQLI_ASSOC) : [];
}

if (str_contains($lowerMessage, 'event') || str_contains($lowerMessage, 'calendar')) {
    $eventRows = $conn->query("SELECT event_title, event_date, venue, description FROM events" . $scopeWhere . " ORDER BY event_date ASC, event_id DESC LIMIT 5");
    $context['records']['events'] = $eventRows ? $eventRows->fetch_all(MYSQLI_ASSOC) : [];
}

if (empty($context['records'])) {
    $context['records']['students'] = ($conn->query("SELECT name, department, year_level FROM students" . $scopeWhere . " ORDER BY student_id DESC LIMIT 3")?->fetch_all(MYSQLI_ASSOC)) ?? [];
    $context['records']['books'] = ($conn->query("SELECT book_name, status, available_copies FROM library_books" . $scopeWhere . " ORDER BY book_id DESC LIMIT 3")?->fetch_all(MYSQLI_ASSOC)) ?? [];
    $context['records']['events'] = ($conn->query("SELECT event_title, event_date, venue FROM events" . $scopeWhere . " ORDER BY event_date ASC LIMIT 3")?->fetch_all(MYSQLI_ASSOC)) ?? [];
}

$fallbackParts = [];
$fallbackParts[] = 'Current scope: ' . $context['scope'] . '.';
$fallbackParts[] = 'Students: ' . $context['summary']['students'] . ', Books: ' . $context['summary']['books'] . ', Issued: ' . $context['summary']['issued_books'] . ', Events: ' . $context['summary']['events'] . '.';

if (!empty($context['records']['students'])) {
    $names = array_map(static fn ($row) => $row['name'] . ' (' . ($row['department'] ?? '-') . ')', $context['records']['students']);
    $fallbackParts[] = 'Recent students: ' . implode(', ', array_slice($names, 0, 3)) . '.';
}
if (!empty($context['records']['books'])) {
    $books = array_map(static fn ($row) => $row['book_name'] . ' [' . ($row['status'] ?? '-') . ']', $context['records']['books']);
    $fallbackParts[] = 'Recent books: ' . implode(', ', array_slice($books, 0, 3)) . '.';
}
if (!empty($context['records']['events'])) {
    $events = array_map(static fn ($row) => $row['event_title'] . ' on ' . format_date_label($row['event_date']), $context['records']['events']);
    $fallbackParts[] = 'Upcoming events: ' . implode(', ', array_slice($events, 0, 3)) . '.';
}

$reply = implode("\n", $fallbackParts);
if (openai_is_configured()) {
    $aiResponse = call_openai_with_context($message, $context);
    if ($aiResponse['ok']) {
        $reply = $aiResponse['message'];
    } else {
        $reply .= "\n\nAssistant note: " . $aiResponse['message'];
    }
}

echo json_encode(['message' => $reply]);
