```php
<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

$pdo = db();

/*
 * Get only currently outstanding laptops.
 *
 * If the same student has multiple outstanding
 * retrieval records, only the latest record is shown.
 * Older records remain available in the database history.
 */
$stmt = $pdo->query("
    SELECT
        r.id,
        s.library_number,
        s.full_name,
        s.class_name,
        s.laptop_model,
        s.colour,
        s.serial_or_ip,
        r.retrieved_at
    FROM retrievals r
    INNER JOIN students s
        ON s.id = r.student_id
    WHERE r.returned_at IS NULL
      AND r.id = (
          SELECT MAX(r2.id)
          FROM retrievals r2
          WHERE r2.student_id = r.student_id
            AND r2.returned_at IS NULL
      )
    ORDER BY r.retrieved_at DESC
");

$outstanding = $stmt->fetchAll();

?>

<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>Outstanding Laptops</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .report-title {
            text-align: center;
            margin: 10px 0 20px;
        }

        .report-title h2 {
            margin-bottom: 5px;
            color: #0759a5;
        }

        .report-title p {
            margin: 0;
            color: #66798b;
        }

        .empty-report {
            text-align: center;
            padding: 30px;
        }

        .days-out {
            font-weight: bold;
            text-align: center;
            white-space: nowrap;
        }

        @media print {

            .top {
                display: none !important;
            }

            .container {
                margin-left: 0 !important;
                max-width: none !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .report-title h2 {
                color: #000 !important;
                font-size: 18px !important;
            }

            .report-title p {
                font-size: 12px !important;
            }

            body {
                background: #fff !important;
                color: #000 !important;
                font-size: 14px !important;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px !important;
            }

            th,
            td {
                border: 1px solid #000 !important;
                padding: 4px 5px !important;
                font-size: 14px !important;
                line-height: 1.00 !important;
            }

            th {
                background: #eee !important;
                color: #000 !important;
                font-weight: bold;
            }

            .days-out {
                font-weight: bold;
            }

        }

    </style>

</head>

<body>

<?php include 'partials/nav.php'; ?>

<main class="container">

    <div class="card">

        <div class="report-header no-print">

            <h1>Outstanding Laptops</h1>

            <button
                type="button"
                class="btn primary"
                onclick="window.print()"
            >
                🖨 Print Report
            </button>

        </div>

        <div class="report-title">

            <h2>Library Laptop Management System</h2>

            <p>
                Outstanding Laptop Report
            </p>

            <p>
                Generated:
                <?= date('Y-m-d H:i:s') ?>
            </p>

        </div>

        <?php if (!$outstanding): ?>

            <div class="empty-report">

                <h3>No Outstanding Laptops</h3>

                <p class="muted">
                    All issued laptops have been submitted.
                </p>

            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table>

                    <thead>

                        <tr>

                            <th>Library No.</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Laptop</th>
                            <th>Colour</th>
                            <th>Serial / IP</th>
                            <th>Retrieved Date & Time</th>
                            <th>Days Out</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($outstanding as $row): ?>

                        <?php

                        /*
                         * Calculate the number of calendar days
                         * the laptop has been outstanding.
                         */
                        $retrievedDate = new DateTime($row['retrieved_at']);
                        $today = new DateTime();

                        $retrievedDate->setTime(0, 0, 0);
                        $today->setTime(0, 0, 0);

                        $daysOut = $retrievedDate->diff($today)->days;

                        ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $row['library_number']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['full_name']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['class_name']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['laptop_model']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['colour']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['serial_or_ip']
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $row['retrieved_at']
                                ) ?>
                            </td>

                            <td class="days-out">
                                <?= $daysOut ?>
                                <?= $daysOut == 1 ? 'day' : 'days' ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <p class="muted no-print">

                Total outstanding laptops:
                <strong><?= count($outstanding) ?></strong>

            </p>

        <?php endif; ?>

    </div>

</main>

</body>

</html>
```