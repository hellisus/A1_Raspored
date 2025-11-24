<?php
// AJAX config fajl - bez session inicijalizacije
if (session_status() == PHP_SESSION_NONE) {
    // Samo ako session nije već pokrenut
    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params(28800);
    date_default_timezone_set('Europe/Belgrade');
    session_start();
}

// Database connection
$host = 'localhost';
$dbname = 'opkdoors_gp_raz_ps';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
