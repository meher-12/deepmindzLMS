<?php
session_start();
include 'db.php';
$enrolledCourseIds = [];

// --- 1. HELPER FUNCTIONS ---
function getExistingColumn($conn, $table, array $candidates, $default = null) {
    $result = $conn->query("SHOW COLUMNS FROM `{$table}`");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = true;
        }
        foreach ($candidates as $candidate) {
            if (isset($columns[$candidate])) {
                return $candidate;
            }
        }
    }
    return $default;
}

// --- 2. CONFIGURATION & COLUMN DETECTION ---
$titleCol = getExistingColumn($conn, 'courses', ['course_name', 'title', 'name'], null);
$descCol = getExistingColumn($conn, 'courses', ['description', 'summary'], null);
$priceCol = getExistingColumn($conn, 'courses', ['price'], null);
$maxStudentsCol = getExistingColumn($conn, 'courses', ['max_students', 'capacity', 'student_limit'], null);

$studentId = !empty($_SESSION['student_id']) ? intval($_SESSION['student_id']) : null;
$message = '';
$showSuccess = false;

// --- 3. ENROLLMENT LOGIC (POST HANDLER) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_course'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    
    if (!$studentId) {
        $message = 'Please log in before enrolling in a course.';
    } elseif ($courseId > 0) {
        // FIX: Changed 'SELECT id' to 'SELECT *' because your enrollment table 
        // might use 'enrollment_id' instead of 'id'
        $check = $conn->prepare('SELECT * FROM enrollments WHERE student_id = ? AND course_id = ? LIMIT 1');
        if ($check) {
            $check->bind_param('ii', $studentId, $courseId);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $message = 'You are already enrolled in this course.';
            } else {
                $query = 'SELECT ' . ($maxStudentsCol ? "{$maxStudentsCol}" : 'NULL') . ' as max_students FROM courses WHERE id = ?';
                $courseCheck = $conn->prepare($query);
                $courseCheck->bind_param('i', $courseId);
                $courseCheck->execute();
                $courseData = $courseCheck->get_result()->fetch_assoc();
                
                $maxCap = $courseData['max_students'] ?? null;

                $countEnroll = $conn->prepare('SELECT COUNT(*) as total FROM enrollments WHERE course_id = ?');
                $countEnroll->bind_param('i', $courseId);
                $countEnroll->execute();
                $currentCount = $countEnroll->get_result()->fetch_assoc()['total'] ?? 0;

                if ($maxCap !== null && $currentCount >= intval($maxCap)) {
                    $message = 'Sorry, this course has reached its enrollment limit.';
                } else {
                    $insert = $conn->prepare('INSERT INTO enrollments (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())');
                    if ($insert) {
                        $insert->bind_param('ii', $studentId, $courseId);
                        if ($insert->execute()) {
                            $showSuccess = true;
                        } else {
                            $message = 'Enrollment failed. Please try again.';
                        }
                    }
                }
            }
        }
    }
}

// --- 4. DATA FETCHING FOR UI ---
$allCourses = [];
if ($titleCol) {
    $selectParts = ["c.id", "c.{$titleCol} AS title"];
    $selectParts[] = $descCol ? "c.{$descCol} AS description" : "'' AS description";
    $selectParts[] = $priceCol ? "c.{$priceCol} AS price" : "0 AS price";
    $selectParts[] = $maxStudentsCol ? "c.{$maxStudentsCol} AS max_students" : "NULL AS max_students";
    
    $query = 'SELECT ' . implode(', ', $selectParts) . ' FROM courses c ORDER BY c.id ASC';
    $res = $conn->query($query);
    if($res) {
        while ($row = $res->fetch_assoc()) {
            $allCourses[] = $row;
        }
    }
}

$courseEnrollmentCounts = [];
$res = $conn->query('SELECT course_id, COUNT(*) as enrolled FROM enrollments GROUP BY course_id');
if($res) {
    while ($row = $res->fetch_assoc()) { 
        $courseEnrollmentCounts[intval($row['course_id'])] = intval($row['enrolled']); 
    }
}

