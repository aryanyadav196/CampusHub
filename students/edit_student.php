<?php
require_once "../db_connect.php";

$pageTitle = "Edit Student";
$basePath = "../";
$errorMessage = "";
$studentId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$student = null;

if ($studentId <= 0) {
    header("Location: view_students.php?status=invalid");
    exit;
}

try {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = trim($_POST["name"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $department = trim($_POST["department"] ?? "");
        $year = trim($_POST["year"] ?? "");
        $phone = trim($_POST["phone"] ?? "");

        if ($name === "" || $email === "" || $department === "" || $year === "" || $phone === "") {
            $errorMessage = "Please fill in all fields.";
        } else {
            $updateStmt = $conn->prepare("UPDATE students SET name = ?, email = ?, department = ?, `year` = ?, phone = ? WHERE student_id = ?");
            $updateStmt->bind_param("sssssi", $name, $email, $department, $year, $phone, $studentId);
            $updateStmt->execute();
            $updateStmt->close();

            header("Location: view_students.php?status=updated");
            exit;
        }
    }

    $stmt = $conn->prepare("SELECT student_id, name, email, department, `year`, phone FROM students WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();

    if (!$student) {
        header("Location: view_students.php?status=invalid");
        exit;
    }
} catch (mysqli_sql_exception $exception) {
    $errorMessage = "Unable to load or update the student record.";
}

require_once "../includes/header.php";
?>

<section class="page-heading">
    <h1>Edit Student</h1>
    <p>Update the student profile and academic information.</p>
</section>

<section class="form-card">
    <?php if ($errorMessage !== ""): ?>
        <div class="error"><?php echo e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post">
        <div>
            <label for="name">Student Name</label>
            <input type="text" id="name" name="name" value="<?php echo e($_POST["name"] ?? ($student["name"] ?? "")); ?>">
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo e($_POST["email"] ?? ($student["email"] ?? "")); ?>">
        </div>

        <div>
            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo e($_POST["department"] ?? ($student["department"] ?? "")); ?>">
        </div>

        <div>
            <label for="year">Year</label>
            <?php $selectedYear = $_POST["year"] ?? ($student["year"] ?? ""); ?>
            <select id="year" name="year">
                <option value="">Select Year</option>
                <option value="1st Year" <?php echo ($selectedYear === "1st Year") ? "selected" : ""; ?>>1st Year</option>
                <option value="2nd Year" <?php echo ($selectedYear === "2nd Year") ? "selected" : ""; ?>>2nd Year</option>
                <option value="3rd Year" <?php echo ($selectedYear === "3rd Year") ? "selected" : ""; ?>>3rd Year</option>
                <option value="4th Year" <?php echo ($selectedYear === "4th Year") ? "selected" : ""; ?>>4th Year</option>
            </select>
        </div>

        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo e($_POST["phone"] ?? ($student["phone"] ?? "")); ?>">
        </div>

        <button type="submit">Update Student</button>
    </form>
</section>

<?php require_once "../includes/footer.php"; ?>
