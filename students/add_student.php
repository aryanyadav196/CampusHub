<?php
require_once "../db_connect.php";

$pageTitle = "Add Student";
$basePath = "../";
$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentName = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $phone = trim($_POST["phone"] ?? "");

    if ($studentName === "" || $email === "" || $department === "" || $year === "" || $phone === "") {
        $errorMessage = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO students (name, email, department, `year`, phone) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $studentName, $email, $department, $year, $phone);

        if ($stmt->execute()) {
            $successMessage = "Student record created successfully.";
            $_POST = array();
        } else {
            $errorMessage = "Unable to add student. " . $stmt->error;
        }

        $stmt->close();
    }
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Register Student</h1>
    <p>Create a student profile with academic and contact information.</p>
</section>

<section class="form-card">
    <?php if ($successMessage !== ""): ?>
        <div class="message"><?php echo e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label for="name">Student Name</label>
            <input type="text" id="name" name="name" value="<?php echo e($_POST["name"] ?? ""); ?>">
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo e($_POST["email"] ?? ""); ?>">
        </div>

        <div>
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo e($_POST["department"] ?? ""); ?>">
        </div>

        <div>
            <label for="year">Year</label>
            <select id="year" name="year">
                <option value="">Select Year</option>
                <option value="1st Year" <?php echo (($_POST["year"] ?? "") === "1st Year") ? "selected" : ""; ?>>1st Year</option>
                <option value="2nd Year" <?php echo (($_POST["year"] ?? "") === "2nd Year") ? "selected" : ""; ?>>2nd Year</option>
                <option value="3rd Year" <?php echo (($_POST["year"] ?? "") === "3rd Year") ? "selected" : ""; ?>>3rd Year</option>
                <option value="4th Year" <?php echo (($_POST["year"] ?? "") === "4th Year") ? "selected" : ""; ?>>4th Year</option>
            </select>
        </div>

        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo e($_POST["phone"] ?? ""); ?>">
        </div>

        <button type="submit">Create Record</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
