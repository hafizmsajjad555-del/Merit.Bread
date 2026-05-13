<?php
session_start();

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "distribution_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Connection failed!");

// Session timeout — 8 hours
if (isset($_SESSION['last_activity']) && 
    (time() - $_SESSION['last_activity'] > 28800)) {
    session_destroy();
    header("Location: login.php");
    exit();
}
$_SESSION['last_activity'] = time();
?>