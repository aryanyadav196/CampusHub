<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Add Student";
$pageKey = "students";
$basePath = "../";
$errorMessage = "";
$successMessage = "";
$colleges = get_colleges($conn);
$selectedCollegeId = get_selected_college_id($_POST, current_college_id());
$years = ["1st Year", "2nd Year", "3rd Year", "4th Year"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentName = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $selectedCollegeId = get_selected_college_id($_POST, current_college_id());
    $upload = upload_image("profile_photo", "students");

    if ($studentName === "" || $email === "" || $department === "" || $year === "" || $phone === "" || $selectedCollegeId <= 0) {
        $errorMessage = "Please complete all required fields.";
    } elseif ($upload["error"] !== "") {
        $errorMessage = $upload["error"];
    } else {
        $stmt = $conn->prepare("
            INSERT INTO students (college_id, name, email, department, year_level, phone, profile_photo)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $photoPath = $upload["path"];
        $stmt->bind_param("issssss", $selectedCollegeId, $studentName, $email, $department, $year, $phone, $photoPath);

        try {
            $stmt->execute();
            $stmt->close();
            $_POST = [];
            $selectedCollegeId = current_college_id();
            $successMessage = "Student profile created successfully.";
        } catch (mysqli_sql_exception $exception) {
            $stmt->close();
            delete_uploaded_file($upload["path"]);
            $errorMessage = "Unable to create the student profile. Make sure the email is unique.";
        }
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Register Student</h1>
    <p>Create a polished student record with academic details and profile photo.</p>
</section>

<section class="split-layout">
    <article class="form-card">
        <?php if ($successMessage !== ""): ?>
            <div class="message"><?php echo e($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage !== ""): ?>
            <div class="error"><?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <?php render_college_select($colleges, $selectedCollegeId); ?>
                <div>
                    <label for="name">Student Name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($_POST["name"] ?? ""); ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($_POST["email"] ?? ""); ?>" required>
                </div>
                <div>
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo e($_POST["department"] ?? ""); ?>" placeholder="BCA, MBA, BSc IT" required>
                </div>
                <div>
                    <label for="year">Year</label>
                    <select id="year" name="year" required>
                        <option value="">Select Year</option>
                        <?php foreach ($years as $option): ?>
                            <option value="<?php echo e($option); ?>" <?php echo (($_POST["year"] ?? "") === $option) ? "selected" : ""; ?>>
                                <?php echo e($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e($_POST["phone"] ?? ""); ?>" required>
                </div>
                <div>
                    <label for="profile_photo">Profile Photo</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    <p class="help-text">Optional. Max size 2 MB.</p>
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Create Student</button>
                <a class="btn-light" href="view_students.php">View Students</a>
            </div>
        </form>
    </article>

    <article class="panel">
        <h2>Why this form feels better</h2>
        <p class="muted">The updated student workflow supports college ownership, clean spacing, photo uploads, and a layout suitable for both desktop and mobile demonstrations.</p>
        <div class="card-grid">
            <article class="metric-card">
                <h3 class="card-title">College Ready</h3>
                <p class="muted">Admin can assign a college, while college users stay auto-scoped.</p>
            </article>
            <article class="metric-card">
                <h3 class="card-title">Visual Profiles</h3>
                <p class="muted">Student photos make the directory feel closer to a real system.</p>
            </article>
            <article class="metric-card">
                <h3 class="card-title">Cleaner UX</h3>
                <p class="muted">Better spacing, styling, validation messaging, and responsive structure.</p>
            </article>
        </div>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
