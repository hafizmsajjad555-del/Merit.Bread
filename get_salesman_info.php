<?php
include 'config.php';
$id   = intval($_GET['id'] ?? 0);
$city = mysqli_real_escape_string($conn, $_GET['city'] ?? '');

$row = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM salesmen WHERE id='$id' AND city='$city' AND is_active=1"));

echo json_encode($row ?: ['error' => 'not found']);
?>