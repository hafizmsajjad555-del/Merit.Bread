<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$is_admin = ($_SESSION['role'] == 'admin');
$type     = $_GET['type']     ?? 'distribution';
$city     = $_GET['city']     ?? $_SESSION['city'];
$from     = $_GET['date_from']?? date('Y-m-01');
$to       = $_GET['date_to']  ?? date('Y-m-d');
$salesman = $_GET['salesman'] ?? '';

$where = "WHERE 1=1";
if (!$is_admin)  $where .= " AND d.agent_id='".$_SESSION['user_id']."'";
if ($city)       $where .= " AND d.city='".mysqli_real_escape_string($conn,$city)."'";
if ($salesman)   $where .= " AND d.salesman_name LIKE '%".mysqli_real_escape_string($conn,$salesman)."%'";
if ($from && $to) $where .= " AND DATE(d.entry_date) BETWEEN '$from' AND '$to'";

// Distribution Sheet Excel
if ($type == 'distribution') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Distribution_Sheet_'.date('d-m-Y').'.xls"');
    header('Pragma: no-cache');

    $data = mysqli_query($conn,
        "SELECT d.*, u.username FROM distribution_sheet d
         JOIN users u ON d.agent_id=u.id
         $where ORDER BY d.city, d.entry_date DESC");

    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "<table border='1'>
    <tr style='background:#1a1a2e;color:white;font-weight:bold;'>
        <th>City</th><th>Salesman</th><th>Father</th><th>Sector</th>
        <th>1st Voucher</th><th>1st Replace</th><th>Net Sales</th>
        <th>2nd Voucher</th><th>2nd Replace</th><th>Total Net Sales</th>
        <th>Total Dispatch</th><th>Finish Goods</th>
        <th>Fuel</th><th>Fuel%</th>
        <th>TD</th><th>MC</th><th>Market Bill</th><th>Free Sampling</th>
        <th>Total MC</th><th>MC%</th><th>Total Tax</th>
        <th>Van Paid</th><th>Van Payable</th>
        <th>Inc Base</th><th>Disp Inc</th>
        <th>Commission</th><th>Dealer</th>
        <th>Total Expenses</th><th>Exp%</th>
        <th>Cash Received</th><th>Cash Send</th><th>IOU</th>
        <th>Center Exp</th><th>Adjustment</th><th>Closing Balance</th>
        <th>Cash In Hand</th><th>Physical Stock</th><th>Date</th>
    </tr>";

    $grand = array_fill_keys([
        'first_voucher','first_vou_rep','net_sales','second_voucher',
        'second_vou_rep','total_net_sales','total_dispatch','finish_goods',
        'fuel','total_mc','total_tax','van_rent_paid','commission',
        'dealer','total_expenses','cash_received','cash_send',
        'iou','center_expenses','closing_balance'
    ], 0);

    while($row = mysqli_fetch_assoc($data)) {
        foreach(array_keys($grand) as $k) {
            if(isset($row[$k])) $grand[$k] += $row[$k];
        }
        echo "<tr>
            <td>{$row['city']}</td>
            <td>{$row['salesman_name']}</td>
            <td>{$row['father_name']}</td>
            <td>{$row['sector_name']}</td>
            <td>{$row['first_voucher']}</td>
            <td>{$row['first_vou_rep']}</td>
            <td>{$row['net_sales']}</td>
            <td>{$row['second_voucher']}</td>
            <td>{$row['second_vou_rep']}</td>
            <td>{$row['total_net_sales']}</td>
            <td>{$row['total_dispatch']}</td>
            <td>{$row['finish_goods']}</td>
            <td>{$row['fuel']}</td>
            <td>{$row['fuel_percentage']}%</td>
            <td>{$row['td']}</td>
            <td>{$row['mc']}</td>
            <td>{$row['market_bill']}</td>
            <td>{$row['free_sampling']}</td>
            <td>{$row['total_mc']}</td>
            <td>{$row['mc_percentage']}%</td>
            <td>{$row['total_tax']}</td>
            <td>{$row['van_rent_paid']}</td>
            <td>{$row['van_rent_payable']}</td>
            <td>{$row['incentive_exp_base']}</td>
            <td>{$row['dispatch_bach_incentive']}</td>
            <td>{$row['commission']}</td>
            <td>{$row['dealer']}</td>
            <td>{$row['total_expenses']}</td>
            <td>{$row['exp_percentage']}%</td>
            <td>{$row['cash_received']}</td>
            <td>{$row['cash_send']}</td>
            <td>{$row['iou']}</td>
            <td>{$row['center_expenses']}</td>
            <td>{$row['adjustment']}</td>
            <td>{$row['closing_balance']}</td>
            <td>{$row['cash_in_hand']}</td>
            <td>{$row['physical_stock']}</td>
            <td>".date('d-m-Y',strtotime($row['entry_date']))."</td>
        </tr>";
    }

    // Grand Total Row
    echo "<tr style='background:#e8f5e9;font-weight:bold;'>
        <td colspan='4'>GRAND TOTAL</td>
        <td>{$grand['first_voucher']}</td>
        <td>{$grand['first_vou_rep']}</td>
        <td>{$grand['net_sales']}</td>
        <td>{$grand['second_voucher']}</td>
        <td>{$grand['second_vou_rep']}</td>
        <td>{$grand['total_net_sales']}</td>
        <td>{$grand['total_dispatch']}</td>
        <td>{$grand['finish_goods']}</td>
        <td>{$grand['fuel']}</td>
        <td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>
        <td>{$grand['total_mc']}</td>
        <td>—</td>
        <td>{$grand['total_tax']}</td>
        <td>{$grand['van_rent_paid']}</td>
        <td>—</td><td>—</td><td>—</td>
        <td>{$grand['commission']}</td>
        <td>{$grand['dealer']}</td>
        <td>{$grand['total_expenses']}</td>
        <td>—</td>
        <td>{$grand['cash_received']}</td>
        <td>{$grand['cash_send']}</td>
        <td>{$grand['iou']}</td>
        <td>{$grand['center_expenses']}</td>
        <td>—</td>
        <td>{$grand['closing_balance']}</td>
        <td>—</td><td>—</td><td>—</td>
    </tr>";
    echo "</table>";
}

