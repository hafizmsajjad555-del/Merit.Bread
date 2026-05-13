<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$is_admin = ($_SESSION['role'] == 'admin');
$city     = $_SESSION['city'];
$user_id  = $_SESSION['user_id'];

$filter_city     = isset($_GET['city'])      ? mysqli_real_escape_string($conn, $_GET['city'])     : ($is_admin ? '' : $city);
$filter_salesman = isset($_GET['salesman'])  ? mysqli_real_escape_string($conn, $_GET['salesman']) : '';
$filter_from     = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$filter_to       = isset($_GET['date_to'])   ? $_GET['date_to']   : date('Y-m-d');
$active_tab      = isset($_GET['tab'])       ? $_GET['tab']       : 'summary';

$cities_list = mysqli_query($conn, "SELECT DISTINCT city FROM users WHERE role='agent' ORDER BY city");

$city_where = "";
if ($filter_city) {
    $city_where .= " AND city='$filter_city'";
} elseif (!$is_admin) {
    $city_where .= " AND city='$city'";
}
if (!$is_admin) {
    $city_where .= " AND agent_id='$user_id'";
}

$sal_where = $filter_salesman ? " AND salesman_name LIKE '%$filter_salesman%'" : "";

// Delete
if (isset($_GET['del']) && isset($_GET['dtbl'])) {
    $del_id  = intval($_GET['del']);
    $del_tbl = mysqli_real_escape_string($conn, $_GET['dtbl']);
    if ($is_admin) {
        mysqli_query($conn, "DELETE FROM $del_tbl WHERE id='$del_id'");
    } else {
        if (mysqli_num_rows(mysqli_query($conn, "SELECT id FROM $del_tbl WHERE id='$del_id' AND agent_id='$user_id'")) > 0) {
            mysqli_query($conn, "DELETE FROM $del_tbl WHERE id='$del_id'");
        }
    }
    header("Location: item_report.php?tab=$active_tab&city=$filter_city&date_from=$filter_from&date_to=$filter_to&salesman=".urlencode($filter_salesman));
    exit();
}

// Edit
$edit_success = "";
if (isset($_POST['edit_entry'])) {
    $id  = intval($_POST['entry_id']);
    $tbl = mysqli_real_escape_string($conn, $_POST['entry_table']);
    $can_edit = $is_admin ? true : mysqli_num_rows(mysqli_query($conn, "SELECT id FROM $tbl WHERE id='$id' AND agent_id='$user_id'")) > 0;
    if ($can_edit) {
        if (in_array($tbl, ['dispatch_first','dispatch_second','finish_good','factory_receiving'])) {
            $qty    = intval($_POST['quantity']);
            $rate_r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT rate FROM items WHERE id=(SELECT item_id FROM $tbl WHERE id='$id')"));
            $rate   = $rate_r['rate'];
            $amt    = $qty * $rate;
            $acol   = $tbl == 'factory_receiving' ? 'total_amount' : 'amount';
            mysqli_query($conn, "UPDATE $tbl SET quantity='$qty', $acol='$amt' WHERE id='$id'");
        } else {
            $q1     = intval($_POST['qty_1st']);
            $q2     = intval($_POST['qty_2nd']);
            $tot    = $q1 + $q2;
            $rate_r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT rate FROM items WHERE id=(SELECT item_id FROM $tbl WHERE id='$id')"));
            $rate   = $rate_r['rate'];
            $amt    = $tot * $rate;
            mysqli_query($conn, "UPDATE $tbl SET qty_1st='$q1',qty_2nd='$q2',total_qty='$tot',amount='$amt' WHERE id='$id'");
        }
        $edit_success = "✅ Entry updated!";
    }
}

// Get voucher total
function getVoucherTotal($conn, $table, $date_col, $city_where, $sal_where, $from, $to, $is_replace=false) {
    $qty = $is_replace ? 'SUM(total_qty)' : 'SUM(quantity)';
    $amt = $table == 'factory_receiving' ? 'SUM(total_amount)' : 'SUM(amount)';
    $use_sal = ($table == 'factory_receiving') ? '' : $sal_where;
    $r = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT $qty as qty, $amt as amt FROM $table
         WHERE DATE($date_col) BETWEEN '$from' AND '$to' $city_where $use_sal"));
    return $r;
}

