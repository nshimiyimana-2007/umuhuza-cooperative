<?php
// login.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$login, $login]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            setFlash('success', 'Welcome back, ' . $admin['username'] . '!');
            header('Location: pages/dashboard.php');
            exit();
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — UMUHUZA Cooperative</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
<div class="auth-card">
    <div class="auth-logo">
        <div class="logo-icon">🌽</div>
        <h1>UMUHUZA<br>Cooperative</h1>
        <p>Eastern Province, Rwanda</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error">✕ <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Username or Email</label>
            <input type="text" name="login" class="form-control"
                   placeholder="Enter username or email"
                   value="<?= e($_POST['login'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Enter password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">
            🔐 Sign In
        </button>
    </form>

    <p style="text-align:center; margin-top:20px; font-size:13px; color:var(--gray-500);">
        Don't have an account? <a href="register.php" style="color:var(--green); font-weight:600;">Register here</a>
    </p>
    <p style="text-align:center; margin-top:10px; font-size:12px; color:var(--gray-300);">
        Default: admin / Admin@1234
    </p>
</div>
</body>
</html>
