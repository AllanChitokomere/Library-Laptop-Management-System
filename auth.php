<?php
session_start();

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (($_SESSION['role'] ?? '') !== 'ADMIN') {
        http_response_code(403);
        exit('Administrator access required.');
    }
}
