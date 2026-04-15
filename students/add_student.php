<?php
define("APP_BASE_PATH", "../");
require_once __DIR__ . "/../includes/app.php";
require_login();

$pageTitle = "Register Student";
$pageKey = "students";
$basePath = "../";
$errorMessage = "";
$colleges = get_colleges($conn);
$selectedCollegeId = get_selected_college_id($_POST, current_college_id());
$years = ["1st Year", "2nd Year", "3rd Year", "4th Year"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentName = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $selectedCollegeId = get_selected_college_id($_POST, current_college_id());
    $upload = upload_image('profile_photo', 'students');

    if ($studentName === '' || $email === '' || $department === '' || $year === '' || $phone === '' || $selectedCollegeId <= 0) {
        $errorMessage = 'Please complete all required fields.';
    } elseif (!is_valid_email_address($email)) {
        $errorMessage = 'Enter a valid email address.';
    } elseif (!is_valid_phone_number($phone)) {
        $errorMessage = 'Enter a valid phone number.';
    } elseif ($upload['error'] !== '') {
        $errorMessage = $upload['error'];
    } else {
        $stmt = $conn->prepare('INSERT INTO students (college_id, name, email, department, year_level, phone, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $photoPath = $upload['path'];
        $stmt->bind_param('issssss', $selectedCollegeId, $studentName, $email, $department, $year, $phone, $photoPath);

        try {
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Student profile created successfully.');
            redirect_to('view_students.php');
        } catch (mysqli_sql_exception $exception) {
            $stmt->close();
            delete_uploaded_file($upload['path']);
            $errorMessage = 'Unable to create the student profile. Make sure the email address is unique.';
        }
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <div>
        <h1>Register Student</h1>
        <p>Create complete student profiles with contact details, academic information, and an optional image.</p>
    </div>
</section>

<nav class="module-tabs">
    <a class="module-tab" href="view_students.php">Directory</a>
    <a class="module-tab active" href="add_student.php">Register Student</a>
</nav>

<section class="split-layout">
    <article class="form-card">
        <?php if ($errorMessage !== ''): ?><div class="error"><?php echo e($errorMessage); ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="form-grid">
                <?php render_college_select($colleges, $selectedCollegeId); ?>
                <div>
                    <label for="name">Student Name</label>
                    <input type="text" id="name" name="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department" value="<?php echo e($_POST['department'] ?? ''); ?>" placeholder="Computer Science, Commerce, MBA" required>
                </div>
                <div>
                    <label for="year">Year</label>
                    <select id="year" name="year" required>
                        <option value="">Select Year</option>
                        <?php foreach ($years as $option): ?>
                            <option value="<?php echo e($option); ?>" <?php echo (($_POST['year'] ?? '') === $option) ? 'selected' : ''; ?>><?php echo e($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?php echo e($_POST['phone'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="profile_photo">Profile Image</label>
                    <input type="file" id="profile_photo" name="profile_photo" accept="image/*">
                    <p class="help-text">Optional. JPG, PNG, WEBP, or GIF up to 2 MB.</p>
                </div>
            </div>
            <div class="action-group">
                <button type="submit">Create Student</button>
                <a class="btn-light" href="view_students.php">Back to Directory</a>
            </div>
        </form>
    </article>

    <article class="insight-card">
        <h2>Profile Standards</h2>
        <ul class="insight-list">
            <li>Use a valid institutional email so communication records stay consistent.</li>
            <li>Phone validation accepts local and formatted numbers used by staff teams.</li>
            <li>Profile images make the directory easier to scan during campus operations.</li>
            <li>Campus assignment follows the signed-in user's access scope automatically.</li>
        </ul>
    </article>
</section>

<?php require_once "../includes/footer.php"; ?>
