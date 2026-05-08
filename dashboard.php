<?php
include 'db.php';
include 'User.php';
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
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    public function save($conn) {
        $stmt = $conn->prepare('SELECT id FROM students WHERE email = ? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $this->email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            return false;
        }
        $stmt = $conn->prepare('INSERT INTO students (name, email, password) VALUES (?, ?, ?)');
        if (!$stmt) return false;
        $stmt->bind_param('sss', $this->name, $this->email, $this->password);
        return $stmt->execute();
    }
    public function update($conn) {
        $stmt = $conn->prepare('UPDATE students SET name = ?, email = ? WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('ssi', $this->name, $this->email, $this->id);
        return $stmt->execute();
    }
    public static function findById($conn, $id) {
        $stmt = $conn->prepare('SELECT id, name, email FROM students WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) return null;
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return null;
        $row = $result->fetch_assoc();
        return new Student($row['id'], $row['name'], $row['email']);
    }
    public static function findByEmail($conn, $email) {
        $stmt = $conn->prepare('SELECT id, name, email, password FROM students WHERE email = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('s', $email);
        if (!$stmt->execute()) return null;
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return null;
        $row = $result->fetch_assoc();
        return new Student($row['id'], $row['name'], $row['email'], $row['password']);
    }
    public static function getAll($conn) {
        $stmt = $conn->prepare('SELECT id, name, email FROM students ORDER BY id DESC');
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function deleteById($conn, $id) {
        $stmt = $conn->prepare('DELETE FROM students WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
class Course {
    private $id;
    private $name;
    private $description;
    private $price;
    private $studentCount;
    public function __construct($id = null, $name = '', $description = '', $price = 0.0, $studentCount = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->studentCount = $studentCount;
    }
    public function getId() {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    public function getDescription() {
        return $this->description;
    }
    public function getPrice() {
        return $this->price;
    }
    public function getStudentCount() {
        return $this->studentCount;
    }
    public static function saveNew($conn, $name, $description, $price, $studentCount) {
        $stmt = $conn->prepare('INSERT INTO courses (course_name, description, price, max_students) VALUES (?, ?, ?, ?)');
        if (!$stmt) return false;
        $stmt->bind_param('ssdi', $name, $description, $price, $studentCount);
        return $stmt->execute();
    }
    public function update($conn) {
        $stmt = $conn->prepare('UPDATE courses SET course_name = ?, description = ?, price = ?, max_students = ? WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('ssdii', $this->name, $this->description, $this->price, $this->studentCount, $this->id);
        return $stmt->execute();
    }
    public static function deleteById($conn, $id) {
        $stmt = $conn->prepare('DELETE FROM courses WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
    public static function findById($conn, $id) {
        $stmt = $conn->prepare('SELECT id, course_name, description, price, max_students FROM courses WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) return null;
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return null;
        $row = $result->fetch_assoc();
        return new Course($row['id'], $row['course_name'], $row['description'], $row['price'], $row['max_students']);
    }
    public static function getAll($conn) {
        $stmt = $conn->prepare('SELECT id, course_name, description, price, max_students FROM courses ORDER BY id DESC');
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
class CourseModule {
    private $id;
    private $courseId;
    private $title;
    private $summary;
    private $contentUrl;
    private $orderNo;
    private $createdAt;
    public function __construct($id = null, $courseId = null, $title = '', $summary = '', $contentUrl = '', $orderNo = 0, $createdAt = null) {
        $this->id = $id;
        $this->courseId = $courseId;
        $this->title = $title;
        $this->summary = $summary;
        $this->contentUrl = $contentUrl;
        $this->orderNo = $orderNo;
        $this->createdAt = $createdAt;
    }
    public function getId() {
        return $this->id;
    }
    public function getCourseId() {
        return $this->courseId;
    }
    public function getTitle() {
        return $this->title;
    }
    public function getSummary() {
        return $this->summary;
    }
    public function getContentUrl() {
        return $this->contentUrl;
    }
    public function getOrderNo() {
        return $this->orderNo;
    }
    public function getCreatedAt() {
        return $this->createdAt;
    }
    public static function getAll($conn, $courseId = null) {
        $query = 'SELECT m.id, m.course_id, m.title, m.summary, m.content_url, m.order_no, m.created_at, c.course_name FROM course_modules m LEFT JOIN courses c ON c.id = m.course_id';
        if ($courseId !== null) {
            $query .= ' WHERE m.course_id = ?';
        }
        $query .= ' ORDER BY m.order_no ASC, m.created_at DESC';
        $stmt = $conn->prepare($query);
        if (!$stmt) return [];
        if ($courseId !== null) {
            $stmt->bind_param('i', $courseId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public static function findById($conn, $id) {
        $stmt = $conn->prepare('SELECT id, course_id, title, summary, content_url, order_no, created_at FROM course_modules WHERE id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) return null;
        $result = $stmt->get_result();
        if ($result->num_rows === 0) return null;
        $row = $result->fetch_assoc();
        return new CourseModule($row['id'], $row['course_id'], $row['title'], $row['summary'], $row['content_url'], $row['order_no'], $row['created_at']);
    }
    public static function saveNew($conn, $courseId, $title, $summary, $contentUrl, $orderNo) {
        $stmt = $conn->prepare('INSERT INTO course_modules (course_id, title, summary, content_url, order_no, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
        if (!$stmt) return false;
        $stmt->bind_param('isssi', $courseId, $title, $summary, $contentUrl, $orderNo);
        return $stmt->execute();
    }
    public function update($conn) {
        $stmt = $conn->prepare('UPDATE course_modules SET course_id = ?, title = ?, summary = ?, content_url = ?, order_no = ? WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('isssii', $this->courseId, $this->title, $this->summary, $this->contentUrl, $this->orderNo, $this->id);
        return $stmt->execute();
    }
    public static function deleteById($conn, $id) {
        $stmt = $conn->prepare('DELETE FROM course_modules WHERE id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
class Dashboard {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function fetchUsers() {
        return User::getAll($this->conn);
    }
    public function fetchStudents() {
        return Student::getAll($this->conn);
    }
    public function fetchCourses() {
        return Course::getAll($this->conn);
    }
    public function deleteUser($id) {
        return User::deleteById($this->conn, $id);
    }
    public function deleteStudent($id) {
        return Student::deleteById($this->conn, $id);
    }
    public function getUser($id) {
        return User::findById($this->conn, $id);
    }
    public function getStudent($id) {
        return Student::findById($this->conn, $id);
    }
    public function getCourse($id) {
        return Course::findById($this->conn, $id);
    }
    public function fetchModules($courseId = null) {
        return CourseModule::getAll($this->conn, $courseId);
    }
    public function getModule($id) {
        return CourseModule::findById($this->conn, $id);
    }
    public function addModule($courseId, $title, $summary, $contentUrl, $orderNo) {
        return CourseModule::saveNew($this->conn, $courseId, $title, $summary, $contentUrl, $orderNo);
    }
    public function updateModule($id, $courseId, $title, $summary, $contentUrl, $orderNo) {
        $module = CourseModule::findById($this->conn, $id);
        if (!$module) {
            return false;
        }
        $module = new CourseModule($id, $courseId, $title, $summary, $contentUrl, $orderNo);
        return $module->update($this->conn);
    }
    public function deleteModule($id) {
        return CourseModule::deleteById($this->conn, $id);
    }
    public function addCourse($name, $description, $price, $studentCount) {
        return Course::saveNew($this->conn, $name, $description, $price, $studentCount);
    }
    public function updateCourse($id, $name, $description, $price, $studentCount) {
        $existingCourse = Course::findById($this->conn, $id);
        if (!$existingCourse) {
            return false;
        }
        $course = new Course($id, $name, $description, $price, $studentCount);
        return $course->update($this->conn);
    }
    public function deleteCourse($id) {
        return Course::deleteById($this->conn, $id);
    }
    public function updateUser($id, $name, $phone, $password = '') {
        $existingUser = User::findById($this->conn, $id);
        if (!$existingUser) {
            return false;
        }
        $user = new User($id, $name, $phone);
        if ($password !== '') {
            $user->setPassword($password);
        }
        return $user->save($this->conn);
    }
    public function updateStudent($id, $name, $email) {
        $existingStudent = Student::findById($this->conn, $id);
        if (!$existingStudent) {
            return false;
        }
        $student = new Student($id, $name, $email);
        return $student->update($this->conn);
    }
}
$dashboard = new Dashboard($conn);
$view = isset($_GET['view']) ? $_GET['view'] : 'dashboard';
$message = isset($_GET['msg']) ? $_GET['msg'] : '';
$editUser = null;
$editStudent = null;
$editCourse = null;
$editModule = null;
$moduleCourseFilter = null;
// Handle user actions
if (isset($_GET['delete_user'])) {
    $deleteId = intval($_GET['delete_user']);
    if ($deleteId > 0) {
        if ($dashboard->deleteUser($deleteId)) {
            header('Location: dashboard.php?view=users&msg=' . urlencode('User deleted successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=users&msg=' . urlencode('Unable to delete user.'));
        exit();
    }
}
// Handle student actions
if (isset($_GET['delete_student'])) {
    $deleteId = intval($_GET['delete_student']);
    if ($deleteId > 0) {
        if ($dashboard->deleteStudent($deleteId)) {
            header('Location: dashboard.php?view=students&msg=' . urlencode('Student deleted successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=students&msg=' . urlencode('Unable to delete student.'));
        exit();
    }
}
if (isset($_GET['delete_course'])) {
    $deleteId = intval($_GET['delete_course']);
    if ($deleteId > 0) {
        if ($dashboard->deleteCourse($deleteId)) {
            header('Location: dashboard.php?view=courses&msg=' . urlencode('Course deleted successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=courses&msg=' . urlencode('Unable to delete course.'));
        exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($id > 0 && $name !== '' && $phone !== '') {
        if ($dashboard->updateUser($id, htmlspecialchars($name), htmlspecialchars($phone), $password)) {
            header('Location: dashboard.php?view=users&msg=' . urlencode('User updated successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=users&msg=' . urlencode('Could not update user.'));
        exit();
    }
    header('Location: dashboard.php?view=users&msg=' . urlencode('Name and phone are required.'));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if ($id > 0 && $name !== '' && $email !== '') {
        if ($dashboard->updateStudent($id, htmlspecialchars($name), htmlspecialchars($email))) {
            header('Location: dashboard.php?view=students&msg=' . urlencode('Student updated successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=students&msg=' . urlencode('Could not update student.'));
        exit();
    }
    header('Location: dashboard.php?view=students&msg=' . urlencode('Name and email are required.'));
    exit();
}

if (isset($_GET['delete_module'])) {
    $deleteId = intval($_GET['delete_module']);
    if ($deleteId > 0) {
        if ($dashboard->deleteModule($deleteId)) {
            header('Location: dashboard.php?view=modules&msg=' . urlencode('Module deleted successfully.'));
            exit();
        }
        header('Location: dashboard.php?view=modules&msg=' . urlencode('Unable to delete module.'));
        exit();
    }
}

if ($view === 'users' && isset($_GET['edit_user'])) {
    $editId = intval($_GET['edit_user']);
    if ($editId > 0) {
        $editUser = $dashboard->getUser($editId);
    }
}
if ($view === 'students' && isset($_GET['edit_student'])) {
    $editId = intval($_GET['edit_student']);
    if ($editId > 0) {
        $editStudent = $dashboard->getStudent($editId);
    }
}

$users = $dashboard->fetchUsers();
$students = $dashboard->fetchStudents();
$courses = $dashboard->fetchCourses();
$modules = $dashboard->fetchModules($moduleCourseFilter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0c0e1a;
            color: #e2e8ff;
            min-height: 100vh;
            font-size: 14px;
        }
        .app {
            display: flex;
            min-height: 100vh;
        }

        /* ── Sidebar ── */
        .side-panel {
            width: 240px;
            background: #0f1225;
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            padding: 28px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .side-panel h2 {
            font-size: 17px;
            font-weight: 600;
            color: #a78bfa;
            margin-bottom: 28px;
            padding: 0 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .side-panel h2::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7c3aed;
            flex-shrink: 0;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: #8b95c9;
            text-decoration: none;
            border-radius: 8px;
            font-size: 13.5px;
            transition: background 0.2s ease, color 0.2s ease;
        }
        .menu-item.active,
        .menu-item:hover {
            background: rgba(124, 58, 237, 0.18);
            color: #e9d5ff;
        }
        .menu-spacer {
            flex: 1;
        }
        .menu-item.logout {
            margin-top: 8px;
            color: #6b7280;
        }
        .menu-item.logout:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #fca5a5;
        }

        /* ── Main content ── */
        .content {
            flex: 1;
            padding: 32px 36px;
            overflow-y: auto;
        }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }
        .page-header h1 {
            font-size: 20px;
            font-weight: 600;
            color: #f1f5ff;
        }
        .user-count-badge {
            background: rgba(124, 58, 237, 0.2);
            color: #c4b5fd;
            font-size: 12px;
            padding: 3px 12px;
            border-radius: 20px;
            border: 1px solid rgba(124, 58, 237, 0.3);
        }

        /* ── Message ── */
        .message {
            margin-bottom: 20px;
            padding: 12px 16px;
            border-radius: 10px;
            background: rgba(124, 58, 237, 0.1);
            border: 1px solid rgba(124, 58, 237, 0.25);
            color: #e9d5ff;
            font-size: 13.5px;
        }

        /* ── Dashboard empty state ── */
        .section-box.empty {
            background: #13162a;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            min-height: 320px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5280;
            font-size: 14px;
        }

        /* ── Table card ── */
        .table-card {
            background: #13162a;
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            overflow: hidden;
        }
        .table-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .table-card-header span {
            font-size: 15px;
            font-weight: 600;
            color: #e2e8ff;
        }
        .table-wrap {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            background: rgba(255, 255, 255, 0.02);
        }
        th {
            padding: 12px 22px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            color: #6b7eb8;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        td {
            padding: 14px 22px;
            font-size: 13.5px;
            color: #c5ceed;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        tbody tr:hover td {
            background: rgba(124, 58, 237, 0.06);
            color: #e2e8ff;
        }
        .id-cell {
            color: #4b5280;
            font-size: 12px;
            font-family: monospace;
        }

        /* ── Action buttons ── */
        .actions {
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-edit {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            border: 1px solid rgba(99, 102, 241, 0.25);
        }
        .btn-edit:hover {
            background: rgba(99, 102, 241, 0.28);
            color: #c7d2fe;
        }
        .btn-delete {
            background: rgba(239, 68, 68, 0.10);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.20);
        }
        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.22);
            color: #fecaca;
        }
        .btn-save {
            background: rgba(124, 58, 237, 0.2);
            color: #c4b5fd;
            border: 1px solid rgba(124, 58, 237, 0.35);
        }
        .btn-save:hover {
            background: rgba(124, 58, 237, 0.35);
            color: #e9d5ff;
        }

        /* ── Edit panel ── */
        .edit-panel {
            margin: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            background: rgba(255, 255, 255, 0.02);
            padding: 24px 22px;
        }
        .edit-panel h3 {
            font-size: 14px;
            font-weight: 600;
            color: #a78bfa;
            margin-bottom: 18px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fields-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }
        .field-group label {
            display: block;
            font-size: 11.5px;
            color: #6b7eb8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .field-group input,
        .field-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.04);
            color: #e2e8ff;
            font-size: 13.5px;
            transition: border-color 0.2s, box-shadow 0.2s;
            resize: vertical;
        }
        .field-group input:focus,
        .field-group textarea:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.5);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }
        .field-group input::placeholder,
        .field-group textarea::placeholder {
            color: #4b5280;
        }
        .field-group.full-width {
            grid-column: span 3;
        }
        .action-panel {
            display: flex;
            gap: 10px;
        }
        /* ── Responsive ── */
        @media (max-width: 900px) {
            .app {
                flex-direction: column;
            }
            .side-panel {
                width: 100%;
                height: auto;
                position: static;
                flex-direction: row;
                flex-wrap: wrap;
                padding: 16px;
                gap: 8px;
            }
            .side-panel h2 {
                width: 100%;
                margin-bottom: 8px;
            }
            .menu-spacer { display: none; }
            .content {
                padding: 20px 16px;
            }
            .fields-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="app">
        <aside class="side-panel">
            <h2>LMS</h2>
            <a class="menu-item <?php echo $view === 'dashboard' ? 'active' : ''; ?>" href="dashboard.php?view=dashboard">Dashboard</a>
            <a class="menu-item <?php echo $view === 'users' ? 'active' : ''; ?>" href="dashboard.php?view=users">Users</a>
            <a class="menu-item <?php echo $view === 'students' ? 'active' : ''; ?>" href="dashboard.php?view=students">Students</a>
            <a class="menu-item <?php echo $view === 'courses' ? 'active' : ''; ?>" href="dashboard.php?view=courses">Courses</a>
            <a class="menu-item <?php echo $view === 'modules' ? 'active' : ''; ?>" href="dashboard.php?view=modules">Modules</a>
            <div class="menu-spacer"></div>
            <a class="menu-item logout" href="logout.php">Logout</a>
        </aside>
        <main class="content">
            <div class="page-header">
                <h1>
                <?php echo $view === 'dashboard' ? 'Dashboard' : ($view === 'students' ? 'Student Management' : ($view === 'courses' ? 'Course Management' : ($view === 'modules' ? 'Module Management' : 'User Management'))); ?>
                </h1>
                <div style="display: flex; gap: 12px; align-items: center;">
                    <?php if ($view === 'users'): ?>
                        <span class="user-count-badge"><?php echo count($users); ?> users</span>
                    <?php elseif ($view === 'students'): ?>
                        <span class="user-count-badge"><?php echo count($students); ?> students</span>
                    <?php elseif ($view === 'courses'): ?>
                        <span class="user-count-badge"><?php echo count($courses); ?> courses</span>
                        <a href="add_course.php" class="btn btn-edit" style="padding: 10px 16px; text-decoration: none; display: inline-flex; align-items: center;">+ Add Course</a>
                    <?php elseif ($view === 'modules'): ?>
                        <span class="user-count-badge"><?php echo count($modules); ?> modules</span>
                        <a href="add_module.php" class="btn btn-edit" style="padding: 10px 16px; text-decoration: none; display: inline-flex; align-items: center;">+ Add Module</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($_GET['msg'])): ?>
                <div class="message"><?php echo htmlspecialchars($_GET['msg']); ?></div>
            <?php endif; ?>
            <?php if ($view === 'dashboard'): ?>
                <div class="section-box empty">
                    <span>Dashboard selected. No content available.</span>
                </div>
            <?php elseif ($view === 'users'): ?>
                <div class="table-card">
                    <div class="table-card-header">
                        <span>All Users</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) === 0): ?>
                                    <tr>
                                        <td colspan="4" style="color: #4b5280; text-align: center; padding: 32px;">No users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="id-cell">#<?php echo str_pad(htmlspecialchars($user['id']), 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn btn-edit" href="dashboard.php?view=users&edit_user=<?php echo $user['id']; ?>">Edit</a>
                                                    <a class="btn btn-delete" href="dashboard.php?view=users&delete_user=<?php echo $user['id']; ?>" onclick="return confirm('Delete this user?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($editUser): ?>
                        <div class="edit-panel">
                            <h3>Editing User #<?php echo htmlspecialchars($editUser->getId()); ?></h3>
                            <form method="POST" action="dashboard.php?view=users">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editUser->getId()); ?>">
                                <div class="fields-row">
                                    <div class="field-group">
                                        <label>Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($editUser->getName()); ?>" required>
                                    </div>
                                    <div class="field-group">
                                        <label>Phone</label>
                                        <input type="text" name="phone" value="<?php echo htmlspecialchars($editUser->getPhone()); ?>" required>
                                    </div>
                                    <div class="field-group">
                                        <label>New Password</label>
                                        <input type="password" name="password" placeholder="Leave blank to keep current">
                                    </div>
                                </div>
                                <div class="action-panel">
                                    <button class="btn btn-save" type="submit" name="update_user">Update User</button>
                                    <a class="btn btn-delete" href="dashboard.php?view=users">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($view === 'students'): ?>
                <div class="table-card">
                    <div class="table-card-header">
                        <span>All Students</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($students) === 0): ?>
                                    <tr>
                                        <td colspan="4" style="color: #4b5280; text-align: center; padding: 32px;">No students found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td class="id-cell">#<?php echo str_pad(htmlspecialchars($student['id']), 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn btn-edit" href="dashboard.php?view=students&edit_student=<?php echo $student['id']; ?>">Edit</a>
                                                    <a class="btn btn-delete" href="dashboard.php?view=students&delete_student=<?php echo $student['id']; ?>" onclick="return confirm('Delete this student?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($editStudent): ?>
                        <div class="edit-panel">
                            <h3>Editing Student #<?php echo htmlspecialchars($editStudent->getId()); ?></h3>
                            <form method="POST" action="dashboard.php?view=students">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($editStudent->getId()); ?>">
                                <div class="fields-row">
                                    <div class="field-group">
                                        <label>Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($editStudent->getName()); ?>" required>
                                    </div>
                                    <div class="field-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($editStudent->getEmail()); ?>" required>
                                    </div>
                                    <div class="field-group">
                                        <label>&nbsp;</label>
                                        <div style="padding-top: 10px; color: #6b7eb8; font-size: 12px;">Password cannot be changed from here</div>
                                    </div>
                                </div>
                                <div class="action-panel">
                                    <button class="btn btn-save" type="submit" name="update_student">Update Student</button>
                                    <a class="btn btn-delete" href="dashboard.php?view=students">Cancel</a>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif ($view === 'courses'): ?>
                <div class="table-card">
                    <div class="table-card-header">
                        <span>All Courses</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Students</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($courses) === 0): ?>
                                    <tr>
                                        <td colspan="6" style="color: #4b5280; text-align: center; padding: 32px;">No courses found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td class="id-cell">#<?php echo str_pad(htmlspecialchars($course['id']), 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                            <td><?php echo htmlspecialchars($course['description']); ?></td>
                                            <td><?php echo number_format(floatval($course['price']), 2); ?></td>
                                            <td><?php echo intval($course['max_students']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn btn-edit" href="add_course.php?edit=<?php echo $course['id']; ?>">Edit</a>
                                                    <a class="btn btn-edit" href="dashboard.php?view=modules&course_id=<?php echo $course['id']; ?>">Modules</a>
                                                    <a class="btn btn-delete" href="dashboard.php?view=courses&delete_course=<?php echo $course['id']; ?>" onclick="return confirm('Delete this course?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif ($view === 'modules'): ?>
                <div class="table-card">
                    <div class="table-card-header">
                        <span>All Modules</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Course</th>
                                    <th>Title</th>
                                    <th>Summary</th>
                                    <th>Order</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($modules) === 0): ?>
                                    <tr>
                                        <td colspan="6" style="color: #4b5280; text-align: center; padding: 32px;">No modules found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($modules as $module): ?>
                                        <tr>
                                            <td class="id-cell">#<?php echo str_pad(htmlspecialchars($module['id']), 3, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($module['course_name'] ?? 'Unassigned'); ?></td>
                                            <td><?php echo htmlspecialchars($module['title']); ?></td>
                                            <td><?php echo htmlspecialchars($module['summary']); ?></td>
                                            <td><?php echo intval($module['order_no']); ?></td>
                                            <td>
                                                <div class="actions">
                                                    <a class="btn btn-edit" href="add_module.php?edit=<?php echo $module['id']; ?>">Edit</a>
                                                    <a class="btn btn-delete" href="dashboard.php?view=modules&delete_module=<?php echo $module['id']; ?>" onclick="return confirm('Delete this module?');">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>