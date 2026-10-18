
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

$backupDir =
    __DIR__ .
    DIRECTORY_SEPARATOR .
    'backups';


/* CREATE BACKUP FOLDER */
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}


/* DOWNLOAD BACKUP */
if (isset($_GET['download'])) {

    $file = basename($_GET['download']);

    $path =
        $backupDir .
        DIRECTORY_SEPARATOR .
        $file;

    if (
        !file_exists($path) ||
        !is_file($path)
    ) {
        die('Backup file not found.');
    }

    header('Content-Type: application/sql');

    header(
        'Content-Disposition: attachment; filename="' .
        $file .
        '"'
    );

    header(
        'Content-Length: ' .
        filesize($path)
    );

    readfile($path);

    exit;
}


/* DELETE BACKUP */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'delete_backup'
) {

    try {

        $file =
            basename(
                $_POST['filename'] ?? ''
            );

        if ($file === '') {
            throw new Exception(
                'Invalid backup file.'
            );
        }

        /*
         * Only allow SQL backup files
         * to be deleted.
         */
        if (
            strtolower(
                pathinfo(
                    $file,
                    PATHINFO_EXTENSION
                )
            ) !== 'sql'
        ) {
            throw new Exception(
                'Invalid backup file.'
            );
        }

        $path =
            $backupDir .
            DIRECTORY_SEPARATOR .
            $file;

        if (
            !file_exists($path) ||
            !is_file($path)
        ) {
            throw new Exception(
                'Backup file not found.'
            );
        }

        if (!unlink($path)) {
            throw new Exception(
                'Could not delete the backup file.'
            );
        }

        $msg =
            'Backup deleted successfully: ' .
            $file;

    } catch (Throwable $e) {

        $err =
            'Delete failed: ' .
            $e->getMessage();
    }
}


/* CREATE BACKUP */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'backup'
) {

    try {

        $timestamp =
            date('Y-m-d_H-i-s');

        $filename =
            'library_laptop_backup_' .
            $timestamp .
            '.sql';

        $path =
            $backupDir .
            DIRECTORY_SEPARATOR .
            $filename;

        $database = DB_NAME;

        $sql = '';

        $sql .=
            "-- Library Laptop Management System Backup\n";

        $sql .=
            "-- Created: " .
            date('Y-m-d H:i:s') .
            "\n\n";

        $sql .=
            "CREATE DATABASE IF NOT EXISTS `" .
            $database .
            "`;\n";

        $sql .=
            "USE `" .
            $database .
            "`;\n\n";


        /* GET TABLES */

        $tables =
            $pdo->query(
                "SHOW TABLES"
            )->fetchAll(
                PDO::FETCH_COLUMN
            );


        foreach ($tables as $table) {

            $safeTable =
                str_replace(
                    '`',
                    '``',
                    $table
                );


            /* TABLE STRUCTURE */

            $createStmt =
                $pdo->query(
                    "SHOW CREATE TABLE `" .
                    $safeTable .
                    "`"
                );

            $createRow =
                $createStmt->fetch(
                    PDO::FETCH_ASSOC
                );

            $createSql = '';

            if ($createRow) {

                $values =
                    array_values(
                        $createRow
                    );

                if (isset($values[1])) {

                    $createSql =
                        $values[1];
                }
            }


            $sql .=
                "DROP TABLE IF EXISTS `" .
                $safeTable .
                "`;\n";


            if ($createSql !== '') {

                $sql .=
                    $createSql .
                    ";\n\n";
            }


            /* TABLE DATA */

            $rows =
                $pdo->query(
                    "SELECT * FROM `" .
                    $safeTable .
                    "`"
                )->fetchAll(
                    PDO::FETCH_ASSOC
                );


            foreach ($rows as $row) {

                $columns = [];
                $values = [];


                foreach ($row as $column => $value) {

                    $columns[] =
                        '`' .
                        str_replace(
                            '`',
                            '``',
                            $column
                        ) .
                        '`';


                    if ($value === null) {

                        $values[] = 'NULL';

                    } else {

                        $values[] =
                            $pdo->quote(
                                (string)$value
                            );
                    }
                }


                $sql .=
                    "INSERT INTO `" .
                    $safeTable .
                    "` (" .
                    implode(
                        ', ',
                        $columns
                    ) .
                    ") VALUES (" .
                    implode(
                        ', ',
                        $values
                    ) .
                    ");\n";
            }


            $sql .= "\n";
        }


        /* SAVE BACKUP */

        if (
            file_put_contents(
                $path,
                $sql
            ) === false
        ) {

            throw new Exception(
                'Could not create backup file.'
            );
        }


        $msg =
            'Backup created successfully: ' .
            $filename;

    } catch (Throwable $e) {

        $err =
            'Backup failed: ' .
            $e->getMessage();
    }
}


