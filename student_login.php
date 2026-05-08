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
}
if (isset($_SESSION['student_id'])) {
    header('Location: studentpage.php');
    exit();
}
$mode = isset($_GET['mode']) && $_GET['mode'] === 'register' ? 'register' : 'login';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $message = 'Please enter both email and password.';
        } else {
            $student = Student::findByEmail($conn, $email);
            if ($student && $student->verifyPassword($password)) {
                $_SESSION['student_id'] = $student->getId();
                $_SESSION['student_password_raw'] = $password;
                header('Location: student_dashboard.php');
                exit();
            }
            $message = 'Invalid credentials. Please try again.';
        }
    } elseif (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $message = 'All fields are required.';
            $mode = 'register';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $mode = 'register';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
            $mode = 'register';
        } else {
            $student = new Student(null, htmlspecialchars($name), htmlspecialchars($email), password_hash($password, PASSWORD_DEFAULT));
            if ($student->save($conn)) {
                $message = 'Registration successful. Please login below.';
                $mode = 'login';
            } else {
                $message = 'Registration failed. Email may already be in use.';
                $mode = 'register';
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
    <title>Student Login - LMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top, rgba(59,130,246,.18), transparent 24%),
                        radial-gradient(circle at bottom, rgba(168,85,247,.12), transparent 24%),
                        linear-gradient(180deg, #020617 0%, #0b1223 100%);
            color: #eef2ff;
        }
        .card {
            width: min(420px, 95%);
            background: rgba(10, 16, 35, 0.95);
            border: 1px solid rgba(148, 163, 184, 0.16);
            border-radius: 24px;
            padding: 34px 32px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.35);
        }
        .logo {
            text-align: center;
            margin-bottom: 22px;
        }
        .logo h1 {
            font-size: 2rem;
            letter-spacing: 0.18em;
            color: #f8fafc;
        }
        .logo p {
            margin-top: 8px;
            color: #94a3b8;
            font-size: 0.95rem;
        }
        h2 {
            font-size: 1.4rem;
            margin-bottom: 18px;
            color: #e2e8f0;
            text-align: center;
        }
        .message {
            margin-bottom: 18px;
            padding: 14px 16px;
            border-radius: 16px;
            background: rgba(248, 113, 113, 0.12);
            border: 1px solid rgba(248, 113, 113, 0.2);
            color: #fee2e2;
            text-align: center;
        }
        .field {
            margin-bottom: 16px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #94a3b8;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid rgba(226, 232, 240, 0.12);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: #eef2ff;
            font-size: 0.95rem;
        }
        input:focus {
            outline: none;
            border-color: rgba(59, 130, 246, 0.6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: rgba(255,255,255,0.08);
        }
        button {
            width: 100%;
            padding: 14px 16px;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #f8fafc;
            background: linear-gradient(135deg, #0ea5e9, #8b5cf6);
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 34px rgba(14, 165, 233, 0.22);
        }
        .footer {
            margin-top: 22px;
            font-size: 0.92rem;
            text-align: center;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <section class="card">
        <div class="logo">
            <h1>LMS Student</h1>
            <p>Login to view your courses and enroll now.</p>
        </div>
        <h2><?php echo $mode === 'register' ? 'Student Register' : 'Student Login'; ?></h2>
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="POST" action="student_login.php?mode=<?php echo $mode; ?>">
            <?php if ($mode === 'register'): ?>
                <div class="field">
                    <label for="name">Name</label>
                    <input id="name" type="text" name="name" placeholder="Your name" required>
                </div>
            <?php endif; ?>
            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" placeholder="student@example.com" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" placeholder="Enter password" required>
            </div>
            <?php if ($mode === 'register'): ?>
                <button type="submit" name="register">Create account</button>
            <?php else: ?>
                <button type="submit" name="login">Sign in</button>
            <?php endif; ?>
        </form>
        <div class="footer">
            <?php if ($mode === 'register'): ?>
                Already have an account? <a href="student_login.php?mode=login" style="color:#60a5fa; text-decoration:none;">Login here</a>.
            <?php else: ?>
                New student? <a href="student_login.php?mode=register" style="color:#60a5fa; text-decoration:none;">Register now</a>.
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
