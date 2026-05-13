<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$id   = intval($_GET['id']);
$type = $_GET['type'] ?? '';

if ($type == 'distribution') {
    mysqli_query($conn, "DELETE FROM distribution_sheet WHERE id='$id'");
}

header("Location: admin_dashboard.php");
exit();
?>