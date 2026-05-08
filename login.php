<?php
include 'db.php';
include 'User.php';
include 'Auth.php';

$auth = new Auth($conn);
$message = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $name = trim($_POST['name'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($password)) {
            $message = 'Both name and password are required.';
        } elseif ($auth->login($name, $password)) {
            header('Location: dashboard.php?view=dashboard');
            exit();
        } else {
            $message = 'Invalid name or password.';
        }
    } elseif (isset($_POST['register'])) {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($name) || empty($phone) || empty($password)) {
            $message = 'All fields are required.';
        } elseif (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters long.';
        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
            $message = 'Please enter a valid phone number.';
        } elseif (strlen($name) < 2) {
            $message = 'Name must be at least 2 characters long.';
        } else {
            $user = new User(null, htmlspecialchars($name), htmlspecialchars($phone));
            $user->setPassword($password);
            if ($user->save($conn)) {
                $message = 'User registered successfully! You can now login.';
                $mode = 'login';
            } else {
                $message = 'Registration failed. User with this name may already exist.';
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
    <title><?php echo $mode == 'login' ? 'Login' : 'Register'; ?> - LMS</title>
    <style>
        *, *::before, *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #eef2ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top, rgba(59, 130, 246, 0.16), transparent 28%),
                        radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.12), transparent 25%),
                        linear-gradient(180deg, #020617 0%, #0b1223 100%);
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image: linear-gradient(135deg, rgba(96, 165, 250, 0.08) 1px, transparent 1px),
                              linear-gradient(225deg, rgba(168, 85, 247, 0.06) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.65;
            pointer-events: none;
        }

        .container {
            position: relative;
            width: min(100%, 420px);
            background: rgba(12, 20, 44, 0.94);
            border: 1px solid rgba(148, 163, 184, 0.18);
            border-radius: 24px;
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.45);
            padding: 36px 32px;
            overflow: hidden;
            animation: panelEnter 0.8s ease-out both;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -40%;
            left: -30%;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.12);
            filter: blur(30px);
            pointer-events: none;
        }

        .container::after {
            content: '';
            position: absolute;
            bottom: -32px;
            right: -32px;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: rgba(168, 85, 247, 0.12);
            filter: blur(24px);
            pointer-events: none;
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            color: #f8fafc;
            text-shadow: 0 0 16px rgba(59, 130, 246, 0.24);
            margin-bottom: 10px;
        }

        .subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        h2 {
            font-size: 1.35rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 24px;
            letter-spacing: 0.02em;
            color: #e2e8f0;
            position: relative;
            z-index: 1;
        }

        h2::after {
            content: '';
            display: block;
            width: 56px;
            height: 2px;
            margin: 14px auto 0;
            background: linear-gradient(90deg, #93c5fd, #c084fc);
            border-radius: 999px;
            opacity: 0.8;
        }

        .message {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 14px 16px;
            margin-bottom: 20px;
            border-radius: 16px;
            font-size: 0.95rem;
            line-height: 1.45;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(248, 113, 113, 0.14);
            color: #f8d7da;
            box-shadow: inset 0 0 0 1px rgba(248, 113, 113, 0.08);
        }

        .message.success {
            border-color: rgba(16, 185, 129, 0.18);
            background: rgba(16, 185, 129, 0.12);
            color: #d1fae5;
        }

        .form-group {
            margin-bottom: 18px;
            opacity: 0;
            animation: fadeInUp 0.65s ease forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.18s; }
        .form-group:nth-child(2) { animation-delay: 0.28s; }
        .form-group:nth-child(3) { animation-delay: 0.38s; }

        label {
            display: block;
            margin-bottom: 10px;
            font-size: 0.82rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.85);
            color: #eef2ff;
            font-size: 0.95rem;
            transition: border-color 0.25s ease, box-shadow 0.25s ease, transform 0.25s ease;
        }

        input::placeholder {
            color: #64748b;
        }

        input:hover,
        input:focus {
            border-color: rgba(56, 189, 248, 0.55);
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
            transform: translateY(-1px);
            outline: none;
            background: rgba(15, 23, 42, 0.95);
        }

        button[type="submit"] {
            width: 100%;
            padding: 15px 16px;
            border: none;
            border-radius: 16px;
            background: linear-gradient(135deg, #0ea5e9, #8b5cf6);
            color: #f8fafc;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            cursor: pointer;
            box-shadow: 0 18px 40px rgba(14, 165, 233, 0.24);
            transition: transform 0.25s ease, box-shadow 0.25s ease, filter 0.25s ease;
            animation: fadeInUp 0.65s ease 0.46s forwards;
            opacity: 0;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 60px rgba(56, 189, 248, 0.28);
            filter: saturate(1.05);
        }

        .auth-switch,
        .back-link {
            position: relative;
            z-index: 1;
            text-align: center;
            margin-top: 18px;
            color: #94a3b8;
            font-size: 0.92rem;
        }

        .auth-switch a,
        .back-link a {
            color: #7dd3fc;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-switch a:hover,
        .back-link a:hover {
            text-decoration: underline;
        }

        .login-mode h2 { color: #7dd3fc; }
        .register-mode h2 { color: #c4b5fd; }

        @keyframes panelEnter {
            from { opacity: 0; transform: translateY(26px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 520px) {
            .container {
                padding: 28px 20px;
                border-radius: 20px;
            }

            .logo {
                font-size: 1.7rem;
            }

            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container <?php echo $mode == 'login' ? 'login-mode' : 'register-mode'; ?>">
        <div class="header">
            <div class="logo">LMS</div>
            <div class="subtitle"><?php echo $mode == 'login' ? 'Welcome Back' : 'Create a new account'; ?></div>
        </div>

        <h2><?php echo $mode == 'login' ? 'Login to your account' : 'Register your account'; ?></h2>

        <?php if ($message): ?>
            <p class="message <?php echo str_contains($message, 'successfully') ? 'success' : ''; ?>">
                <?php echo htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <?php if ($mode == 'login'): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                <button type="submit" name="login">Login</button>
            </form>
            <div class="auth-switch">
                Don't have an account? <a href="?mode=register">Create one</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" placeholder="Enter your phone number" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Minimum 6 characters" required>
                </div>
                <button type="submit" name="register">Register</button>
            </form>
            <div class="auth-switch">
                Already have an account? <a href="?mode=login">Sign in</a>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>