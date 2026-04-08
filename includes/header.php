<?php
if (!isset($pageTitle)) {
    $pageTitle = "CampusHub";
}

if (!isset($basePath)) {
    $basePath = "";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="logo" href="<?php echo $basePath; ?>index.php">CampusHub</a>
            <nav class="main-nav">
                <a href="<?php echo $basePath; ?>index.php">Home</a>
                <a href="<?php echo $basePath; ?>students/add_student.php">Students</a>
                <a href="<?php echo $basePath; ?>library/add_book.php">Library</a>
                <a href="<?php echo $basePath; ?>events/add_event.php">Events</a>
            </nav>
        </div>
    </header>
    <main class="container page-content">
