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
                users.id,
                users.name,
                users.email,
                users.password_hash,
                users.role,
                users.college_id,
                colleges.college_name
            FROM users
            LEFT JOIN colleges ON users.college_id = colleges.id
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
                "id" => (int) $user["id"],
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
    <title>CampusHub Sign In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
    <section class="auth-card">
        <div class="auth-showcase">
            <p class="hero-kicker">Campus Operations</p>
            <h2>Manage student records, library services, events, and reports from one secure workspace.</h2>
            <p>Designed for institutions that need clear workflows, fast access to records, and dependable day-to-day operations.</p>
            <ul>
                <li>Responsive dashboard with live operational metrics</li>
                <li>Integrated student, library, and event modules</li>
                <li>Role-based access for institution and campus administrators</li>
            </ul>
        </div>
        <div class="auth-form">
            <h1>Sign in</h1>
            <p class="muted">Use your account to continue to CampusHub.</p>

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

            <div class="sample-accounts">
                <strong>Sample accounts</strong>
                <p>Institution admin: admin@campushub.com / admin123</p>
                <p>Campus admin: north@campushub.com / college123</p>
            </div>
        </div>
    </section>
</body>
</html>
