<?php
include 'config.php';
$item_id = intval($_GET['item_id']);
$user_id = $_SESSION['user_id'];
$check = mysqli_num_rows(mysqli_query($conn,
    "SELECT id FROM favourite_items WHERE user_id='$user_id' AND item_id='$item_id'"));
if ($check > 0) {
    mysqli_query($conn, "DELETE FROM favourite_items WHERE user_id='$user_id' AND item_id='$item_id'");
    echo json_encode(['status'=>'removed']);
} else {
    mysqli_query($conn, "INSERT INTO favourite_items (user_id,item_id) VALUES ('$user_id','$item_id')");
    echo json_encode(['status'=>'added']);
}