
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

    $stmt = $pdo->prepare("
        SELECT *
        FROM students
        WHERE CAST(library_number AS CHAR) LIKE ?
           OR full_name LIKE ?
           OR class_name LIKE ?
           OR laptop_model LIKE ?
           OR colour LIKE ?
           OR serial_or_ip LIKE ?
        ORDER BY library_number ASC
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
        echo '<p class="muted">No matching students found.</p>';
        echo '</div>';
        exit;
    }

    echo '<div class="card">';
    echo '<h2>Matching Students</h2>';
    echo '<div class="table-wrap">';
    echo '<table>';

    echo '<tr>';
    echo '<th>Library No.</th>';
    echo '<th>Name</th>';
    echo '<th>Class</th>';
    echo '<th>Laptop</th>';
    echo '<th>Colour</th>';
    echo '<th>Serial/IP</th>';
    echo '<th>Action</th>';
    echo '</tr>';

    foreach ($students as $student) {

        $id = (int)$student['id'];

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

        echo '<td>';

        /*
         * Retrieve button.
         * Clicking it shows the confirmation directly
         * beside the button.
         */

        echo '<div class="retrieve-action">';

        echo '<button type="button" ';
        echo 'class="btn small primary" ';
        echo 'onclick="showRetrieveConfirm(this, ';
        echo $id;
        echo ', \'' . $name . '\')">';
        echo 'Retrieve';
        echo '</button>';

        echo '<div class="local-confirm" style="display:none;">';

        echo '<span class="confirm-text">';
        echo 'Confirm retrieve?';
        echo '</span>';

        echo '<button type="button" ';
        echo 'class="btn small primary confirm-yes" ';
        echo 'onclick="confirmRetrieve(this)">';
        echo 'Yes';
        echo '</button>';

        echo '<button type="button" ';
        echo 'class="btn small cancel-btn" ';
        echo 'onclick="cancelRetrieve(this)">';
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
   CONFIRM RETRIEVAL
   ========================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'confirm_retrieve'
) {

    try {

        $student_id = (int)($_POST['student_id'] ?? 0);

        if ($student_id <= 0) {
            throw new Exception('Invalid student.');
        }

        /* Find student */
        $stmt = $pdo->prepare("
            SELECT *
            FROM students
            WHERE id = ?
        ");

        $stmt->execute([$student_id]);

        $student = $stmt->fetch();

        if (!$student) {
            throw new Exception('Student not found.');
        }

        /* Check if student already has an outstanding laptop */
        $stmt = $pdo->prepare("
            SELECT id
            FROM retrievals
            WHERE student_id = ?
              AND returned_at IS NULL
            LIMIT 1
        ");

        $stmt->execute([$student_id]);

        if ($stmt->fetch()) {

            throw new Exception(
                'This student already has an outstanding laptop.'
            );
        }

        /* Save retrieval */
        $stmt = $pdo->prepare("
            INSERT INTO retrievals
            (student_id, retrieved_at, verified_by)
            VALUES (?, NOW(), ?)
        ");

        $stmt->execute([
            $student_id,
            $_SESSION['user_id']
        ]);

        $msg =
            'Laptop retrieved successfully for ' .
            $student['full_name'] .
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

    <title>Retrieve Laptop</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>

        .retrieve-search {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            border: 1px solid #b8cbe0;
            border-radius: 7px;
        }

        #searchResults {
            margin-top: 15px;
        }

        .retrieve-action {
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

        .confirm-yes {
            background: #0759a5;
            color: white;
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

    <h1>Retrieve Laptop</h1>


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
            class="retrieve-search"
            placeholder="Search by name, library number, class, laptop, colour or serial/IP..."
            autocomplete="off"
        >

        <div id="searchResults"></div>

    </div>


    <!-- Hidden retrieval form -->

    <form
        method="post"
        id="retrieveForm"
        style="display:none;"
    >

        <input
            type="hidden"
            name="action"
            value="confirm_retrieve"
        >

        <input
            type="hidden"
            name="student_id"
            id="studentId"
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
            'transactions.php?live_search=' +
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
   SHOW LOCAL CONFIRMATION
   ========================= */

function showRetrieveConfirm(
    button,
    studentId,
    studentName
) {

    /*
     * Hide any other confirmation boxes
     * currently open.
     */

    document
        .querySelectorAll('.local-confirm')
        .forEach(function(box) {

            box.style.display = 'none';

        });


    /*
     * Find the confirmation box belonging
     * to the button that was clicked.
     */

    const actionBox =
        button.parentElement
              .querySelector('.local-confirm');


    actionBox.dataset.studentId =
        studentId;

    actionBox.dataset.studentName =
        studentName;


    actionBox.querySelector('.confirm-text').textContent =
        'Retrieve for ' + studentName + '?';


    actionBox.style.display = 'flex';

}


/* =========================
   CONFIRM RETRIEVE
   ========================= */

function confirmRetrieve(button) {

    const actionBox =
        button.closest('.local-confirm');

    const studentId =
        actionBox.dataset.studentId;


    document.getElementById('studentId').value =
        studentId;


    document.getElementById('retrieveForm').submit();

}


/* =========================
   CANCEL
   ========================= */

function cancelRetrieve(button) {

    const actionBox =
        button.closest('.local-confirm');

    actionBox.style.display = 'none';

}

</script>

</body>

</html>
```

