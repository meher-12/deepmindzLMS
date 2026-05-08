<?php
session_start();
include 'db.php';
class Student {
    private $id;
    private $name;
    private $email;
    private $password;


    public function __construct($id = null, $name = '', $email = '', $password = '') {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
    }


    public function getId() {
        return $this->id;
    }


    public function getName() {
        return $this->name;
    }


    public function getEmail() {
        return $this->email;
    }


    public function getPassword() {
        return $this->password;
    }


    public static function findById($conn, $id) {
        $stmt = $conn->prepare('SELECT id, name, email, password FROM students WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) return null;
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return null;
        $row = $result->fetch_assoc();
        return new Student($row['id'], $row['name'], $row['email'], $row['password']);
    }


    private function getTableFieldName($conn, $table, array $options, $default = null) {
        $fields = [];
        $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fields[$row['Field']] = true;
            }
        }


        foreach ($options as $option) {
            if (isset($fields[$option])) {
                return $option;
            }
        }


        return $default;
    }
    public function getEnrolledCourses($conn) {
        $titleField = $this->getTableFieldName($conn, 'courses', ['course_name', 'title', 'name', 'course_title'], 'id');
        $descriptionField = $this->getTableFieldName($conn, 'courses', ['description', 'course_description', 'summary'], null);
        $priceField = $this->getTableFieldName($conn, 'courses', ['price', 'course_price'], null);
        $studentCountField = $this->getTableFieldName($conn, 'courses', ['student_count', 'enrolled_students'], null);
        $enrollmentDateField = $this->getTableFieldName($conn, 'enrollments', ['enrollment_date', 'enrolled_at', 'enrollment_date_time'], null);

        $selectFields = [
            'c.id',
            $titleField ? "c.{$titleField} AS title" : "c.id AS title",
            $descriptionField ? "c.{$descriptionField} AS description" : "'' AS description",
            $priceField ? "c.{$priceField} AS price" : "0 AS price",
            $studentCountField ? "c.{$studentCountField} AS student_count" : "0 AS student_count",
            $enrollmentDateField ? "e.{$enrollmentDateField} AS enrollment_date" : "'0000-00-00 00:00:00' AS enrollment_date"
        ];
        $orderField = $enrollmentDateField ? "e.{$enrollmentDateField}" : 'e.id';
        $query = sprintf(
            'SELECT %s
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE e.student_id = ?
             ORDER BY %s DESC',
            implode(', ', $selectFields),
            $orderField
        );
        $stmt = $conn->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param('i', $this->id);
        if (!$stmt->execute()) return [];
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function updateDetails($conn, $name, $email, $password = null) {
        if ($password !== null && trim($password) !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE students SET name = ?, email = ?, password = ? WHERE id = ?');
            if (!$stmt) return false;
            $stmt->bind_param('sssi', $name, $email, $hashedPassword, $this->id);
        } else {
            $stmt = $conn->prepare('UPDATE students SET name = ?, email = ? WHERE id = ?');
            if (!$stmt) return false;
            $stmt->bind_param('ssi', $name, $email, $this->id);
        }

        if (!$stmt->execute()) {
            return false;
        }

        $this->name = $name;
        $this->email = $email;
        if (isset($hashedPassword)) {
            $this->password = $hashedPassword;
        }
        return true;
    }
}
class Course {
    private static function getTableFieldName($conn, $table, array $options, $default = null) {
        $fields = [];
        $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $fields[$row['Field']] = true;
            }
        }
        foreach ($options as $option) {
            if (isset($fields[$option])) {
                return $option;
            }
        }
        return $default;
    }
    private static function tableExists($conn, $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        return $result && $result->num_rows > 0;
    }

    public static function getModules($conn, $courseId) {
        if (!self::tableExists($conn, 'course_modules')) {
            return [];
        }

        $titleField = self::getTableFieldName($conn, 'course_modules', ['title', 'name', 'module_name', 'lesson_title'], null);
        $summaryField = self::getTableFieldName($conn, 'course_modules', ['summary', 'description', 'module_description'], null);

        $selectFields = [
            $titleField ? "{$titleField} AS title" : "'Untitled module' AS title",
            $summaryField ? "{$summaryField} AS summary" : "'' AS summary"
        ];

        $stmt = $conn->prepare('SELECT ' . implode(', ', $selectFields) . ' FROM course_modules WHERE course_id = ? ORDER BY id ASC');
        if (!$stmt) return [];
        $stmt->bind_param('i', $courseId);
        if (!$stmt->execute()) return [];
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
if (empty($_SESSION['student_id'])) {
    header('Location: student_login.php');
    exit();
}
$student = Student::findById($conn, intval($_SESSION['student_id']));
if (!$student) {
    session_destroy();
    header('Location: student_login.php');
    exit();
}

$updateSuccess = null;
$updateError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $name = trim($_POST['student_name'] ?? '');
    $email = trim($_POST['student_email'] ?? '');
    $password = $_POST['student_password'] ?? null;

    if ($student->updateDetails($conn, $name, $email, $password)) {
        $updateSuccess = 'Details updated successfully.';
        if (!empty($password)) {
            $_SESSION['student_password_raw'] = $password;
        }
    } else {
        $updateError = 'Unable to update details. Please try again.';
    }
}