/* RESTORE BACKUP */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'restore'
) {

    try {

        if (
            !isset($_FILES['backup_file'])
            ||
            $_FILES['backup_file']['error']
                !== UPLOAD_ERR_OK
        ) {

            throw new Exception(
                'Please select a backup file.'
            );
        }


        $uploaded =
            $_FILES['backup_file'];


        $extension =
            strtolower(
                pathinfo(
                    $uploaded['name'],
                    PATHINFO_EXTENSION
                )
            );


        if ($extension !== 'sql') {

            throw new Exception(
                'Only .sql backup files are allowed.'
            );
        }


        $sql =
            file_get_contents(
                $uploaded['tmp_name']
            );


        if (
            $sql === false ||
            trim($sql) === ''
        ) {

            throw new Exception(
                'The backup file is empty.'
            );
        }


        /* Remove comment lines */

        $sql =
            preg_replace(
                '/^\s*(--|#).*$/m',
                '',
                $sql
            );


        /* Split SQL statements */

        $statements =
            preg_split(
                '/;\s*(?:\r\n|\r|\n|$)/',
                $sql
            );


        $pdo->exec(
            "SET FOREIGN_KEY_CHECKS=0"
        );


        foreach ($statements as $statement) {

            $statement =
                trim($statement);

            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }


        $pdo->exec(
            "SET FOREIGN_KEY_CHECKS=1"
        );


        $msg =
            'Database restored successfully.';

    } catch (Throwable $e) {

        try {

            $pdo->exec(
                "SET FOREIGN_KEY_CHECKS=1"
            );

        } catch (Throwable $ignore) {
        }


        $err =
            'Restore failed: ' .
            $e->getMessage();
    }
}


/* GET AVAILABLE BACKUPS */

$backups = [];

$files =
    glob(
        $backupDir .
        DIRECTORY_SEPARATOR .
        '*.sql'
    );


if ($files) {

    foreach ($files as $file) {

        if (is_file($file)) {

            $backups[] = [

                'name' =>
                    basename($file),

                'size' =>
                    filesize($file),

                'time' =>
                    filemtime($file)

            ];
        }
    }
}


/*
 * NEWEST FIRST
 *
 * The most recently created/updated
 * backup appears at the top.
 */
usort(
    $backups,
    function ($a, $b) {

        return $b['time'] <=> $a['time'];

    }
);

?>

<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <title>Backup & Restore</title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

    <style>

        .backup-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .backup-table {
            margin-top: 15px;
        }

        .backup-note {
            background: #eef5ff;
            border-left: 4px solid #0759a5;
            padding: 12px;
            border-radius: 6px;
            margin-top: 15px;
        }

        .file-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #b8cbe0;
            border-radius: 7px;
            background: #fff;
        }

        .backup-actions-cell {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

    </style>

</head>

<body>

<?php include 'partials/nav.php'; ?>


<main class="container">

    <h1>Backup & Restore</h1>


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


    <!-- CREATE BACKUP -->

    <div class="card">

        <h2>Create Database Backup</h2>

        <p>
            Create a complete backup of the
            Library Laptop Management System database.
        </p>

        <form method="post">

            <input
                type="hidden"
                name="action"
                value="backup"
            >

            <button
                type="submit"
                class="btn primary"
            >
                💾 Create Backup
            </button>

        </form>

    </div>


    <!-- RESTORE -->

    <div class="card">

        <h2>Restore Database</h2>

        <p>
            Select a previously created
            <strong>.sql</strong> backup file.
        </p>

        <form
            method="post"
            enctype="multipart/form-data"
            onsubmit="return confirmRestore();"
        >

            <input
                type="hidden"
                name="action"
                value="restore"
            >

            <input
                type="file"
                name="backup_file"
                class="file-input"
                accept=".sql"
                required
            >

            <br><br>

            <button
                type="submit"
                class="btn primary"
            >
                Restore Backup
            </button>

        </form>


        <div class="backup-note">

            <strong>Important:</strong>

            Restoring a backup replaces the
            current database information with the
            information contained in the backup.

        </div>

    </div>


    <!-- AVAILABLE BACKUPS -->

    <div class="card">

        <h2>Available Backups</h2>


        <?php if (!$backups): ?>

            <p class="muted">
                No backups have been created yet.
            </p>

        <?php else: ?>

            <div class="table-wrap backup-table">

                <table>

                    <thead>

                        <tr>

                            <th>Backup File</th>

                            <th>Date Created</th>

                            <th>Size</th>

                            <th>Action</th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($backups as $backup): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $backup['name']
                                ) ?>
                            </td>


                            <td>
                                <?= date(
                                    'Y-m-d H:i:s',
                                    $backup['time']
                                ) ?>
                            </td>


                            <td>
                                <?= number_format(
                                    $backup['size'] / 1024,
                                    2
                                ) ?>
                                KB
                            </td>


                            <td>

                                <div class="backup-actions-cell">

                                    <!-- DOWNLOAD -->

                                    <a
                                        href="backup.php?download=<?= urlencode($backup['name']) ?>"
                                        class="btn small primary"
                                    >
                                        Download
                                    </a>


                                    <!-- DELETE -->

                                    <form
                                        method="post"
                                        onsubmit="return confirmDeleteBackup('<?= htmlspecialchars(
                                            $backup['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete_backup"
                                        >

                                        <input
                                            type="hidden"
                                            name="filename"
                                            value="<?= htmlspecialchars(
                                                $backup['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
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

        <?php endif; ?>

    </div>

</main>


<script>

function confirmRestore() {

    return confirm(
        'Are you sure you want to restore this backup? ' +
        'The current database information will be replaced.'
    );

}


function confirmDeleteBackup(filename) {

    return confirm(
        'Delete this backup?\n\n' +
        filename +
        '\n\nThis backup file will be permanently removed.'
    );

}

</script>

</body>

</html>
