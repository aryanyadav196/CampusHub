<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_login();

$pageTitle = "Settings";
$pageKey = "settings";
$basePath = "";
$errorMessage = "";
$successMessage = "";
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '') {
        $errorMessage = 'Display name is required.';
    } else {
        $stmt = $conn->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
        $dbUser = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($newPassword !== '' || $confirmPassword !== '' || $currentPassword !== '') {
            if ($currentPassword === '' || !$dbUser || !password_verify($currentPassword, $dbUser['password_hash'])) {
                $errorMessage = 'Enter the correct current password to change it.';
            } elseif (strlen($newPassword) < 8) {
                $errorMessage = 'New password must be at least 8 characters long.';
            } elseif ($newPassword !== $confirmPassword) {
                $errorMessage = 'New password and confirmation do not match.';
            }
        }

        if ($errorMessage === '') {
            if ($newPassword !== '') {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare('UPDATE users SET name = ?, password_hash = ? WHERE id = ?');
                $updateStmt->bind_param('ssi', $name, $newHash, $user['id']);
            } else {
                $updateStmt = $conn->prepare('UPDATE users SET name = ? WHERE id = ?');
                $updateStmt->bind_param('si', $name, $user['id']);
            }

            $updateStmt->execute();
            $updateStmt->close();
            $_SESSION['user']['name'] = $name;
            $user = current_user();
            $successMessage = 'Settings updated successfully.';
        }
    }
}

require_once "includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Settings</h1>
        <p>Manage account details, assistant readiness, and workspace preferences.</p>
    </div>
</section>

<section class="split-layout">
    <article class="form-card">
        <h2>Account Details</h2>
        <?php if ($successMessage !== ''): ?><div class="message"><?php echo e($successMessage); ?></div><?php endif; ?>
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post">
            <div class="form-grid">
                <div>
                    <label for="name">Display Name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($_POST['name'] ?? $user['name']); ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" value="<?php echo e($user['email']); ?>" disabled>
                </div>
                <div>
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" placeholder="Required to change password">
                </div>
                <div>
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                </div>
                <div>
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Save Settings</button>
                <a class="btn-light" href="index.php">Back to Dashboard</a>
            </div>
        </form>
    </article>

    <article class="insight-card">
        <h2>Workspace Details</h2>
        <ul class="insight-list">
            <li>Role: <?php echo e(is_admin() ? 'Institution Admin' : 'Campus Admin'); ?></li>
            <li>Scope: <?php echo e(is_admin() ? 'All campuses' : ($user['college_name'] ?? 'Assigned campus')); ?></li>
            <li>Theme switching is available from the top bar and saved in the browser.</li>
            <li>Assistant status: <?php echo openai_is_configured() ? 'OpenAI connection configured.' : 'Database-backed replies available; add OPENAI_API_KEY in .env for AI responses.'; ?></li>
        </ul>
    </article>
</section>

<section class="summary-grid" style="margin-top: 24px;">
    <article class="summary-card">
        <p class="stat-label">Assistant</p>
        <h2>Live Data Access</h2>
        <p class="muted">Questions about students, books, and events are answered from current records.</p>
    </article>
    <article class="summary-card">
        <p class="stat-label">Appearance</p>
        <h2>Adaptive Theme</h2>
        <p class="muted">The interface switches between light and dark mode without leaving the page.</p>
    </article>
    <article class="summary-card">
        <p class="stat-label">Security</p>
        <h2>Password Controls</h2>
        <p class="muted">Account name and password changes are handled from this screen.</p>
    </article>
</section>

<?php require_once "includes/footer.php"; ?>
