<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Edit Student";
$pageKey = "students";
$basePath = "../";
$errorMessage = "";
$studentId = (int) ($_GET["id"] ?? 0);
$years = ["1st Year", "2nd Year", "3rd Year", "4th Year"];
$colleges = get_colleges($conn);

if ($studentId <= 0) {
    set_flash("error", "Invalid student record.");
    redirect_to("view_students.php");
}

$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    set_flash("error", "Student record not found.");
    redirect_to("view_students.php");
}

require_college_access((int) $student["college_id"], "view_students.php");
$selectedCollegeId = get_selected_college_id($_POST, (int) $student["college_id"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $selectedCollegeId = get_selected_college_id($_POST, (int) $student["college_id"]);
    $upload = upload_image("profile_photo", "students", $student["profile_photo"] ?? null);

    if ($name === "" || $email === "" || $department === "" || $year === "" || $phone === "" || $selectedCollegeId <= 0) {
        $errorMessage = "Please complete all required fields.";
    } elseif ($upload["error"] !== "") {
        $errorMessage = $upload["error"];
    } else {
        $updateStmt = $conn->prepare("
            UPDATE students
            SET college_id = ?, name = ?, email = ?, department = ?, year_level = ?, phone = ?, profile_photo = ?
            WHERE student_id = ?
        ");
        $photoPath = $upload["path"];
        $updateStmt->bind_param("issssssi", $selectedCollegeId, $name, $email, $department, $year, $phone, $photoPath, $studentId);
        try {
            $updateStmt->execute();
            $updateStmt->close();
            set_flash("success", "Student profile updated successfully.");
            redirect_to("view_students.php");
        } catch (mysqli_sql_exception $exception) {
            $updateStmt->close();
            $errorMessage = "Unable to update the student profile.";
        }
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Student</h1>
    <p>Update contact details, academic data, college ownership, and profile photo.</p>
</section>

<section class="form-card">
    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="form-grid">
            <?php render_college_select($colleges, $selectedCollegeId); ?>
            <div>
                <label for="name">Student Name</label>
                <input type="text" id="name" name="name" value="<?php echo e($_POST["name"] ?? $student["name"]); ?>" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo e($_POST["email"] ?? $student["email"]); ?>" required>
            </div>
            <div>
                <label for="department">Department</label>
                <input type="text" id="department" name="department" value="<?php echo e($_POST["department"] ?? $student["department"]); ?>" required>
            </div>
            <div>
                <label for="year">Year</label>
                <?php $selectedYear = $_POST["year"] ?? $student["year_level"]; ?>
                <select id="year" name="year" required>
                    <option value="">Select Year</option>
                    <?php foreach ($years as $option): ?>
                        <option value="<?php echo e($option); ?>" <?php echo $selectedYear === $option ? "selected" : ""; ?>><?php echo e($option); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone" value="<?php echo e($_POST["phone"] ?? $student["phone"]); ?>" required>
            </div>
            <div>
                <label for="profile_photo">Profile Photo</label>
                <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                <?php if (!empty($student["profile_photo"])): ?>
                    <p class="help-text">Current file: <?php echo e($student["profile_photo"]); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="action-group">
            <button type="submit">Update Student</button>
            <a class="btn-light" href="view_students.php">Back</a>
        </div>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
