<?php
define("APP_BASE_PATH", "");
require_once __DIR__ . "/includes/app.php";
require_guest();

$errorMessage = "";
$redirectPath = normalize_redirect_path($_GET["redirect"] ?? "index.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $redirectPath = normalize_redirect_path($_POST["redirect"] ?? "index.php");

    if ($email === "" || $password === "") {
        $errorMessage = "Enter both email and password.";
    } else {
        $stmt = $conn->prepare("
            SELECT
                users.user_id,
                users.name,
                users.email,
                users.password_hash,
                users.role,
                users.college_id,
                colleges.college_name
            FROM users
            LEFT JOIN colleges ON colleges.college_id = users.college_id
            WHERE users.email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user["password_hash"])) {
            $errorMessage = "Invalid login credentials.";
        } else {
            $_SESSION["user"] = [
                "user_id" => (int) $user["user_id"],
                "name" => $user["name"],
                "email" => $user["email"],
                "role" => $user["role"],
                "college_id" => (int) ($user["college_id"] ?? 0),
                "college_name" => $user["college_name"] ?? "All Colleges",
            ];
            set_flash("success", "Welcome back, " . $user["name"] . ".");
            redirect_to($redirectPath);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusHub Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <section class="auth-card">
        <div class="auth-showcase">
            <p class="hero-kicker">CampusHub SaaS</p>
            <h2>One system for student records, library operations, events, and reporting.</h2>
            <p>Built for DBMS practicals, structured like a real multi-college campus platform.</p>
            <ul>
                <li>Role-based admin and college access</li>
                <li>Responsive dashboard with analytics</li>
                <li>Centralized student, library, and event workflows</li>
            </ul>
        </div>
        <div class="auth-form">
            <h1>Sign in</h1>
            <p class="muted">Use your CampusHub account to continue.</p>

            <?php if ($errorMessage !== ""): ?>
                <div class="error"><?php echo e($errorMessage); ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="redirect" value="<?php echo e($redirectPath); ?>">
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($_POST["email"] ?? ""); ?>" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Login</button>
            </form>

            <div class="demo-credentials">
                <strong>Demo accounts</strong>
                <p>Admin: `admin@campushub.com` / `admin123`</p>
                <p>College: `north@campushub.com` / `college123`</p>
            </div>
        </div>
    </section>
</body>
</html>
