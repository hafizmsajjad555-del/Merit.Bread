<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$filename = "merit_bread_backup_" . date('d-m-Y_H-i-s') . ".sql";
$command  = "mysqldump --user=root --password= distribution_db > C:\\xampp\\htdocs\\distribution\\backups\\$filename";
exec($command);

// Download
$filepath = "C:\\xampp\\htdocs\\distribution\\backups\\$filename";
if (file_exists($filepath)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    readfile($filepath);
    unlink($filepath);
} else {
    // Manual backup via phpMyAdmin
    header("Location: http://localhost/phpmyadmin/index.php?route=/database/export&db=distribution_db");
}
exit();
?>