if ($studentId) {
    $res = $conn->query("SELECT course_id FROM enrollments WHERE student_id = $studentId");
    if($res) {
        while ($row = $res->fetch_assoc()) { 
            $enrolledCourseIds[] = intval($row['course_id']); 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS | Course Catalog</title>  
    <style>
        :root {
            --bg-dark: #0b0e14;
            --sidebar-bg: #161b22;
            --card-bg: #1c2128;
            --accent-primary: #7c4dff;
            --accent-secondary: #00d2ff;
            --text-white: #f0f6fc;
            --text-gray: #8b949e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-white);
            line-height: 1.6;
        }
        .course-link {
            color: #ffffff;
            text-decoration: none;
        }
        .course-link:hover {
            text-decoration: underline;
        }
        /* Artistic Success Popup */
        .success-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(8px);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            animation: fadeIn 0.4s ease;
        }
        .success-card {
            background: var(--card-bg);
            padding: 50px;
            border-radius: 24px;
            text-align: center;
            border: 1px solid var(--accent-primary);
            box-shadow: 0 0 30px rgba(124, 77, 255, 0.3);
        }
        .success-card h2 {
        color: var(--accent-secondary);
        margin-bottom: 15px;
    }
        .close-btn {
            background: var(--accent-primary);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 20px;
            font-weight: bold;
        }
        /* Navigation */
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 6%;
            background: var(--sidebar-bg);
            border-bottom: 1px solid #30363d;
        }
        .logo {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--accent-primary);
    }
        .nav-links a {
            color: var(--text-white);
            text-decoration: none;
            margin-left: 25px;
            font-size: 0.9rem;
            opacity: 0.8;
            transition: 0.3s;
        }
        .nav-links a:hover {
        opacity: 1; color: var(--accent-secondary);
    }
        .dropdown {
            position: relative;
            display: inline-block;
        }
        .dropbtn {
            padding: 10px 16px;
            border: none;
            border-radius: 14px;
            background: rgba(59, 130, 246, 0.14);
            color: var(--text-white);
            cursor: pointer;
            font-size: 0.9rem;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            margin-top: 8px;
            background: rgba(28, 33, 40, 0.98);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 14px;
            min-width: 180px;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.25);
            z-index: 50;
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-content a {
            display: block;
            padding: 12px 16px;
            color: var(--text-white);
            text-decoration: none;
            transition: background 0.2s ease;
        }
        .dropdown-content a:hover {
            background: rgba(59, 130, 246, 0.12);
        }
        /* Hero Section */
        .hero {
            padding: 100px 6% 60px;
            text-align: center;
            background: radial-gradient(circle at top, rgba(124, 77, 255, 0.1), transparent);
        }
        .hero h1 {
        font-size: 3.5rem;
        margin-bottom: 20px;
    }
        .hero p {
        color: var(--text-gray);
        font-size: 1.2rem;
        max-width: 600px;
        margin: 0 auto;
    }
        /* Course Grid */
        .container {
        padding: 40px 6%;
    }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #30363d;
            transition: transform 0.3s ease, border-color 0.3s ease;
        }
        .card:hover {
            transform: translateY(-8px);
            border-color: var(--accent-primary);
        }
        .thumb {
            width: 100%;
            height: 180px;
            background-size: cover;
            background-position: center;
        }
        .content {
        padding: 25px;
    }
        .content h3 {
        margin-bottom: 10px;
        font-size: 1.3rem;
    }
        .content p {
        color: var(--text-gray);
        font-size: 0.95rem;
        margin-bottom: 20px;
    }
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .price {
        font-weight: bold; font-size: 1.2rem;
        color: var(--accent-secondary);
    }
        .buy-btn {
            background: linear-gradient(135deg, var(--accent-primary), #4527a0);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }
        .buy-btn:hover {
        filter: brightness(1.2);
    }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    </style>
</head>
<body>
<?php if ($showSuccess): ?>
        <div class="success-overlay" id="overlay">
            <div class="success-card">
                <h2>🎊 Enrollment Successful!</h2>
                <p>Welcome to the course. You can now access all modules.</p>
                <button class="close-btn" onclick="document.getElementById('overlay').style.display='none'">Go to Dashboard</button>
            </div>
        </div>
    <?php endif; ?>
    <nav>
        <div class="logo">LMS•PRO</div>
        <div class="nav-links">
            <a href="student_dashboard.php">Dashboard</a>
            <a href="studentpage.php">Course Catalog</a>
            <?php if (!empty($_SESSION['student_id'])): ?>
                <a href="student_logout.php">Sign Out</a>
            <?php else: ?>
                <div class="dropdown">
                    <button class="dropbtn">Sign in</button>
                    <div class="dropdown-content">
                        <a href="student_login.php?mode=login">Student Login</a>
                        <a href="student_login.php?mode=register">Student Register</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php if (!empty($message)): ?>
        <div style="max-width: 1180px; margin: 20px auto 0; padding: 16px 24px; background: rgba(248,113,113,0.14); color: #fee2e2; border: 1px solid rgba(248,113,113,0.22); border-radius: 16px; font-size: 0.95rem;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <header class="hero">
        <h1>Expand Your Knowledge</h1>
        <p>Choose from our selection of premium courses and start building your future.</p>
    </header>
    <main class="container">
        <div class="grid">
            <?php if (empty($allCourses)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #94a3b8;">
                    <p>No courses available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($allCourses as $course): ?>
                    <div class="card">
                        <div class="content">
                            <h3><a class="course-link" href="course_modules.php?course_id=<?= intval($course['id']) ?>"><?= htmlspecialchars($course['title']) ?></a></h3>
                            <?php if (!empty($course['description'])): ?>
                                <p><?= htmlspecialchars($course['description']) ?></p>
                            <?php endif; ?>
                            <div class="footer">
                                <span class="price"><?= number_format($course['price'], 2) ?></span>
                                <?php if (!empty($course['max_students'])): ?>
                                    <span class="price" style="font-size:0.95rem; color:#94a3b8; margin-left:12px;"><?php echo intval($course['max_students']); ?> students</span>
                                <?php endif; ?>
                                <?php 
                                $currentEnrollment = $courseEnrollmentCounts[intval($course['id'])] ?? 0;
                                $maxCapacity = !empty($course['max_students']) ? intval($course['max_students']) : null;
                                $isCourseFull = $maxCapacity !== null && $currentEnrollment >= $maxCapacity;
                                ?>
                                <?php if ($studentId): ?>
                                    <?php if (in_array(intval($course['id']), $enrolledCourseIds, true)): ?>
                                        <button class="buy-btn" type="button" disabled style="background: rgba(59,130,246,0.3); cursor: default;">Enrolled</button>
                                    <?php elseif ($isCourseFull): ?>
                                        <button class="buy-btn" type="button" disabled style="background: rgba(239,68,68,0.3); cursor: default;">Course Filled</button>
                                    <?php else: ?>
                                        <form method="POST" action="studentpage.php" style="display:inline-block; margin:0;">
                                            <input type="hidden" name="course_id" value="<?= intval($course['id']) ?>">
                                            <button type="submit" name="buy_course" class="buy-btn">Enroll Now</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="student_login.php?mode=login" class="buy-btn">Login to Enroll</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>