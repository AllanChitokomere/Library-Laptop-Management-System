<?php
require_once 'config.php';

$pdo = db();

echo "<h2>Database Server Test</h2>";

echo "Host: " . DB_HOST . "<br>";
echo "Port: " . DB_PORT . "<br>";
echo "Database: " . DB_NAME . "<br><br>";

echo "<b>MySQL server:</b> ";
echo $pdo->query("SELECT @@port")->fetchColumn();
echo "<br>";

echo "<b>Server ID:</b> ";
echo $pdo->query("SELECT @@server_id")->fetchColumn();
echo "<br><br>";

echo "<h3>retrievals columns:</h3>";

$stmt = $pdo->query("SHOW COLUMNS FROM retrievals");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " — " . $row['Type'] . "<br>";
}

echo "<br><h3>Database location:</h3>";

echo $pdo->query("SELECT DATABASE()")->fetchColumn();
?>