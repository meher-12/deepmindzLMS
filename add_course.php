<?php
include 'db.php';
include 'User.php';

$editCourse = null;
$courseId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;

if ($courseId > 0) {
    $stmt = $conn->prepare('SELECT id, course_name, description, price, student_count FROM courses WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editCourse = $result->fetch_assoc();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_course'])) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $courseName = trim($_POST['course_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $maxStudents = intval($_POST['max_students'] ?? 0);

    if ($courseName === '') {
        $error = 'Course name is required.';
    } else {
        if ($courseId > 0) {
            $stmt = $conn->prepare('UPDATE courses SET course_name = ?, description = ?, price = ?, max_students = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('ssdii', $courseName, $description, $price, $maxStudents, $courseId);
                if ($stmt->execute()) {
                    header('Location: dashboard.php?view=courses&msg=' . urlencode('Course updated successfully.'));
                    exit();
                }
                $error = 'Could not update course.';
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO courses (course_name, description, price, max_students) VALUES (?, ?, ?, ?)');
            if ($stmt) {
                $stmt->bind_param('ssdi', $courseName, $description, $price, $maxStudents);
                if ($stmt->execute()) {
                    header('Location: dashboard.php?view=courses&msg=' . urlencode('Course added successfully.'));
                    exit();
                }
                $error = 'Could not add course.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $editCourse ? 'Edit Course' : 'Add Course'; ?> - LMS</title>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Rajdhani:wght@300;500;700&display=swap');

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Rajdhani', sans-serif;
        background: radial-gradient(circle at center, #1a1a2e 0%, #0c0e1a 100%);
        color: #e2e8ff;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Animated background pulses */
    body::before {
        content: "";
        position: fixed;
        top: -10%;
        right: -10%;
        width: 500px;
        height: 500px;
        background: rgba(124, 58, 237, 0.08);
        filter: blur(100px);
        border-radius: 50%;
        z-index: -1;
        animation: pulse 8s infinite alternate;
    }

    @keyframes pulse {
        0% { opacity: 0.5; transform: scale(1); }
        100% { opacity: 1; transform: scale(1.2); }
    }

    .container {
        max-width: 700px;
        margin: 0 auto;
        padding: 60px 20px;
        animation: slideIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateY(40px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        border-left: 4px solid #00f2ff;
        padding-left: 20px;
    }

    .header h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        text-shadow: 0 0 15px rgba(0, 242, 255, 0.6);
    }

    .header a {
        color: #7c3aed;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
    }

    .header a:hover {
        text-shadow: 0 0 10px #7c3aed;
        letter-spacing: 2px;
    }

    /* Glassmorphism Card with Scanner Effect */
    .form-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
        position: relative;
        overflow: hidden;
    }

    /* Futuristic Scanner Line */
    .form-card::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 1px;
        background: linear-gradient(90deg, transparent, #00f2ff, transparent);
        box-shadow: 0 0 15px #00f2ff;
        animation: scanLine 4s linear infinite;
        opacity: 0.5;
    }

    @keyframes scanLine {
        0% { transform: translateY(-100px); }
        100% { transform: translateY(800px); }
    }

    .error-message {
        background: rgba(255, 71, 87, 0.15);
        border-right: 4px solid #ff4757;
        color: #ff6b81;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-weight: 600;
        animation: shake 0.4s ease;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .form-group {
        margin-bottom: 25px;
        transition: 0.3s;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #00f2ff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1.5px;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        font-family: 'Rajdhani', sans-serif;
        font-size: 1.1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #7c3aed;
        background: rgba(124, 58, 237, 0.05);
        box-shadow: 0 0 20px rgba(124, 58, 237, 0.2), inset 0 0 10px rgba(124, 58, 237, 0.1);
        transform: translateX(5px);
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .action-panel {
        display: flex;
        gap: 15px;
        justify-content: flex-end;
        margin-top: 40px;
    }

    .btn {
        padding: 14px 32px;
        border: none;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: 'Orbitron', sans-serif;
        font-size: 0.8rem;
        position: relative;
        overflow: hidden;
    }

    .btn-save {
        background: #7c3aed;
        color: white;
        box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4);
    }

    .btn-save:hover {
        background: #9061f9;
        transform: scale(1.05);
        box-shadow: 0 0 25px rgba(124, 58, 237, 0.6);
    }

    .btn-cancel {
        background: transparent;
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.3);
    }

    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        border-color: #fff;
    }

    /* Styling number inputs for data feel */
    input[type="number"] {
        font-family: 'Orbitron', sans-serif;
        color: #00f2ff;
        letter-spacing: 1px;
    }

    textarea {
        resize: none;
        min-height: 140px;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><?php echo $editCourse ? 'Edit Course' : 'Add New Course'; ?></h1>
                <p style="color: #94a3b8;">Fill in the course details below</p>
            </div>
            <a href="dashboard.php?view=courses">Back to Courses</a>
        </div>

        <div class="form-card">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="add_course.php<?php echo $editCourse ? '?edit=' . $editCourse['id'] : ''; ?>">
                <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($editCourse ? $editCourse['id'] : 0); ?>">

                <div class="form-group">
                    <label for="course_name">Course Name *</label>
                    <input type="text" id="course_name" name="course_name" value="<?php echo htmlspecialchars($editCourse ? $editCourse['course_name'] : ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo htmlspecialchars($editCourse ? $editCourse['description'] : ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($editCourse ? $editCourse['price'] : '0.00'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="max_students">Maximum Students</label>
                        <input type="number" id="max_students" name="max_students" step="1" min="0" value="<?php echo htmlspecialchars($editCourse ? $editCourse['max_students'] : '0'); ?>">
                    </div>
                </div>

                <div class="action-panel">
                    <a href="dashboard.php?view=courses" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="save_course" class="btn btn-save"><?php echo $editCourse ? 'Update Course' : 'Add Course'; ?></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
