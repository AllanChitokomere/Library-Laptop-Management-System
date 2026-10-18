<?php
require_once 'config.php';

$message = '';
$error = '';
try {
    $serverDsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';charset=utf8mb4';
    $pdo = new PDO($serverDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . str_replace('`','',DB_NAME) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo = db();

    $sql = file_get_contents(__DIR__ . '/database/library_laptop.sql');
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = str_replace('__ADMIN_HASH__', $pdo->quote($hash), $sql);

    // Execute statements one by one so setup works reliably with PDO.
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql);
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $message = 'Setup completed successfully. You can now log in with admin / admin123.';
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html><html><head><meta charset="utf-8"><title>System Setup</title>
<link rel="stylesheet" href="assets/style.css"></head>
<body class="login-bg"><div class="login-card">
<h1>Library Laptop Management System</h1>
<?php if ($message): ?><div class="alert success"><?=htmlspecialchars($message)?></div><a class="btn primary" href="login.php">Go to Login</a><?php endif; ?>
<?php if ($error): ?><div class="alert error"><?=htmlspecialchars($error)?></div><p>Check that the fresh XAMPP MySQL is running on port 3307.</p><?php endif; ?>
</div></body></html>
