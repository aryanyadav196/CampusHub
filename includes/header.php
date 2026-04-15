<?php
if (!isset($pageTitle)) {
    $pageTitle = "CampusHub";
}

if (!isset($basePath)) {
    $basePath = "";
}

if (!isset($pageKey)) {
    $pageKey = "dashboard";
}

$flashMessages = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e($basePath); ?>css/style.css">
    <?php if (!empty($loadCharts)): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php endif; ?>
</head>
<body>
<div class="app-shell">
    <?php require __DIR__ . "/sidebar.php"; ?>
    <div class="app-main">
        <header class="topbar">
            <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div>
                <p class="topbar-label">Campus Operations</p>
                <h1 class="topbar-title"><?php echo e($pageTitle); ?></h1>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle" type="button" data-theme-toggle>
                    <span class="theme-toggle-track"></span>
                    <span class="theme-toggle-label">Dark Mode</span>
                </button>
                <div class="user-pill">
                    <strong><?php echo e(current_user()["name"] ?? ""); ?></strong>
                    <span><?php echo e(is_admin() ? "Institution Admin" : "Campus Admin"); ?></span>
                </div>
            </div>
        </header>
        <main class="page-wrap">
            <?php foreach ($flashMessages as $flash): ?>
                <div class="<?php echo ($flash["type"] ?? "") === "success" ? "message toast-message" : "error toast-message"; ?>">
                    <?php echo e($flash["message"] ?? ""); ?>
                </div>
            <?php endforeach; ?>
