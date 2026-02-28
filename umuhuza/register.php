<?php
// register.php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($username)) $errors[] = 'Username is required.';
    elseif (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';

    if (empty($email)) $errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';

    if (empty($password)) $errors[] = 'Password is required.';
    elseif (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
    elseif (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';

    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicates
        $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);
            $success = 'Account created successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — UMUHUZA Cooperative</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">
<div class="auth-card" style="max-width:480px;">
    <div class="auth-logo">
        <div class="logo-icon">🌽</div>
        <h1>Create Admin Account</h1>
        <p>UMUHUZA Cooperative</p>
    </div>

    <?php foreach ($errors as $err): ?>
        <div class="alert alert-error">✕ <?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">✓ <?= e($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control"
                   placeholder="Enter username" value="<?= e($_POST['username'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control"
                   placeholder="admin@example.com" value="<?= e($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group" style="margin-bottom:14px;">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control"
                   placeholder="Min 8 chars, 1 uppercase, 1 number" required>
        </div>
        <div class="form-group" style="margin-bottom:24px;">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="confirm_password" class="form-control"
                   placeholder="Re-enter password" required>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center; padding:12px;">
            ✅ Create Account
        </button>
    </form>

    <p style="text-align:center; margin-top:20px; font-size:13px; color:var(--gray-500);">
        Already have an account? <a href="login.php" style="color:var(--green); font-weight:600;">Login here</a>
    </p>
</div>
</body>
</html>
