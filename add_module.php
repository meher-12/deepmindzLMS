<?php
include 'db.php';
include 'User.php';

$editModule = null;
$moduleId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$courses = [];

// Fetch all courses
$stmt = $conn->prepare('SELECT id, course_name FROM courses ORDER BY course_name ASC');
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
}

if ($moduleId > 0) {
    $stmt = $conn->prepare('SELECT id, course_id, title, summary, content_url, order_no FROM course_modules WHERE id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $moduleId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $editModule = $result->fetch_assoc();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_module'])) {
    $moduleId = intval($_POST['module_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $title = trim($_POST['module_title'] ?? '');
    $summary = trim($_POST['summary'] ?? '');
    $contentUrl = trim($_POST['content_url'] ?? '');
    $orderNo = intval($_POST['order_no'] ?? 0);

    if ($courseId <= 0 || $title === '') {
        $error = 'Course and module title are required.';
    } else {
        if ($moduleId > 0) {
            $stmt = $conn->prepare('UPDATE course_modules SET course_id = ?, title = ?, summary = ?, content_url = ?, order_no = ? WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('isssii', $courseId, $title, $summary, $contentUrl, $orderNo, $moduleId);
                if ($stmt->execute()) {
                    header('Location: dashboard.php?view=modules&msg=' . urlencode('Module updated successfully.'));
                    exit();
                }
                $error = 'Could not update module.';
            }
        } else {
            $stmt = $conn->prepare('INSERT INTO course_modules (course_id, title, summary, content_url, order_no, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $stmt->bind_param('isssi', $courseId, $title, $summary, $contentUrl, $orderNo);
                if ($stmt->execute()) {
                    header('Location: dashboard.php?view=modules&msg=' . urlencode('Module added successfully.'));
                    exit();
                }
                $error = 'Could not add module.';
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
    <title><?php echo $editModule ? 'Edit Module' : 'Add Module'; ?> - LMS</title>
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

    /* Floating background decoration */
    body::before {
        content: "";
        position: fixed;
        top: -10%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(124, 58, 237, 0.1);
        filter: blur(100px);
        border-radius: 50%;
        z-index: -1;
    }

    .container {
        max-width: 700px;
        margin: 0 auto;
        padding: 60px 20px;
        animation: fadeIn 0.8s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        margin-bottom: 40px;
        border-left: 4px solid #7c3aed;
        padding-left: 20px;
    }

    .header h1 {
        font-family: 'Orbitron', sans-serif;
        font-size: 2rem;
        letter-spacing: 2px;
        text-transform: uppercase;
        text-shadow: 0 0 15px rgba(124, 58, 237, 0.6);
    }

    .header a {
        color: #00f2ff;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
    }

    .header a:hover {
        text-shadow: 0 0 10px #00f2ff;
    }

    /* Glassmorphism Card */
    .form-card {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        padding: 40px;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.8);
        position: relative;
        overflow: hidden;
    }

    /* Scanner line animation effect */
    .form-card::after {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, #7c3aed, transparent);
        animation: scan 3s linear infinite;
    }
    @keyframes scan {
        0% { transform: translateY(-100px); }
        100% { transform: translateY(600px); }
    }
    .error-message {
        background: rgba(255, 71, 87, 0.1);
        border-left: 4px solid #ff4757;
        color: #ff6b81;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 0.9rem;
    }
    .form-group {
        margin-bottom: 25px;
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
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 14px 18px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.3);
        color: #fff;
        font-family: 'Rajdhani', sans-serif;
        font-size: 1.1rem;
        transition: all 0.3s ease;
    }
    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #7c3aed;
        background: rgba(124, 58, 237, 0.05);
        box-shadow: 0 0 15px rgba(124, 58, 237, 0.2);
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
        padding: 14px 30px;
        border: none;
        border-radius: 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        font-family: 'Orbitron', sans-serif;
        font-size: 0.8rem;
    }
    .btn-save {
        background: #7c3aed;
        color: white;
        box-shadow: 0 0 20px rgba(124, 58, 237, 0.4);
    }
    .btn-save:hover {
        background: #9061f9;
        transform: scale(1.05);
        box-shadow: 0 0 30px rgba(124, 58, 237, 0.6);
    }
    .btn-cancel {
        background: transparent;
        color: #94a3b8;
        border: 1px solid rgba(148, 163, 184, 0.3);
    }
    .btn-cancel:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }
    /* Custom Scrollbar for Textarea */
    textarea::-webkit-scrollbar {
        width: 8px;
    }
    textarea::-webkit-scrollbar-thumb {
        background: #334155;
        border-radius: 10px;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><?php echo $editModule ? 'Edit Module' : 'Add New Module'; ?></h1>
                <p style="color: #94a3b8;">Fill in the module details below</p>
            </div>
            <a href="dashboard.php?view=modules">Back to Modules</a>
        </div>
        <div class="form-card">
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" action="add_module.php<?php echo $editModule ? '?edit=' . $editModule['id'] : ''; ?>">
                <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($editModule ? $editModule['id'] : 0); ?>">

                <div class="form-group">
                    <label for="course_id">Course *</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select a course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo ($editModule && $editModule['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="module_title">Module Title *</label>
                    <input type="text" id="module_title" name="module_title" value="<?php echo htmlspecialchars($editModule ? $editModule['title'] : ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="summary">Summary</label>
                    <textarea id="summary" name="summary"><?php echo htmlspecialchars($editModule ? $editModule['summary'] : ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="content_url">Content URL</label>
                    <input type="text" id="content_url" name="content_url" value="<?php echo htmlspecialchars($editModule ? $editModule['content_url'] : ''); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="order_no">Display Order</label>
                        <input type="number" id="order_no" name="order_no" step="1" min="0" value="<?php echo htmlspecialchars($editModule ? $editModule['order_no'] : '0'); ?>">
                    </div>
                </div>
                <div class="action-panel">
                    <a href="dashboard.php?view=modules" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="save_module" class="btn btn-save"><?php echo $editModule ? 'Update Module' : 'Add Module'; ?></button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
