<?php
require_once 'auth.php'; require_login(); require_once 'config.php';
$pdo=db();
$students=(int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$laptops=(int)$pdo->query("SELECT COUNT(*) FROM laptops")->fetchColumn();
$available=(int)$pdo->query("SELECT COUNT(*) FROM laptops WHERE status='AVAILABLE'")->fetchColumn();
$outstanding=(int)$pdo->query("SELECT COUNT(*) FROM retrievals WHERE returned_at IS NULL")->fetchColumn();
?><!doctype html><html><head><meta charset="utf-8"><title>Dashboard</title><link rel="stylesheet" href="assets/style.css"></head>
<body><?php include 'partials/nav.php'; ?><main class="container"><h1>Dashboard</h1>
<div class="cards">
<div class="card"><span>Students</span><b><?=$students?></b></div>
<div class="card"><span>Laptops</span><b><?=$laptops?></b></div>
<div class="card"><span>Available</span><b><?=$available?></b></div>
<div class="card"><span>Outstanding</span><b><?=$outstanding?></b></div>
</div><div class="card"><h2>Welcome</h2><p>Use the menu to manage students, laptops, retrievals, returns, history and reports.</p></div>
</main></body></html>
