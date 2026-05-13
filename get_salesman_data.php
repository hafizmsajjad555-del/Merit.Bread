<?php
include 'config.php';

$salesman = mysqli_real_escape_string($conn, $_GET['salesman'] ?? '');
$date     = $_GET['date'] ?? date('Y-m-d');
$city     = mysqli_real_escape_string($conn, $_GET['city'] ?? '');

if (!$salesman || !$city) {
    echo json_encode(['found' => false]); exit();
}

// 1st Voucher Dispatch
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM dispatch_first
     WHERE salesman_name='$salesman' AND city='$city' AND dispatch_date='$date'"));
$fv = $r['total'];

// 1st Voucher Replace
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM replace_first
     WHERE salesman_name='$salesman' AND city='$city' AND replace_date='$date'"));
$fv_rep = $r['total'];

// 2nd Voucher Dispatch
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM dispatch_second
     WHERE salesman_name='$salesman' AND city='$city' AND dispatch_date='$date'"));
$sv = $r['total'];

// 2nd Voucher Replace
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM replace_second
     WHERE salesman_name='$salesman' AND city='$city' AND replace_date='$date'"));
$sv_rep = $r['total'];

// Finish Good
$r = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COALESCE(SUM(amount),0) as total FROM finish_good
     WHERE salesman_name='$salesman' AND city='$city' AND finish_date='$date'"));
$fg = $r['total'];

$found = ($fv + $fv_rep + $sv + $sv_rep + $fg) > 0;

echo json_encode([
    'found'          => $found,
    'first_voucher'  => $fv,
    'first_vou_rep'  => $fv_rep,
    'second_voucher' => $sv,
    'second_vou_rep' => $sv_rep,
    'finish_goods'   => $fg
]);