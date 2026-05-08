<?php
include 'db.php';
$courseId = intval($_GET['course_id'] ?? 0);
$course = null;
$modules = [];
$message = ($courseId <= 0) ? 'Please select a valid course.' : '';
if ($courseId > 0) {
    // 1. Fetch Course Details
    $stmt = $conn->prepare('SELECT course_name, description, price FROM courses WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if (!$course) {
        $message = 'Course not found.';
    } else {
        // 2. Fetch Modules only if course exists
        $stmt = $conn->prepare('SELECT title, summary, content_url, order_no, created_at FROM course_modules WHERE course_id = ? ORDER BY order_no ASC, created_at DESC');
        $stmt->bind_param('i', $courseId);
        $stmt->execute();
        $modules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Modules - LMS</title>
    <style>
        body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background: #0b1220;
        color: #e2e8ff;
    }
        .page {
        max-width: 1000px;
        margin: 0 auto;
        padding: 32px 20px;
    }
        .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
        gap: 12px;
    }
        .topbar h1 {
        font-size: 2rem;
        margin-bottom: 6px;
    }
        .topbar p {
        color: #94a3b8;
        margin: 0;
    }
        .return-link, .module-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        background: rgba(59,130,246,0.2);
        color: #e2e8ff;
        transition: background 0.2s ease;
    }
        .return-link:hover, .module-action:hover {
        background: rgba(59,130,246,0.4);
    }
        .course-info {
        display: grid;
        gap: 20px;
        margin-bottom: 32px;
    }
        .course-meta {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 12px;
        align-items: center;
    }
        .course-meta span {
        color: #94a3b8;
    }
        .module-list {
        display: grid;
        gap: 18px;
    }
        .module-card {
        padding: 24px;
        border: 1px solid rgba(148,163,184,0.18);
        border-radius: 18px;
        background: rgba(15,23,42,0.85);
    }
        .module-card h2 {
        margin-top: 0;
        margin-bottom: 8px;
        font-size: 1.3rem;
    }
        .module-card p {
        color: #cbd5e1;
        line-height: 1.6;
    }
        .module-card footer {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
        font-size: 0.95rem;
        color: #94a3b8;
    }
        .empty-state {
        padding: 40px 24px;
        border-radius: 18px;
        background: rgba(15,23,42,0.9);
        border: 1px solid rgba(148,163,184,0.18);
        text-align: center;
        color: #94a3b8;
    }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div>
                <h1>Course Modules</h1>
                <p>Browse all modules for the selected course.</p>
            </div>
            <a class="return-link" href="studentpage.php">Back to Courses</a>
        </div>
        <?php if ($message): ?>
            <div class="empty-state"><?= htmlspecialchars($message) ?></div>
        <?php else: ?>
            <div class="course-info">
                <div class="course-meta">
                    <div>
                        <h2><?= htmlspecialchars($course['course_name']) ?></h2>
                        <?php if ($course['description']): ?>
                            <p><?= htmlspecialchars($course['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <span>Course price: <?= number_format($course['price'], 2) ?></span>
                </div>
            </div>
            <?php if (!$modules): ?>
                <div class="empty-state"><p>No modules added yet.</p></div>
            <?php else: ?>
                <div class="module-list">
                    <?php foreach ($modules as $m): ?>
                        <article class="module-card">
                            <h2><?= htmlspecialchars($m['title']) ?></h2>
                            <p><?= htmlspecialchars($m['summary']) ?></p>
                            <footer>
                                <span>Order: <?= intval($m['order_no']) ?></span>
                                <?php if ($m['content_url']): ?>
                                    <a class="module-action" href="<?= htmlspecialchars($m['content_url']) ?>" target="_blank">Open Content</a>
                                <?php endif; ?>
                                <span>Added: <?= date('M j, Y', strtotime($m['created_at'])) ?></span>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>