$enrolledCourses = $student->getEnrolledCourses($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - LMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #070b16;
            color: #e2e8f0;
        }
        .page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 24px;
        }
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 24px;
            padding: 18px 24px;
            background: rgba(15, 23, 42, 0.96);
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }
        .brand h1 {
            font-size: 1.5rem;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
        }
        .brand p {
            color: #94a3b8;
            font-size: 0.95rem;
        }
        .nav-links {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }
        .nav-links a, .nav-links button {
            color: #e2e8f0;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.14);
            transition: background 0.2s ease;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-size: inherit;
        }
        .nav-links a:hover, .nav-links button:hover {
            background: rgba(59, 130, 246, 0.24);
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 26px;
        }
        .summary-card {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 20px;
            padding: 22px;
            border: 1px solid rgba(148, 163, 184, 0.12);
        }
        .summary-card h2 {
            margin-bottom: 14px;
            color: #c7d2fe;
            font-size: 1rem;
        }
        .summary-card p {
            color: #94a3b8;
            line-height: 1.7;
        }
        .status-pill {
            display: inline-flex;
            margin-top: 12px;
            padding: 7px 14px;
            border-radius: 999px;
            background: rgba(124, 58, 237, 0.18);
            color: #ede9fe;
            font-size: 0.95rem;
        }
        .courses {
            display: grid;
            gap: 18px;
        }
        .course-card {
            background: rgba(15, 23, 42, 0.92);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 18px;
            padding: 20px;
        }
        .course-card h3 {
            margin-bottom: 10px;
            color: #f8fafc;
        }
        .course-card p {
            color: #94a3b8;
            line-height: 1.7;
            margin-bottom: 14px;
        }
        .course-card .label {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.14);
            color: #e2e8f0;
            font-size: 0.9rem;
        }
        .module-list {
            list-style: none;
            margin-top: 16px;
            padding-left: 0;
            display: grid;
            gap: 10px;
        }
        .module-list li {
            padding: 12px 14px;
            background: rgba(255, 255, 255, 0.04);
            border-radius: 14px;
            color: #d8e1ff;
        }
        .module-list li strong {
            display: block;
            margin-bottom: 4px;
            color: #eef2ff;
        }
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
            padding: 18px;
        }
        .popup-card {
            max-width: 420px;
            width: 100%;
            background: rgba(15, 23, 42, 0.98);
            border: 1px solid rgba(124, 58, 237, 0.4);
            border-radius: 24px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
        }
        .popup-card h2 {
            margin-bottom: 14px;
            color: #c7d2fe;
            font-size: 1.5rem;
        }
        .popup-card p {
            color: #94a3b8;
            margin-bottom: 16px;
            line-height: 1.7;
        }
        .popup-info {
            text-align: left;
            margin-bottom: 18px;
        }
        .popup-info p {
            margin-bottom: 10px;
        }
        .popup-info strong {
            color: #e2e8f0;
        }
        .popup-form {
            display: none;
            text-align: left;
            margin-top: 12px;
        }
        .popup-form .input-row {
            margin-bottom: 14px;
        }
        .popup-form label {
            display: block;
            margin-bottom: 6px;
            color: #c7d2fe;
            font-size: 0.95rem;
        }
        .popup-form input {
            width: 100%;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            background: rgba(15, 23, 42, 0.92);
            color: #e2e8f0;
        }
        .popup-form input::placeholder {
            color: #7c8fa4;
        }
        .popup-actions,
        .popup-form .form-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 18px;
        }
        .popup-button,
        .popup-close {
            margin-top: 0;
            padding: 12px 22px;
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
        .popup-edit,
        .popup-save {
            background: linear-gradient(135deg, #7c4dff, #00d2ff);
            box-shadow: 0 8px 20px rgba(124, 77, 255, 0.24);
        }
        .popup-edit:hover,
        .popup-save:hover {
            background: linear-gradient(135deg, #6a40e6, #00b0e6);
        }
        .popup-cancel,
        .popup-close {
            background: rgba(59, 130, 246, 0.18);
            color: #e2e8f0;
        }
        .popup-cancel:hover,
        .popup-close:hover {
            background: rgba(59, 130, 246, 0.28);
        }
        .success-message,
        .error-message {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            font-size: 0.95rem;
            text-align: left;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.16);
            color: #d1fae5;
        }
        .error-message {
            background: rgba(248, 113, 113, 0.16);
            color: #fee2e2;
        }
        .hidden {
            display: none;
        }
        @media (max-width: 920px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="popup-overlay hidden" id="student-info-popup">
        <div class="popup-card">
            <h2>Student Details</h2>
            <?php if (!empty($updateSuccess)): ?>
                <p class="success-message"><?php echo htmlspecialchars($updateSuccess); ?></p>
            <?php endif; ?>
            <?php if (!empty($updateError)): ?>
                <p class="error-message"><?php echo htmlspecialchars($updateError); ?></p>
            <?php endif; ?>

            <div class="popup-info" id="student-view-info">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($student->getName()); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($student->getEmail()); ?></p>
    <?php
    // 1. Check if the raw password was just submitted (during registration/update)
    $enteredPassword = $_SESSION['student_password_raw'] ?? '';
    // 2. Fallback: If no raw session exists, it defaults to the DB value
    if ($enteredPassword === '') {
        $enteredPassword = $student->getPassword();
    }
    $maskedPassword = '';
    // 3. Logic to determine if we are looking at a HASH or RAW text
    $isHashed = (substr($enteredPassword, 0, 3) === '$2y');

    if ($enteredPassword !== '') {
        if ($isHashed) {
            // If it's a hash, we can't show the real password characters.
            // Just show a fixed length of asterisks so the hash isn't exposed.
            $maskedPassword = "********"; 
        } else {
            $length = strlen($enteredPassword);
            $visibleCount = 3;

            if ($length <= $visibleCount) {
                $maskedPassword = $enteredPassword;
            } else {
                $startingChars = substr($enteredPassword, 0, $visibleCount);
                $maskedPassword = $startingChars . str_repeat('*', $length - $visibleCount);
            }
        }
    }
?>
<strong>Password:</strong> <?php echo htmlspecialchars($maskedPassword); ?></p>
            </div>

            <form class="popup-form" id="student-edit-form" method="post" action="">
                <input type="hidden" name="update_student" value="1">
                <div class="input-row">
                    <label for="student_name">Name</label>
                    <input id="student_name" name="student_name" type="text" value="<?php echo htmlspecialchars($student->getName()); ?>" required>
                </div>
                <div class="input-row">
                    <label for="student_email">Email</label>
                    <input id="student_email" name="student_email" type="email" value="<?php echo htmlspecialchars($student->getEmail()); ?>" required>
                </div>
                <div class="input-row">
                    <label for="student_password">New Password</label>
                    <input id="student_password" name="student_password" type="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="popup-save">Save</button>
                    <button type="button" class="popup-cancel" onclick="toggleEdit(false)">Cancel</button>
                </div>
            </form>

            <div class="popup-actions">
                <button type="button" class="popup-edit popup-button" onclick="toggleEdit(true)">Edit</button>
                <button type="button" class="popup-close popup-button" onclick="closeStudentPopup()">Close</button>
            </div>
        </div>
    </div>
    <main class="page">
        <nav>
            <div class="brand">
                <h1>Student Dashboard</h1>
                <p>Hello, <?php echo htmlspecialchars($student->getName()); ?>.</p>
            </div>
            <div class="nav-links">
                <a href="studentpage.php">Course Catalog</a>
                <button class="btn btn-save" type="button" onclick="openStudentPopup()">View Info</button>
                <a href="student_logout.php">Sign Out</a>
            </div>
        </nav>


        <section class="summary-grid">
            <article class="summary-card">
                <h2>Enrolled Courses</h2>
                <p>View the courses you are currently enrolled in with access to module details.</p>
                <span class="status-pill"><?php echo count($enrolledCourses); ?> course(s)</span>
            </article>
            <article class="summary-card">
                <h2>Course Modules</h2>
                <p>Each enrolled course includes a module list that appears below for quick reference.</p>
            </article>
        </section>


        <section class="courses">
            <?php if (empty($enrolledCourses)): ?>
                <div class="course-card">
                    <h3>No enrolled courses yet</h3>
                    <p>Return to the course catalog and enroll in a course to see it here on your dashboard.</p>
                </div>
            <?php else: ?>
                <?php foreach ($enrolledCourses as $course): ?>
                    <div class="course-card">
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <?php if (!empty($course['description'])): ?>
                            <p><?php echo htmlspecialchars($course['description']); ?></p>
                        <?php endif; ?>
                        <div class="meta">
                            <?php $enrolledAt = strtotime($course['enrollment_date']); ?>
                            <span class="label">Enrolled on <?php echo htmlspecialchars($enrolledAt ? date('M j, Y', $enrolledAt) : 'Unknown date'); ?></span>
                            <?php if (!empty($course['student_count'])): ?>
                                <span class="label"><?php echo intval($course['student_count']); ?> students</span>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 16px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                            <a class="nav-links" href="course_modules.php?course_id=<?php echo intval($course['id']); ?>" style="background: rgba(59, 130, 246, 0.2); color: #e2e8f0; border-radius: 14px; padding: 10px 16px; text-decoration: none;">View Modules</a>
                        </div>
                        <ul class="module-list">
                            <?php foreach (Course::getModules($conn, $course['id']) as $module): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars($module['title']); ?></strong>
                                    <?php echo htmlspecialchars($module['summary']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
    <script>
        function openStudentPopup() {
            document.getElementById('student-info-popup').classList.remove('hidden');
            toggleEdit(false);
        }

        function closeStudentPopup() {
            document.getElementById('student-info-popup').classList.add('hidden');
        }

        function toggleEdit(enabled) {
            document.getElementById('student-view-info').style.display = enabled ? 'none' : 'block';
            document.getElementById('student-edit-form').style.display = enabled ? 'block' : 'none';
        }
    </script>
</body>
</html>