// Get salesmen list
function getSalesmen($conn, $city_where, $sal_where, $from, $to) {
    $q = mysqli_query($conn,
        "SELECT DISTINCT salesman_name FROM (
            SELECT salesman_name FROM dispatch_first
            WHERE DATE(dispatch_date) BETWEEN '$from' AND '$to' $city_where $sal_where
            UNION
            SELECT salesman_name FROM dispatch_second
            WHERE DATE(dispatch_date) BETWEEN '$from' AND '$to' $city_where $sal_where
            UNION
            SELECT salesman_name FROM finish_good
            WHERE DATE(finish_date) BETWEEN '$from' AND '$to' $city_where $sal_where
            UNION
            SELECT salesman_name FROM replace_first
            WHERE DATE(replace_date) BETWEEN '$from' AND '$to' $city_where $sal_where
            UNION
            SELECT salesman_name FROM replace_second
            WHERE DATE(replace_date) BETWEEN '$from' AND '$to' $city_where $sal_where
            UNION
            SELECT salesman_name FROM replace_finish
            WHERE DATE(replace_date) BETWEEN '$from' AND '$to' $city_where $sal_where
        ) as all_s ORDER BY salesman_name");
    $arr = [];
    while($r = mysqli_fetch_assoc($q)) $arr[] = $r['salesman_name'];
    return $arr;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Item Wise Report — Merit Bread DMS</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f0f2f5; }
        .header {
            background:linear-gradient(135deg,#1a1a2e,#16213e);
            color:white; padding:15px 30px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .header h1 { font-size:18px; }
        .header a { color:#ff6b6b; text-decoration:none; margin-left:12px; font-size:13px; }
        .header a.green  { color:#90EE90; }
        .header a.yellow { color:#FFD700; }
        .container { max-width:1400px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .filter-card { background:white; border-radius:12px; padding:20px 25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .filter-grid { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
        .field label { display:block; font-size:11px; color:#666; margin-bottom:5px; font-weight:bold; text-transform:uppercase; }
        .field input,.field select { padding:9px 12px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .field input:focus,.field select:focus { border-color:#4CAF50; outline:none; }
        .btn-filter { padding:10px 20px; background:#4CAF50; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold; }
        .btn-reset  { padding:10px 15px; background:#ff6b6b; color:white; border-radius:8px; text-decoration:none; font-size:13px; display:inline-block; }
        .btn-print  { padding:10px 15px; background:#1a1a2e; color:white; border:none; border-radius:8px; cursor:pointer; font-size:13px; }
        .view-tabs  { display:flex; gap:10px; margin-bottom:20px; }
        .view-tab   { padding:10px 25px; border-radius:8px; font-weight:bold; font-size:13px; text-decoration:none; border:2px solid #eee; background:white; color:#666; }
        .view-tab.active { background:#1a1a2e; color:white; border-color:#1a1a2e; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:16px; padding-bottom:10px; border-bottom:2px solid #4CAF50; }
        .voucher-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:15px; margin-bottom:25px; }
        .v-card { border-radius:12px; padding:20px; color:white; }
        .v-card h3 { font-size:13px; margin-bottom:12px; opacity:0.9; }
        .v-card .amt { font-size:26px; font-weight:bold; }
        .v-card .qty { font-size:12px; opacity:0.8; margin-top:4px; }
        .sum-tbl { width:100%; border-collapse:collapse; font-size:12px; }
        .sum-tbl th { background:#1a1a2e; color:white; padding:9px 10px; text-align:left; white-space:nowrap; }
        .sum-tbl td { padding:8px 10px; border-bottom:1px solid #eee; white-space:nowrap; }
        .sum-tbl tr:hover td { background:#f9f9f9; }
        .sum-tbl tfoot td { background:#e8f5e9; font-weight:bold; border-top:2px solid #4CAF50; }
        .tbl-wrap { overflow-x:auto; }
        .dtbl { width:100%; border-collapse:collapse; font-size:12px; min-width:700px; }
        .dtbl th { background:#1a1a2e; color:white; padding:9px 10px; text-align:left; white-space:nowrap; }
        .dtbl td { padding:7px 10px; border-bottom:1px solid #eee; white-space:nowrap; }
        .dtbl tr:hover td { background:#f9f9f9; }
        .dtbl tfoot td { background:#e8f5e9; font-weight:bold; border-top:2px solid #4CAF50; }
        .btn-e { background:#FF9800; color:white; padding:4px 9px; border:none; border-radius:5px; cursor:pointer; font-size:11px; }
        .btn-d { background:#F44336; color:white; padding:4px 9px; border:none; border-radius:5px; cursor:pointer; font-size:11px; text-decoration:none; display:inline-block; }
        .modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:999; justify-content:center; align-items:center; }
        .modal.open { display:flex; }
        .modal-box { background:white; border-radius:12px; padding:25px; width:360px; }
        .modal-box h3 { margin-bottom:15px; color:#1a1a2e; border-bottom:2px solid #4CAF50; padding-bottom:10px; }
        .mfield { margin-bottom:12px; }
        .mfield label { display:block; font-size:11px; color:#666; margin-bottom:4px; font-weight:bold; text-transform:uppercase; }
        .mfield input { width:100%; padding:9px 12px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .mfield input:focus { border-color:#4CAF50; outline:none; }
        .mfield input.ro { background:#f8f9fa; color:#888; }
        .btn-sm { padding:10px; border:none; border-radius:8px; width:100%; font-size:13px; cursor:pointer; font-weight:bold; margin-top:5px; }
        .btn-save-m { background:linear-gradient(135deg,#4CAF50,#2e7d32); color:white; }
        .btn-cls-m  { background:#eee; color:#666; }
        @media print {
            .header,.filter-card,.view-tabs,.btn-e,.btn-d,.modal { display:none !important; }
            .card { box-shadow:none !important; }
            body { background:white !important; }
        }
    </style>
    <script>
    function openEdit(id, tbl, item, isReplace, qty, q1, q2, rate) {
        document.getElementById('m_id').value   = id;
        document.getElementById('m_tbl').value  = tbl;
        document.getElementById('m_item').value = item;
        document.getElementById('m_rate').value = 'Rs. ' + parseFloat(rate).toFixed(2);
        if (isReplace) {
            document.getElementById('m_qty_row').style.display = 'none';
            document.getElementById('m_rep_row').style.display = 'block';
            document.getElementById('m_q1').value = q1;
            document.getElementById('m_q2').value = q2;
        } else {
            document.getElementById('m_qty_row').style.display = 'block';
            document.getElementById('m_rep_row').style.display = 'none';
            document.getElementById('m_qty').value = qty;
        }
        document.getElementById('editModal').classList.add('open');
    }
    function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — Item Wise Report</h1>
    <div>
        <button onclick="window.print()" style="background:rgba(255,255,255,0.15);color:white;padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.3);cursor:pointer;font-size:12px;">🖨️ Print</button>
        <?php if($is_admin): ?>
        <a href="admin_dashboard.php" class="green">← Admin</a>
        <?php else: ?>
        <a href="item_entry.php" class="green">← Entry</a>
        <?php endif; ?>
        <a href="change_password.php" class="yellow">🔐</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if($edit_success) echo "<div class='alert-s'>$edit_success</div>"; ?>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET">
            <input type="hidden" name="tab" value="<?= $active_tab ?>">
            <div class="filter-grid">
                <?php if($is_admin): ?>
                <div class="field">
                    <label>City</label>
                    <select name="city">
                        <option value="">-- All Cities --</option>
                        <?php mysqli_data_seek($cities_list,0);
                        while($c = mysqli_fetch_assoc($cities_list)): ?>
                        <option value="<?= $c['city'] ?>" <?= $filter_city==$c['city']?'selected':'' ?>>
                            <?= $c['city'] ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="field">
                    <label>Salesman</label>
                    <input type="text" name="salesman" placeholder="Search..." value="<?= $filter_salesman ?>" style="width:160px;">
                </div>
                <div class="field">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= $filter_from ?>">
                </div>
                <div class="field">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= $filter_to ?>">
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn-filter">🔍 Filter</button>
                    <a href="item_report.php?tab=<?= $active_tab ?>" class="btn-reset">↺</a>
<a href="export_excel.php?type=item_wise&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>"
   style="background:#217346;color:white;padding:9px 15px;border-radius:8px;text-decoration:none;font-size:13px;">
   📊 Excel Download
</a>
                    <button type="button" onclick="window.print()" class="btn-print">🖨️</button>
                </div>
            </div>
        </form>
    </div>

    <!-- View Tabs -->
    <div class="view-tabs">
        <a href="?tab=summary&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>"
           class="view-tab <?= $active_tab=='summary'?'active':'' ?>">📊 Voucher Summary</a>
        <a href="?tab=salesman&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>"
           class="view-tab <?= $active_tab=='salesman'?'active':'' ?>">👤 Salesman Wise</a>
        <a href="?tab=entries&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>"
           class="view-tab <?= $active_tab=='entries'?'active':'' ?>">📋 All Entries</a>
    </div>

    <?php
    // ========== TAB 1: VOUCHER SUMMARY ==========
    if ($active_tab == 'summary'):
        $v_d1  = getVoucherTotal($conn,'dispatch_first',  'dispatch_date',$city_where,$sal_where,$filter_from,$filter_to);
        $v_r1  = getVoucherTotal($conn,'replace_first',   'replace_date', $city_where,$sal_where,$filter_from,$filter_to,true);
        $v_d2  = getVoucherTotal($conn,'dispatch_second', 'dispatch_date',$city_where,$sal_where,$filter_from,$filter_to);
        $v_r2  = getVoucherTotal($conn,'replace_second',  'replace_date', $city_where,$sal_where,$filter_from,$filter_to,true);
        $v_fg  = getVoucherTotal($conn,'finish_good',     'finish_date',  $city_where,$sal_where,$filter_from,$filter_to);
        $v_rfg = getVoucherTotal($conn,'replace_finish',  'replace_date', $city_where,$sal_where,$filter_from,$filter_to,true);
    ?>

    <!-- Voucher Cards -->
    <div class="voucher-grid">
        <div class="v-card" style="background:linear-gradient(135deg,#1565c0,#0D47A1)">
            <h3>📦 1st Voucher Dispatch</h3>
            <div class="amt">Rs.<?= number_format($v_d1['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_d1['qty']??0) ?> pcs</div>
        </div>
        <div class="v-card" style="background:linear-gradient(135deg,#e65100,#BF360C)">
            <h3>🔄 1st Voucher Replace</h3>
            <div class="amt">Rs.<?= number_format($v_r1['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_r1['qty']??0) ?> pcs</div>
        </div>
        <div class="v-card" style="background:linear-gradient(135deg,#4A148C,#311B92)">
            <h3>📦 2nd Voucher Dispatch</h3>
            <div class="amt">Rs.<?= number_format($v_d2['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_d2['qty']??0) ?> pcs</div>
        </div>
        <div class="v-card" style="background:linear-gradient(135deg,#B71C1C,#880E4F)">
            <h3>🔄 2nd Voucher Replace</h3>
            <div class="amt">Rs.<?= number_format($v_r2['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_r2['qty']??0) ?> pcs</div>
        </div>
        <div class="v-card" style="background:linear-gradient(135deg,#1B5E20,#33691E)">
            <h3>✅ Finish Good</h3>
            <div class="amt">Rs.<?= number_format($v_fg['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_fg['qty']??0) ?> pcs</div>
        </div>
        <div class="v-card" style="background:linear-gradient(135deg,#004D40,#006064)">
            <h3>🔄 Finish Good Replace</h3>
            <div class="amt">Rs.<?= number_format($v_rfg['amt']??0,0) ?></div>
            <div class="qty">📦 <?= number_format($v_rfg['qty']??0) ?> pcs</div>
        </div>
    </div>

    <!-- Salesman Summary Table -->
    <div class="card">
        <h2>📊 Salesman Wise Summary — <?= date('d-m-Y',strtotime($filter_from)) ?> to <?= date('d-m-Y',strtotime($filter_to)) ?></h2>
        <?php $salesmen = getSalesmen($conn,$city_where,$sal_where,$filter_from,$filter_to); ?>
        <div class="tbl-wrap">
        <table class="sum-tbl">
            <thead><tr>
                <th>#</th><th>Salesman</th>
                <th style="background:#1565c0">1st Dispatch</th>
                <th style="background:#e65100">1st Replace</th>
                <th style="background:#1565c0">Net 1st</th>
                <th style="background:#4A148C">2nd Dispatch</th>
                <th style="background:#B71C1C">2nd Replace</th>
                <th style="background:#4A148C">Net 2nd</th>
                <th style="background:#1B5E20">Finish Good</th>
                <th style="background:#004D40">FG Replace</th>
                <th style="background:#1B5E20">Net FG</th>
                <th style="background:#263238">Grand Total</th>
            </tr></thead>
            <tbody>
            <?php
            $gt_d1=0;$gt_r1=0;$gt_d2=0;$gt_r2=0;$gt_fg=0;$gt_rfg=0;
            $i=1;
            foreach($salesmen as $sname):
                $se = mysqli_real_escape_string($conn,$sname);
                $sd1  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM dispatch_first  WHERE salesman_name='$se' AND DATE(dispatch_date) BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $sr1  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM replace_first   WHERE salesman_name='$se' AND DATE(replace_date)  BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $sd2  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM dispatch_second WHERE salesman_name='$se' AND DATE(dispatch_date) BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $sr2  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM replace_second  WHERE salesman_name='$se' AND DATE(replace_date)  BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $sfg  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM finish_good     WHERE salesman_name='$se' AND DATE(finish_date)   BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $srfg = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COALESCE(SUM(amount),0) as a FROM replace_finish  WHERE salesman_name='$se' AND DATE(replace_date)  BETWEEN '$filter_from' AND '$filter_to' $city_where"));
                $d1=$sd1['a']; $r1=$sr1['a']; $d2=$sd2['a']; $r2=$sr2['a']; $fg=$sfg['a']; $rfg=$srfg['a'];
                $net1=$d1-$r1; $net2=$d2-$r2; $netfg=$fg-$rfg; $grand=$net1+$net2+$netfg;
                $gt_d1+=$d1;$gt_r1+=$r1;$gt_d2+=$d2;$gt_r2+=$r2;$gt_fg+=$fg;$gt_rfg+=$rfg;
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($sname) ?></strong></td>
                <td style="color:#1565c0;font-weight:bold;">Rs.<?= number_format($d1,0) ?></td>
                <td style="color:#e65100;font-weight:bold;">Rs.<?= number_format($r1,0) ?></td>
                <td style="background:#e3f2fd;color:#1565c0;font-weight:bold;">Rs.<?= number_format($net1,0) ?></td>
                <td style="color:#4A148C;font-weight:bold;">Rs.<?= number_format($d2,0) ?></td>
                <td style="color:#B71C1C;font-weight:bold;">Rs.<?= number_format($r2,0) ?></td>
                <td style="background:#f3e5f5;color:#4A148C;font-weight:bold;">Rs.<?= number_format($net2,0) ?></td>
                <td style="color:#1B5E20;font-weight:bold;">Rs.<?= number_format($fg,0) ?></td>
                <td style="color:#004D40;font-weight:bold;">Rs.<?= number_format($rfg,0) ?></td>
                <td style="background:#e8f5e9;color:#1B5E20;font-weight:bold;">Rs.<?= number_format($netfg,0) ?></td>
                <td style="background:#1a1a2e;color:#4CAF50;font-weight:bold;font-size:13px;">Rs.<?= number_format($grand,0) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="2"><strong>🔢 GRAND TOTAL</strong></td>
                <td>Rs.<?= number_format($gt_d1,0) ?></td>
                <td>Rs.<?= number_format($gt_r1,0) ?></td>
                <td style="background:#e3f2fd;"><strong>Rs.<?= number_format($gt_d1-$gt_r1,0) ?></strong></td>
                <td>Rs.<?= number_format($gt_d2,0) ?></td>
                <td>Rs.<?= number_format($gt_r2,0) ?></td>
                <td style="background:#f3e5f5;"><strong>Rs.<?= number_format($gt_d2-$gt_r2,0) ?></strong></td>
                <td>Rs.<?= number_format($gt_fg,0) ?></td>
                <td>Rs.<?= number_format($gt_rfg,0) ?></td>
                <td style="background:#e8f5e9;"><strong>Rs.<?= number_format($gt_fg-$gt_rfg,0) ?></strong></td>
                <td style="background:#1a1a2e;color:#4CAF50;"><strong>Rs.<?= number_format(($gt_d1-$gt_r1)+($gt_d2-$gt_r2)+($gt_fg-$gt_rfg),0) ?></strong></td>
            </tr></tfoot>
        </table>
        </div>
    </div>

    <?php
    // ========== TAB 2: SALESMAN WISE DETAIL ==========
    elseif($active_tab == 'salesman'):
        $salesmen2 = getSalesmen($conn,$city_where,$sal_where,$filter_from,$filter_to);
        $types = [
            ['dispatch_first',  'dispatch_date','📦 1st Voucher Dispatch','#1565c0',false],
            ['replace_first',   'replace_date', '🔄 1st Voucher Replace', '#e65100',true],
            ['dispatch_second', 'dispatch_date','📦 2nd Voucher Dispatch','#4A148C',false],
            ['replace_second',  'replace_date', '🔄 2nd Voucher Replace', '#B71C1C',true],
            ['finish_good',     'finish_date',  '✅ Finish Good',          '#1B5E20',false],
            ['replace_finish',  'replace_date', '🔄 FG Replace',          '#004D40',true],
        ];
        foreach($salesmen2 as $sname):
            $se = mysqli_real_escape_string($conn,$sname);
            $sal_grand = 0;
    ?>
    <div class="card">
        <h2>👤 <?= htmlspecialchars($sname) ?></h2>
        <?php foreach($types as $t):
            list($tbl,$dcol,$label,$clr,$is_rep) = $t;
            $qty_col = $is_rep ? 't.total_qty' : 't.quantity';
            $sel_rep = $is_rep ? ", t.qty_1st, t.qty_2nd" : "";
            $rows_q  = mysqli_query($conn,
                "SELECT t.id, t.city, t.salesman_name,
                 $qty_col as quantity, t.amount $sel_rep,
                 i.item_name, i.rate as item_rate
                 FROM $tbl t JOIN items i ON t.item_id=i.id
                 WHERE t.salesman_name='$se'
                 AND DATE(t.$dcol) BETWEEN '$filter_from' AND '$filter_to'
                 $city_where
                 ORDER BY i.sort_order");
            $rows = [];
            while($r = mysqli_fetch_assoc($rows_q)) $rows[] = $r;
            if (empty($rows)) continue;
            $sub = array_sum(array_column($rows,'amount'));
            $sal_grand += $sub;
        ?>
        <div style="margin-bottom:15px;border-left:4px solid <?= $clr ?>;padding-left:12px;">
            <div style="color:<?= $clr ?>;font-weight:bold;font-size:13px;margin-bottom:8px;display:flex;justify-content:space-between;">
                <span><?= $label ?></span>
                <span>Rs.<?= number_format($sub,0) ?></span>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:12px;">
                <thead><tr style="background:#f8f9fa;">
                    <th style="padding:6px 10px;text-align:left;border-bottom:1px solid #eee;">#</th>
                    <th style="padding:6px 10px;text-align:left;border-bottom:1px solid #eee;">Item</th>
                    <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #eee;">Rate</th>
                    <?php if($is_rep): ?>
                    <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #eee;">1st Qty</th>
                    <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #eee;">2nd Qty</th>
                    <?php endif; ?>
                    <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #eee;">Qty</th>
                    <th style="padding:6px 10px;text-align:right;border-bottom:1px solid #eee;">Amount</th>
                    <th style="padding:6px 10px;text-align:center;border-bottom:1px solid #eee;">Action</th>
                </tr></thead>
                <tbody>
                <?php foreach($rows as $j => $row): ?>
                <tr>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;"><?= $j+1 ?></td>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;"><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:right;">Rs.<?= number_format($row['item_rate'],2) ?></td>
                    <?php if($is_rep): ?>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:right;"><?= $row['qty_1st'] ?></td>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:right;"><?= $row['qty_2nd'] ?></td>
                    <?php endif; ?>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:right;"><?= $row['quantity'] ?></td>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:right;color:<?= $clr ?>;font-weight:bold;">Rs.<?= number_format($row['amount'],2) ?></td>
                    <td style="padding:6px 10px;border-bottom:1px solid #f5f5f5;text-align:center;">
                        <button class="btn-e" onclick="openEdit(<?= $row['id'] ?>,'<?= $tbl ?>','<?= addslashes($row['item_name']) ?>',<?= $is_rep?'true':'false' ?>,<?= $row['quantity'] ?>,<?= $is_rep?$row['qty_1st']:0 ?>,<?= $is_rep?$row['qty_2nd']:0 ?>,<?= $row['item_rate'] ?>)">✏️</button>
                        <a href="?tab=salesman&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>&del=<?= $row['id'] ?>&dtbl=<?= $tbl ?>" onclick="return confirm('Delete?')" class="btn-d">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>
        <div style="text-align:right;padding:10px 0;font-size:15px;font-weight:bold;border-top:2px solid #4CAF50;margin-top:10px;">
            Grand Total: <span style="color:#4CAF50;">Rs.<?= number_format($sal_grand,0) ?></span>
        </div>
    </div>
    <?php endforeach; ?>

    <?php
    // ========== TAB 3: ALL ENTRIES ==========
    elseif($active_tab == 'entries'):
        $all_types = [
            ['dispatch_first',  'dispatch_date','📦 1st Voucher Dispatch','#1565c0',false],
            ['replace_first',   'replace_date', '🔄 1st Voucher Replace', '#e65100',true],
            ['dispatch_second', 'dispatch_date','📦 2nd Voucher Dispatch','#4A148C',false],
            ['replace_second',  'replace_date', '🔄 2nd Voucher Replace', '#B71C1C',true],
            ['finish_good',     'finish_date',  '✅ Finish Good',          '#1B5E20',false],
            ['replace_finish',  'replace_date', '🔄 FG Replace',          '#004D40',true],
        ];
        foreach($all_types as $t):
            list($tbl,$dcol,$label,$clr,$is_rep) = $t;
            $qty_col = $is_rep ? 't.total_qty' : 't.quantity';
            $sel_rep = $is_rep ? ", t.qty_1st, t.qty_2nd" : "";
            $rows_q  = mysqli_query($conn,
                "SELECT t.id, t.city, t.salesman_name,
                 $qty_col as quantity, t.amount $sel_rep,
                 i.item_name, i.rate as item_rate, t.$dcol as entry_date
                 FROM $tbl t JOIN items i ON t.item_id=i.id
                 WHERE DATE(t.$dcol) BETWEEN '$filter_from' AND '$filter_to'
                 $city_where $sal_where
                 ORDER BY t.salesman_name, i.sort_order");
            $rows = [];
            while($r = mysqli_fetch_assoc($rows_q)) $rows[] = $r;
            if (empty($rows)) continue;
            $total_amt = array_sum(array_column($rows,'amount'));
    ?>
    <div class="card">
        <h2 style="color:<?= $clr ?>;border-color:<?= $clr ?>;">
            <?= $label ?>
            <span style="font-size:13px;color:#888;font-weight:normal;">
                Total: <strong style="color:<?= $clr ?>">Rs.<?= number_format($total_amt,0) ?></strong>
            </span>
        </h2>
        <div class="tbl-wrap">
        <table class="dtbl">
            <thead><tr>
                <th>Action</th><th>#</th>
                <?php if($is_admin): ?><th>City</th><?php endif; ?>
                <th>Salesman</th><th>Item</th><th>Rate</th>
                <?php if($is_rep): ?><th>1st Qty</th><th>2nd Qty</th><?php endif; ?>
                <th>Qty</th><th>Amount</th><th>Date</th>
            </tr></thead>
            <tbody>
            <?php $i=1; $gtotal=0; foreach($rows as $row): $gtotal+=$row['amount']; ?>
            <tr>
                <td>
                    <button class="btn-e" onclick="openEdit(<?= $row['id'] ?>,'<?= $tbl ?>','<?= addslashes($row['item_name']) ?>',<?= $is_rep?'true':'false' ?>,<?= $row['quantity'] ?>,<?= $is_rep?$row['qty_1st']:0 ?>,<?= $is_rep?$row['qty_2nd']:0 ?>,<?= $row['item_rate'] ?>)">✏️</button>
                    <a href="?tab=entries&city=<?= urlencode($filter_city) ?>&date_from=<?= $filter_from ?>&date_to=<?= $filter_to ?>&salesman=<?= urlencode($filter_salesman) ?>&del=<?= $row['id'] ?>&dtbl=<?= $tbl ?>" onclick="return confirm('Delete?')" class="btn-d">🗑️</a>
                </td>
                <td><?= $i++ ?></td>
                <?php if($is_admin): ?><td><?= $row['city'] ?></td><?php endif; ?>
                <td><strong><?= htmlspecialchars($row['salesman_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['item_name']) ?></td>
                <td>Rs.<?= number_format($row['item_rate'],2) ?></td>
                <?php if($is_rep): ?>
                <td><?= $row['qty_1st'] ?></td>
                <td><?= $row['qty_2nd'] ?></td>
                <?php endif; ?>
                <td><?= $row['quantity'] ?></td>
                <td style="color:<?= $clr ?>;font-weight:bold;">Rs.<?= number_format($row['amount'],2) ?></td>
                <td><?= date('d-m-Y',strtotime($row['entry_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="<?= $is_admin?($is_rep?9:7):($is_rep?8:6) ?>"><strong>Total</strong></td>
                <td style="color:<?= $clr ?>;font-weight:bold;">Rs.<?= number_format($gtotal,0) ?></td>
                <td></td>
            </tr></tfoot>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<!-- Edit Modal -->
<div class="modal" id="editModal">
    <div class="modal-box">
        <h3>✏️ Edit Entry</h3>
        <form method="POST">
            <input type="hidden" id="m_id"  name="entry_id">
            <input type="hidden" id="m_tbl" name="entry_table">
            <input type="hidden" name="tab"       value="<?= $active_tab ?>">
            <input type="hidden" name="city"      value="<?= $filter_city ?>">
            <input type="hidden" name="date_from" value="<?= $filter_from ?>">
            <input type="hidden" name="date_to"   value="<?= $filter_to ?>">
            <input type="hidden" name="salesman"  value="<?= $filter_salesman ?>">
            <div class="mfield"><label>Item</label>
                <input type="text" id="m_item" class="ro" readonly></div>
            <div class="mfield"><label>Rate</label>
                <input type="text" id="m_rate" class="ro" readonly></div>
            <div id="m_qty_row" class="mfield"><label>Quantity</label>
                <input type="number" id="m_qty" name="quantity" min="0"></div>
            <div id="m_rep_row" style="display:none">
                <div class="mfield"><label>1st Replace Qty</label>
                    <input type="number" id="m_q1" name="qty_1st" min="0"></div>
                <div class="mfield"><label>2nd Replace Qty</label>
                    <input type="number" id="m_q2" name="qty_2nd" min="0"></div>
            </div>
            <button type="submit" name="edit_entry" class="btn-sm btn-save-m">💾 Save</button>
            <button type="button" onclick="closeEdit()" class="btn-sm btn-cls-m">✕ Cancel</button>
        </form>
    </div>
</div>
</body>
</html>