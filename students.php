<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

$pdo = db();

$msg = '';
$err = '';

/* =========================
   SAVE / DELETE
   ========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $action = $_POST['action'] ?? '';

        /* ADD OR EDIT STUDENT */
        if ($action === 'save') {

            $id = (int)($_POST['id'] ?? 0);

            $library = (int)($_POST['library_number'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $class_name = trim($_POST['class_name'] ?? '');
            $laptop_model = trim($_POST['laptop_model'] ?? '');
            $colour = trim($_POST['colour'] ?? '');
            $serial_or_ip = trim($_POST['serial_or_ip'] ?? '');

            if ($id) {

                $s = $pdo->prepare("
                    UPDATE students
                    SET
                        library_number = ?,
                        full_name = ?,
                        class_name = ?,
                        laptop_model = ?,
                        colour = ?,
                        serial_or_ip = ?
                    WHERE id = ?
                ");

                $s->execute([
                    $library,
                    $full_name,
                    $class_name,
                    $laptop_model,
                    $colour,
                    $serial_or_ip,
                    $id
                ]);

                $msg = 'Student updated successfully.';

            } else {

                $s = $pdo->prepare("
                    INSERT INTO students
                    (library_number, full_name, class_name, laptop_model, colour, serial_or_ip)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                $s->execute([
                    $library,
                    $full_name,
                    $class_name,
                    $laptop_model,
                    $colour,
                    $serial_or_ip
                ]);

                $msg = 'Student registered successfully.';
            }
        }

        /* DELETE STUDENT */
        elseif ($action === 'delete') {

            $id = (int)($_POST['id'] ?? 0);

            $s = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $s->execute([$id]);

            $msg = 'Student and all information connected to the student were deleted successfully.';
        }

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}


/* =========================
   EDIT STUDENT
   ========================= */

$edit = null;

if (isset($_GET['edit'])) {

    $s = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $s->execute([(int)$_GET['edit']]);

    $edit = $s->fetch();

}


/* =========================
   SEARCH
   ========================= */

$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $like = '%' . $search . '%';

    $s = $pdo->prepare("
        SELECT s.*
        FROM students s
        WHERE
            CAST(s.library_number AS CHAR) LIKE ?
            OR s.full_name LIKE ?
            OR s.class_name LIKE ?
            OR s.laptop_model LIKE ?
            OR s.colour LIKE ?
            OR s.serial_or_ip LIKE ?
        ORDER BY s.library_number
    ");

    $s->execute([
        $like,
        $like,
        $like,
        $like,
        $like,
        $like
    ]);

    $rows = $s->fetchAll();

} else {

    $rows = $pdo->query("
        SELECT *
        FROM students
        ORDER BY library_number
    ")->fetchAll();
}


/* =========================
   NEXT LIBRARY NUMBER
   ========================= */

$next = (int)$pdo->query("
    SELECT COALESCE(MAX(library_number), 1) + 1
    FROM students
")->fetchColumn();

?>
<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <title>Register Student - Library Laptop Management System</title>

    <link rel="stylesheet" href="assets/style.css">

</head>

<body>

<?php include 'partials/nav.php'; ?>

<main class="container">

    <h1>Register Student</h1>

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


    <!-- =========================
         REGISTER / EDIT FORM
         ========================= -->

    <div class="card">

        <h2>
            <?= $edit ? 'Edit Student' : 'Register New Student' ?>
        </h2>

        <form method="post" class="grid">

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="id"
                value="<?= htmlspecialchars($edit['id'] ?? 0) ?>"
            >


         <label>
    Library Number

    <input
        type="number"
        name="library_number"
        value="<?= htmlspecialchars($edit['library_number'] ?? $next) ?>"
        <?= $edit ? '' : 'readonly' ?>
    >

</label>


            <label>
                Full Name

                <input
                    type="text"
                    name="full_name"
                    required
                    value="<?= htmlspecialchars($edit['full_name'] ?? '') ?>"
                >

            </label>


            <label>
                Class

                <input
                    type="text"
                    name="class_name"
                    required
                    value="<?= htmlspecialchars($edit['class_name'] ?? '') ?>"
                >

            </label>


            <label>
                Laptop Model

                <input
                    type="text"
                    name="laptop_model"
                    required
                    value="<?= htmlspecialchars($edit['laptop_model'] ?? '') ?>"
                >

            </label>


            <label>
                Colour

                <input
                    type="text"
                    name="colour"
                    required
                    value="<?= htmlspecialchars($edit['colour'] ?? '') ?>"
                >

            </label>


            <label>
                Serial / IP

                <input
                    type="text"
                    name="serial_or_ip"
                    required
                    value="<?= htmlspecialchars($edit['serial_or_ip'] ?? '') ?>"
                >

            </label>


            <div>

                <button
                    type="submit"
                    class="btn primary"
                >
                    <?= $edit ? 'Update Student' : 'Register Student' ?>
                </button>


                <?php if ($edit): ?>

                    <a
                        class="btn"
                        href="students.php"
                    >
                        Cancel
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- =========================
         SEARCH
         ========================= -->

    <div class="card">

        <h2>Registered Students</h2>

        <form method="get">

            <div style="display:flex;gap:10px;flex-wrap:wrap;">

                <input
                    type="text"
                    name="search"
                    placeholder="Search by library number, name, class, laptop, colour or serial/IP"
                    value="<?= htmlspecialchars($search) ?>"
                    style="flex:1;min-width:250px;padding:11px;border:1px solid #b8cbe0;border-radius:7px;"
                >

                <button
                    type="submit"
                    class="btn primary"
                >
                    Search
                </button>

                <?php if ($search !== ''): ?>

                    <a
                        href="students.php"
                        class="btn"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>


    <!-- =========================
         STUDENT LIST
         ========================= -->

    <div class="card">

        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>Library No.</th>

                        <th>Name</th>

                        <th>Class</th>

                        <th>Laptop</th>

                        <th>Colour</th>

                        <th>Serial/IP</th>

                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php if (!$rows): ?>

                    <tr>

                        <td
                            colspan="7"
                            style="text-align:center;"
                        >
                            No students found.
                        </td>

                    </tr>

                <?php endif; ?>


                <?php foreach ($rows as $r): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars($r['library_number']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['full_name']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['class_name']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['laptop_model']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['colour']) ?>
                        </td>


                        <td>
                            <?= htmlspecialchars($r['serial_or_ip']) ?>
                        </td>


                        <td>

                            <div class="actions">

                                <!-- EDIT -->

                                <a
                                    class="btn small"
                                    href="students.php?edit=<?= $r['id'] ?>"
                                >
                                    Edit
                                </a>


                                <!-- HISTORY -->

                                <a
                                    class="btn small"
                                    href="history.php?student_id=<?= $r['id'] ?>"
                                >
                                    History
                                </a>


                                <!-- DELETE -->

                                <form
                                    method="post"
                                    onsubmit="return confirm('Delete this student completely? All information and laptop history connected to this student will also be deleted.');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= $r['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn small danger"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</main>

</body>

</html>