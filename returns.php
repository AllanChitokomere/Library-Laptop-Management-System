<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

$pdo = db();

$msg = '';
$err = '';

/* =========================
   LIVE STUDENT SEARCH
   ========================= */
if (isset($_GET['live_search'])) {

    $search = trim($_GET['live_search']);

    if ($search === '') {
        exit;
    }

    $like = '%' . $search . '%';

    /*
     * Get only the latest outstanding
     * retrieval for each student.
     */
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            r.id AS retrieval_id,
            r.retrieved_at
        FROM students s
        INNER JOIN retrievals r
            ON r.id = (
                SELECT r2.id
                FROM retrievals r2
                WHERE r2.student_id = s.id
                  AND r2.returned_at IS NULL
                ORDER BY r2.retrieved_at DESC, r2.id DESC
                LIMIT 1
            )
        WHERE
               CAST(s.library_number AS CHAR) LIKE ?
            OR s.full_name LIKE ?
            OR s.class_name LIKE ?
            OR s.laptop_model LIKE ?
            OR s.colour LIKE ?
            OR s.serial_or_ip LIKE ?
        ORDER BY s.library_number ASC
    ");

    $stmt->execute([
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    ]);

    $students = $stmt->fetchAll();

    if (!$students) {

        echo '<div class="card">';
        echo '<p class="muted">No outstanding students found.</p>';
        echo '</div>';

        exit;
    }

    echo '<div class="card">';
    echo '<h2>Outstanding Laptops</h2>';

    echo '<div class="table-wrap">';
    echo '<table>';

    echo '<tr>';
    echo '<th>Library No.</th>';
    echo '<th>Name</th>';
    echo '<th>Class</th>';
    echo '<th>Laptop</th>';
    echo '<th>Colour</th>';
    echo '<th>Serial/IP</th>';
    echo '<th>Retrieved</th>';
    echo '<th>Action</th>';
    echo '</tr>';

    foreach ($students as $student) {

        $id = (int)$student['retrieval_id'];

        $name = htmlspecialchars(
            $student['full_name'],
            ENT_QUOTES,
            'UTF-8'
        );

        echo '<tr>';

        echo '<td>' .
            htmlspecialchars($student['library_number']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['full_name']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['class_name']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['laptop_model']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['colour']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['serial_or_ip']) .
            '</td>';

        echo '<td>' .
            htmlspecialchars($student['retrieved_at']) .
            '</td>';

        echo '<td>';

        echo '<div class="submit-action">';

        echo '<button type="button" ';
        echo 'class="btn small primary" ';
        echo 'onclick="showSubmitConfirm(this, ';
        echo $id;
        echo ', \'' . $name . '\')">';
        echo 'Submit';
        echo '</button>';

        echo '<div class="local-confirm" style="display:none;">';

        echo '<span class="confirm-text">';
        echo 'Confirm submission?';
        echo '</span>';

        echo '<button type="button" ';
        echo 'class="btn small primary" ';
        echo 'onclick="confirmSubmit(this)">';
        echo 'Yes';
        echo '</button>';

        echo '<button type="button" ';
        echo 'class="btn small cancel-btn" ';
        echo 'onclick="cancelSubmit(this)">';
        echo 'Cancel';
        echo '</button>';

        echo '</div>';

        echo '</div>';

        echo '</td>';

        echo '</tr>';
    }

    echo '</table>';
    echo '</div>';
    echo '</div>';

    exit;
}


/* =========================
   CONFIRM SUBMISSION
   ========================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'confirm_submit'
) {

    try {

        $retrieval_id =
            (int)($_POST['retrieval_id'] ?? 0);

        if ($retrieval_id <= 0) {
            throw new Exception('Invalid retrieval.');
        }

        /* Find the outstanding retrieval */
        $stmt = $pdo->prepare("
            SELECT
                r.*,
                s.full_name
            FROM retrievals r
            INNER JOIN students s
                ON s.id = r.student_id
            WHERE r.id = ?
              AND r.returned_at IS NULL
        ");

        $stmt->execute([$retrieval_id]);

        $retrieval = $stmt->fetch();

        if (!$retrieval) {

            throw new Exception(
                'This laptop has already been submitted.'
            );
        }

        /*
         * Save the real submission date and time.
         */
        $stmt = $pdo->prepare("
            UPDATE retrievals
            SET returned_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$retrieval_id]);

        $msg =
            'Laptop submitted successfully by ' .
            $retrieval['full_name'] .
            '.';

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}

