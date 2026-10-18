<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

$pdo = db();

$student_id = (int)($_GET['student_id'] ?? 0);

if ($student_id <= 0) {
    die('Student not found.');
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    die('Student not found.');
}

$stmt = $pdo->prepare("
    SELECT
        r.id,
        r.retrieved_at,
        r.returned_at,
        r.verified_by
    FROM retrievals r
    WHERE r.student_id = ?
    ORDER BY r.retrieved_at DESC
");
$stmt->execute([$student_id]);
$history = $stmt->fetchAll();
?>

<!doctype html>

<html>
<head>
    <meta charset="utf-8">
    <title>Student History</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<?php include 'partials/nav.php'; ?>

<main class="container">

<h1>Student History</h1>

<div class="card">
    <h2><?= htmlspecialchars($student['full_name']) ?></h2>

    <p>
        <strong>Library Number:</strong>
        <?= htmlspecialchars($student['library_number']) ?>
    </p>

    <p>
        <strong>Class:</strong>
        <?= htmlspecialchars($student['class_name']) ?>
    </p>

    <p>
        <strong>Laptop:</strong>
        <?= htmlspecialchars($student['laptop_model']) ?>
    </p>

    <p>
        <strong>Serial / IP:</strong>
        <?= htmlspecialchars($student['serial_or_ip']) ?>
    </p>
</div>

<div class="card">

    <h2>Laptop History</h2>

    <?php if (!$history): ?>

        <p class="muted">
            No laptop history has been recorded for this student yet.
        </p>

    <?php else: ?>

        <div class="table-wrap">

            <table>

                <tr>
                    <th>Retrieved Date & Time</th>
                    <th>Submitted Date & Time</th>
                    <th>Status</th>
                </tr>

                <?php foreach ($history as $row): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($row['retrieved_at']) ?>
                        </td>

                        <td>
                            <?php if ($row['returned_at']): ?>
                                <?= htmlspecialchars($row['returned_at']) ?>
                            <?php else: ?>
                                Not submitted yet
                            <?php endif; ?>
                        </td>

                        <td>
                            <?php if ($row['returned_at']): ?>
                                Returned
                            <?php else: ?>
                                Outstanding
                            <?php endif; ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </table>

        </div>

    <?php endif; ?>

    <br>

    <a href="students.php" class="btn">
        ← Back to Students
    </a>

</div>


</main>

</body>
</html>
