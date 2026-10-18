<header class="top">

    <div class="brand">
        Library Laptop Management System
    </div>

    <nav>

        <a href="dashboard.php">📊 Dashboard</a>

        <a href="students.php">👨‍🎓 Students</a>

        <a href="transactions.php">💻 Retrieve Laptop</a>

        <a href="returns.php">↩️ Submit Laptop</a>

        <a href="reports.php">⚠️ Outstanding Laptops</a>

        <?php if (($_SESSION['role'] ?? '') === 'ADMIN'): ?>

            <a href="users.php">👤 User Profile</a>

            <a href="backup.php">💾 Backup & Restore</a>

        <?php endif; ?>

        <a href="logout.php">🚪 Logout</a>

    </nav>

</header>