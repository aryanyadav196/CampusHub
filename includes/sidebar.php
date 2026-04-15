<?php
$navItems = [
    "dashboard" => ["label" => "Dashboard", "href" => $basePath . "index.php", "icon" => "dashboard"],
    "students" => ["label" => "Students", "href" => $basePath . "students/view_students.php", "icon" => "students"],
    "library" => ["label" => "Library", "href" => $basePath . "library/view_books.php", "icon" => "library"],
    "events" => ["label" => "Events", "href" => $basePath . "events/view_events.php", "icon" => "events"],
    "reports" => ["label" => "Reports", "href" => $basePath . "reports.php", "icon" => "reports"],
    "settings" => ["label" => "Settings", "href" => $basePath . "settings.php", "icon" => "settings"],
];

function sidebar_icon(string $key): string
{
    return match ($key) {
        "students" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm-8 1a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-3.314 0-6 1.79-6 4v2h12v-2c0-2.21-2.686-4-6-4Zm8 0c-.29 0-.573.021-.85.06A5.68 5.68 0 0 1 18 18v2h6v-2c0-2.21-2.686-4-6-4Z"/></svg>',
        "library" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h11a3 3 0 0 1 3 3v15H8a3 3 0 0 0-3 3V3Zm3 18h9V6a1 1 0 0 0-1-1H7v16a2.99 2.99 0 0 1 1-.17ZM4 5v16a3 3 0 0 1 3-3V5H4Z"/></svg>',
        "events" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h2v3H7V2Zm8 0h2v3h-2V2ZM4 5h16a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm0 6v9h16v-9H4Zm4 2h3v3H8v-3Z"/></svg>',
        "reports" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h9l5 5v15a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm8 1.5V8h4.5L14 3.5ZM8 12h8v2H8v-2Zm0 4h8v2H8v-2Zm0-8h5v2H8V8Z"/></svg>',
        "settings" => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19.14 12.94a7.48 7.48 0 0 0 .05-.94 7.48 7.48 0 0 0-.05-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.17 7.17 0 0 0-1.63-.94l-.36-2.54a.5.5 0 0 0-.49-.42h-3.84a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.71 8.84a.5.5 0 0 0 .12.64l2.03 1.58a7.48 7.48 0 0 0-.05.94 7.48 7.48 0 0 0 .05.94l-2.03 1.58a.5.5 0 0 0-.12.64l1.92 3.32a.5.5 0 0 0 .6.22l2.39-.96c.5.4 1.05.72 1.63.94l.36 2.54a.5.5 0 0 0 .49.42h3.84a.5.5 0 0 0 .49-.42l.36-2.54c.58-.23 1.12-.54 1.63-.94l2.39.96a.5.5 0 0 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.58ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"/></svg>',
        default => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3v10Zm10 8h8v-8h-8v8ZM3 21h8v-6H3v6Zm10-10h8V3h-8v8Z"/></svg>',
    };
}
?>
<aside class="sidebar" data-sidebar>
    <div class="sidebar-brand">
        <a href="<?php echo e($basePath); ?>index.php">CampusHub</a>
        <p>Unified operations for students, library, events, and reporting.</p>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a class="sidebar-link <?php echo $pageKey === $key ? "active" : ""; ?>" href="<?php echo e($item["href"]); ?>">
                <span class="sidebar-icon"><?php echo sidebar_icon($item["icon"]); ?></span>
                <span><?php echo e($item["label"]); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-college">
            <span>Active Scope</span>
            <strong><?php echo is_admin() ? "All Colleges" : e(current_user()["college_name"] ?? ""); ?></strong>
        </div>
        <a class="btn btn-light btn-full" href="<?php echo e($basePath); ?>logout.php">Logout</a>
    </div>
</aside>
