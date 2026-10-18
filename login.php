<?php
session_start();
require_once 'config.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
try {
    $pdo = db();
    $stmt = $pdo->query("SELECT id, username, password_hash, role FROM users WHERE username='admin' LIMIT 1");
    $admin = $stmt->fetch();
    if (!$admin) {
        header('Location: setup.php');
        exit;
    }
} catch (Throwable $e) {
    $error = 'Database is not ready. Run setup.php first.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $stmt = db()->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->execute([trim($_POST['username'] ?? '')]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'] ?? '', $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Library Laptop Management System</title>
<link rel="stylesheet" href="assets/style.css"></head>
<body class="login-bg">
<div class="login-card">
<h1>Library Laptop Management System</h1>
<p class="muted">Please sign in to continue.</p>
<?php if ($error): ?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post">
<label>Username</label><input name="username" required autofocus>
<label>Password</label><input type="password" name="password" required>
<button class="btn primary" type="submit">Login</button>
</form>
<p class="hint">Default administrator: <b>admin</b> / <b>admin123</b></p>
</div>
</body></html>