// Item Wise Excel
elseif ($type == 'item_wise') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="Item_Wise_Report_'.date('d-m-Y').'.xls"');

    $cw = "";
    if (!$is_admin) $cw .= " AND city='".mysqli_real_escape_string($conn,$_SESSION['city'])."' AND agent_id='".$_SESSION['user_id']."'";
    elseif ($city)  $cw .= " AND city='".mysqli_real_escape_string($conn,$city)."'";
    $sw = $salesman ? " AND salesman_name LIKE '%".mysqli_real_escape_string($conn,$salesman)."%'" : "";
    $dw = " AND DATE(%s) BETWEEN '$from' AND '$to'";

    $types = [
        ['dispatch_first',  'dispatch_date','1st Voucher Dispatch', false],
        ['replace_first',   'replace_date', '1st Voucher Replace',  true],
        ['dispatch_second', 'dispatch_date','2nd Voucher Dispatch', false],
        ['replace_second',  'replace_date', '2nd Voucher Replace',  true],
        ['finish_good',     'finish_date',  'Finish Good',          false],
        ['replace_finish',  'replace_date', 'FG Replace',           true],
    ];

    echo "\xEF\xBB\xBF";
    echo "<table border='1'>";

    foreach ($types as $t) {
        list($tbl,$dcol,$label,$is_rep) = $t;
        $qty_col = $is_rep ? 't.total_qty' : 't.quantity';
        $sel_rep = $is_rep ? ", t.qty_1st, t.qty_2nd" : "";
        $d_where = sprintf($dw, "t.$dcol");

        $rows = mysqli_query($conn,
            "SELECT t.city, t.salesman_name, $qty_col as quantity,
             t.amount $sel_rep, i.item_name, i.rate as item_rate
             FROM $tbl t JOIN items i ON t.item_id=i.id
             WHERE 1=1 $cw $sw $d_where
             ORDER BY t.salesman_name, i.sort_order");

        echo "<tr style='background:#1a1a2e;color:white;'>
            <td colspan='10'><b>$label</b></td>
        </tr>
        <tr style='background:#f0f0f0;font-weight:bold;'>
            <td>City</td><td>Salesman</td><td>Item</td><td>Rate</td>";
        if ($is_rep) echo "<td>1st Qty</td><td>2nd Qty</td>";
        echo "<td>Qty</td><td>Amount</td>
        </tr>";

        $total = 0;
        while ($row = mysqli_fetch_assoc($rows)) {
            $total += $row['amount'];
            echo "<tr>
                <td>{$row['city']}</td>
                <td>{$row['salesman_name']}</td>
                <td>{$row['item_name']}</td>
                <td>{$row['item_rate']}</td>";
            if ($is_rep) echo "<td>{$row['qty_1st']}</td><td>{$row['qty_2nd']}</td>";
            echo "<td>{$row['quantity']}</td>
                <td>{$row['amount']}</td>
            </tr>";
        }
        echo "<tr style='background:#e8f5e9;font-weight:bold;'>
            <td colspan='".($is_rep?6:4)."'>Total — $label</td>
            <td>".number_format($total,2)."</td>
        </tr>
        <tr><td colspan='8'></td></tr>";
    }
    echo "</table>";
}

exit();
?>