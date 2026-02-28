<?php
// includes/db.php
$host = 'localhost';
$dbname = 'umuhuza_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<div style="padding:20px;background:#fee;border:1px solid #c00;font-family:sans-serif;">
    <strong>Database Connection Failed:</strong> ' . htmlspecialchars($e->getMessage()) . '
    <br><br>Please ensure MySQL is running and import <code>database.sql</code> first.
    </div>');
}
?>
