
<?php
require_once 'auth.php';
require_login();
require_once 'config.php';

if (($_SESSION['role'] ?? '') !== 'ADMIN') {
    die('Access denied. Administrator access is required.');
}

$pdo = db();

$msg = '';
$err = '';

/* =========================
   DELETE USER
   ========================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete'
) {

    try {

        $id = (int)($_POST['id'] ?? 0);

        if ($id <= 0) {
            throw new Exception('Invalid user.');
        }

        /* Prevent deleting yourself */
        if ($id === (int)$_SESSION['user_id']) {
            throw new Exception(
                'You cannot delete the account you are currently using.'
            );
        }

        $stmt = $pdo->prepare("
            DELETE FROM users
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $msg = 'User deleted successfully.';

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}


/* =========================
   SAVE USER
   ========================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'save'
) {

    try {

        $id =
            (int)($_POST['id'] ?? 0);

        $username =
            trim($_POST['username'] ?? '');

        $password =
            $_POST['password'] ?? '';

        $role =
            strtoupper(
                trim($_POST['role'] ?? 'GENERAL')
            );


        if ($username === '') {
            throw new Exception('Username is required.');
        }

        if (!in_array($role, ['ADMIN', 'GENERAL'], true)) {
            throw new Exception('Invalid user role.');
        }


        /* =========================
           EDIT EXISTING USER
           ========================= */
        if ($id > 0) {

            /*
             * Check if username belongs
             * to another user.
             */
            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                  AND id <> ?
                LIMIT 1
            ");

            $stmt->execute([
                $username,
                $id
            ]);

            if ($stmt->fetch()) {
                throw new Exception(
                    'That username is already in use.'
                );
            }


            if ($password !== '') {

                $hash =
                    password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET username = ?,
                        password_hash = ?,
                        role = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $username,
                    $hash,
                    $role,
                    $id
                ]);

            } else {

                $stmt = $pdo->prepare("
                    UPDATE users
                    SET username = ?,
                        role = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $username,
                    $role,
                    $id
                ]);
            }

            $msg = 'User updated successfully.';

        } else {

            /* =========================
               CREATE NEW USER
               ========================= */

            if ($password === '') {
                throw new Exception(
                    'Password is required for a new user.'
                );
            }


            /* Check duplicate username */
            $stmt = $pdo->prepare("
                SELECT id
                FROM users
                WHERE username = ?
                LIMIT 1
            ");

            $stmt->execute([$username]);

            if ($stmt->fetch()) {
                throw new Exception(
                    'That username already exists.'
                );
            }


            $hash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $stmt = $pdo->prepare("
                INSERT INTO users
                (username, password_hash, role)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $username,
                $hash,
                $role
            ]);

            $msg = 'User registered successfully.';
        }

    } catch (Throwable $e) {

        $err = $e->getMessage();
    }
}


/* =========================
   EDIT USER DATA
   ========================= */
$edit = null;

if (isset($_GET['edit'])) {

    $editId =
        (int)$_GET['edit'];

    if ($editId > 0) {

        $stmt = $pdo->prepare("
            SELECT id, username, role
            FROM users
            WHERE id = ?
        ");

        $stmt->execute([$editId]);

        $edit = $stmt->fetch();
    }
}


/* =========================
   GET ALL USERS
   ========================= */
$stmt = $pdo->query("
    SELECT
        id,
        username,
        role
    FROM users
    ORDER BY username ASC
");

$users = $stmt->fetchAll();

?>

<!doctype html>
<html>

<head>

    <meta charset="utf-8">

    <title>User Management</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>

<?php include 'partials/nav.php'; ?>

<main class="container">

    <h1>User Management</h1>


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
         USER FORM
         ========================= -->

    <div class="card">

        <h2>
            <?= $edit
                ? 'Edit User'
                : 'Register New User' ?>
        </h2>


        <form method="post">

            <input
                type="hidden"
                name="action"
                value="save"
            >

            <input
                type="hidden"
                name="id"
                value="<?= $edit
                    ? (int)$edit['id']
                    : 0 ?>"
            >


            <div class="grid">

                <label>

                    Username

                    <input
                        type="text"
                        name="username"
                        required
                        value="<?= $edit
                            ? htmlspecialchars(
                                $edit['username']
                            )
                            : '' ?>"
                    >

                </label>


                <label>

                    Password

                    <input
                        type="password"
                        name="password"
                        <?= $edit
                            ? ''
                            : 'required' ?>
                        placeholder="<?= $edit
                            ? 'Leave blank to keep current password'
                            : '' ?>"
                    >

                </label>


                <label>

                    Role

                    <select name="role">

                        <option
                            value="GENERAL"
                            <?= (
                                $edit &&
                                $edit['role'] === 'GENERAL'
                            )
                            ? 'selected'
                            : '' ?>
                        >
                            GENERAL
                        </option>

                        <option
                            value="ADMIN"
                            <?= (
                                $edit &&
                                $edit['role'] === 'ADMIN'
                            )
                            ? 'selected'
                            : '' ?>
                        >
                            ADMIN
                        </option>

                    </select>

                </label>

            </div>


            <br>


            <button
                type="submit"
                class="btn primary"
            >
                <?= $edit
                    ? 'Update User'
                    : 'Register User' ?>
            </button>


            <?php if ($edit): ?>

                <a
                    href="users.php"
                    class="btn"
                >
                    Cancel
                </a>

            <?php endif; ?>

        </form>

    </div>


    <!-- =========================
         USER LIST
         ========================= -->

    <div class="card">

        <h2>Registered Users</h2>


        <div class="table-wrap">

            <table>

                <thead>

                    <tr>

                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>

                    </tr>

                </thead>


                <tbody>

                <?php foreach ($users as $user): ?>

                    <tr>

                        <td>
                            <?= htmlspecialchars(
                                $user['username']
                            ) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars(
                                $user['role']
                            ) ?>
                        </td>

                        <td>

                            <div class="actions">

                                <a
                                    href="users.php?edit=<?= (int)$user['id'] ?>"
                                    class="btn small"
                                >
                                    Edit
                                </a>


                                <?php if (
                                    (int)$user['id']
                                    !==
                                    (int)$_SESSION['user_id']
                                ): ?>

                                    <form
                                        method="post"
                                        onsubmit="return confirm('Delete this user?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= (int)$user['id'] ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn small danger"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                <?php else: ?>

                                    <span class="muted">
                                        Current account
                                    </span>

                                <?php endif; ?>

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