?>

<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>Submit Laptop</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>

        .submit-search {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border: 1px solid #b8cbe0;
            border-radius: 7px;
        }

        #searchResults {
            margin-top: 15px;
        }

        .submit-action {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .local-confirm {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 7px;
            background: #eef5ff;
            border: 1px solid #b8cbe0;
            border-radius: 7px;
            white-space: nowrap;
        }

        .confirm-text {
            font-size: 13px;
            font-weight: 600;
            color: #18324a;
            margin-right: 3px;
        }

        .cancel-btn {
            background: #e5edf6;
            color: #18324a;
        }

    </style>

</head>

<body>

<?php include 'partials/nav.php'; ?>

<main class="container">

    <h1>Submit Laptop</h1>


    <?php if ($msg): ?>

        <div class="alert success">
            <?= htmlspecialchars($msg) ?>
        </div>

    <?php endif; ?>


    <?php if ($err): ?>

        <div class="alert error">
            <?= htmlspecialchars($err) ?>
        </div>

    <?php endif; ?>


    <div class="card">

        <h2>Search Student</h2>

        <input
            type="text"
            id="studentSearch"
            class="submit-search"
            placeholder="Search by an Field."
            autocomplete="off"
        >

        <div id="searchResults"></div>

    </div>


    <!-- Hidden submit form -->

    <form
        method="post"
        id="submitForm"
        style="display:none;"
    >

        <input
            type="hidden"
            name="action"
            value="confirm_submit"
        >

        <input
            type="hidden"
            name="retrieval_id"
            id="retrievalId"
        >

    </form>

</main>


<script>

/* =========================
   LIVE SEARCH
   ========================= */

const searchBox =
    document.getElementById('studentSearch');

const results =
    document.getElementById('searchResults');

let searchTimer = null;


searchBox.addEventListener('input', function () {

    clearTimeout(searchTimer);

    const value = this.value.trim();

    if (value === '') {

        results.innerHTML = '';

        return;
    }

    searchTimer = setTimeout(function () {

        fetch(
            'returns.php?live_search=' +
            encodeURIComponent(value)
        )

        .then(function(response) {

            return response.text();

        })

        .then(function(html) {

            results.innerHTML = html;

        })

        .catch(function() {

            results.innerHTML =
                '<div class="alert error">' +
                'Could not search students.' +
                '</div>';

        });

    }, 200);

});


/* =========================
   SHOW CONFIRMATION
   ========================= */

function showSubmitConfirm(
    button,
    retrievalId,
    studentName
) {

    /*
     * Close other confirmation boxes.
     */
    document
        .querySelectorAll('.local-confirm')
        .forEach(function(box) {

            box.style.display = 'none';

        });


    /*
     * Get the confirmation box
     * belonging to this button.
     */
    const actionBox =
        button.parentElement
              .querySelector('.local-confirm');


    actionBox.dataset.retrievalId =
        retrievalId;

    actionBox.dataset.studentName =
        studentName;


    actionBox.querySelector('.confirm-text').textContent =
        'Submit for ' + studentName + '?';


    actionBox.style.display = 'flex';

}


/* =========================
   CONFIRM SUBMIT
   ========================= */

function confirmSubmit(button) {

    const actionBox =
        button.closest('.local-confirm');

    const retrievalId =
        actionBox.dataset.retrievalId;


    document.getElementById('retrievalId').value =
        retrievalId;


    document.getElementById('submitForm').submit();

}


/* =========================
   CANCEL
   ========================= */

function cancelSubmit(button) {

    const actionBox =
        button.closest('.local-confirm');

    actionBox.style.display = 'none';

}

</script>

</body>

</html>