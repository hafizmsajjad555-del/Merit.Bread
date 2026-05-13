<?php

include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$filter_city     = isset($_GET['city'])      ? mysqli_real_escape_string($conn, $_GET['city'])      : '';
$filter_salesman = isset($_GET['salesman'])  ? mysqli_real_escape_string($conn, $_GET['salesman'])  : '';
$filter_from     = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_to       = isset($_GET['date_to'])   ? $_GET['date_to']   : '';

$where = "WHERE 1=1";
if ($filter_city)     $where .= " AND d.city='$filter_city'";
if ($filter_salesman) $where .= " AND d.salesman_name LIKE '%$filter_salesman%'";
if ($filter_from)     $where .= " AND DATE(d.entry_date) >= '$filter_from'";
if ($filter_to)       $where .= " AND DATE(d.entry_date) <= '$filter_to'";

// Overall stats
$stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
     COALESCE(SUM(total_dispatch),0) as dispatch,
     COALESCE(SUM(total_expenses),0) as expenses,
     COALESCE(SUM(first_voucher),0) as fv_total,
     COALESCE(SUM(second_voucher),0) as sv_total
     FROM distribution_sheet"));

// Cities list
$cities_list = mysqli_query($conn, "SELECT DISTINCT city FROM users WHERE role='agent' ORDER BY city");

// Warehouse wise summary — sab columns
$warehouse_summary = mysqli_query($conn,
    "SELECT city,
     COUNT(*) as salesmen,
     COALESCE(SUM(first_voucher),0)          as total_fv,
     COALESCE(SUM(first_vou_rep),0)          as total_fv_rep,
     COALESCE(SUM(second_voucher),0)         as total_sv,
     COALESCE(SUM(second_vou_rep),0)         as total_sv_rep,
     COALESCE(SUM(total_dispatch),0)         as total_dispatch,
     COALESCE(SUM(net_sales),0)              as total_net_sales,
     COALESCE(SUM(total_net_sales),0)        as total_net,
     COALESCE(SUM(finish_goods),0)           as total_finish,
     COALESCE(SUM(total_expenses),0)         as total_exp,
     COALESCE(SUM(cash_received),0)          as total_cash_recv,
     COALESCE(SUM(cash_send),0)              as total_cash_send,
     COALESCE(SUM(iou),0)                    as total_iou,
     COALESCE(SUM(center_expenses),0)        as total_center_exp,
     COALESCE(SUM(closing_balance),0)        as total_closing,
     COALESCE(SUM(cash_in_hand),0)           as total_cash_hand,
     COALESCE(SUM(physical_stock),0)         as total_phys_stock,
     COALESCE(SUM(adjustment),0)             as total_adj
     FROM distribution_sheet
     GROUP BY city ORDER BY city");

// Detail entries
$data = mysqli_query($conn,
    "SELECT d.*, u.username FROM distribution_sheet d
     JOIN users u ON d.agent_id=u.id
     $where ORDER BY d.city, d.entry_date DESC");

// Filtered stats
$filtered_stats = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as total,
     COALESCE(SUM(first_voucher),0)   as fv_total,
     COALESCE(SUM(second_voucher),0)  as sv_total,
     COALESCE(SUM(total_dispatch),0)  as dispatch,
     COALESCE(SUM(total_net_sales),0) as net_sales,
     COALESCE(SUM(total_expenses),0)  as expenses
     FROM distribution_sheet d $where"));

// Rows array banao grand total ke liye
$rows = [];
while($row = mysqli_fetch_assoc($data)) $rows[] = $row;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-size: 20px; }
        .header a { color: #ff6b6b; text-decoration: none; margin-left:15px; }
        .header a.green { color: #90EE90; }
        .container { max-width: 1400px; margin: 25px auto; padding: 0 20px; }
        .stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 15px; margin-bottom: 25px; }
        .stat-card { background: white; padding: 18px 20px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); border-left: 5px solid #4CAF50; }
        .stat-card h3 { color: #888; font-size: 11px; margin-bottom: 8px; }
        .stat-card p  { font-size: 18px; font-weight: bold; color: #1a1a2e; }
        .tabs { display:flex; gap:10px; margin-bottom:20px; }
        .tab { padding:10px 25px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:14px; border:2px solid #eee; background:white; color:#666; }
        .tab.active { background:#1a1a2e; color:white; border-color:#1a1a2e; }
        .tab-content { display:none; }
        .tab-content.active { display:block; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .card h2 { color: #1a1a2e; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        /* Warehouse Cards */
        .warehouse-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
        .wh-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); border-top: 4px solid #4CAF50; }
        .wh-card h3 { font-size: 15px; color: #1a1a2e; margin-bottom: 15px; display:flex; justify-content:space-between; align-items:center; }
        .wh-badge { background:#e3f2fd; color:#1565c0; padding:3px 10px; border-radius:20px; font-size:11px; }
        .wh-row { display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px solid #f5f5f5; font-size:12px; }
        .wh-row:last-child { border:none; font-weight:bold; font-size:13px; }
        .wh-row.section { background:#f8f9fa; padding:4px 8px; margin:5px -8px; font-weight:bold; color:#666; font-size:11px; border:none; }
        .wh-label { color:#666; }
        .wh-value { font-weight:600; }
        .green  { color:#2e7d32; }
        .blue   { color:#1565c0; }
        .orange { color:#e65100; }
        .purple { color:#6a1b9a; }
        .red    { color:#c62828; }
        /* Filters */
        .filters { display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:center; }
        .filters select, .filters input { padding: 9px 13px; border: 2px solid #eee; border-radius: 8px; font-size: 13px; }
        .filters button { padding: 9px 20px; background: #4CAF50; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        .filters a.reset { padding: 9px 20px; background: #ff6b6b; color: white; border-radius: 8px; text-decoration: none; font-size: 13px; }
        /* Filter summary */
        .filter-stats { display:grid; grid-template-columns:repeat(6,1fr); gap:10px; margin-bottom:20px; }
        .fstat { background:#f8f9fa; padding:12px; border-radius:8px; text-align:center; border-left:3px solid #4CAF50; }
        .fstat h4 { font-size:10px; color:#888; margin-bottom:5px; }
        .fstat p  { font-size:14px; font-weight:bold; color:#1a1a2e; }
        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width:100%; border-collapse:collapse; font-size:11px; min-width:2200px; }
        th { background:#1a1a2e; color:white; padding:9px 7px; text-align:left; white-space:nowrap; }
        td { padding:7px; border-bottom:1px solid #eee; white-space:nowrap; }
        tr:hover td { background:#f9f9f9; }
        tfoot td { background:#e8f5e9; color:#1a1a2e; font-weight:bold; border-top:2px solid #4CAF50; }
        .city-badge { background:#e3f2fd; color:#1565c0; padding:2px 8px; border-radius:20px; font-size:10px; }
        .auto-col  { background:#e8f5e9; color:#2e7d32; font-weight:bold; }
        .cash-col  { background:#fff3e0; color:#e65100; }
        .btn-edit  { background:#FF9800; color:white; padding:4px 10px; border:none; border-radius:5px; cursor:pointer; font-size:11px; text-decoration:none; display:inline-block; }
    </style>
    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('content-' + tab).classList.add('active');
    }
    window.onload = function() {
        <?php echo ($filter_city || $filter_salesman || $filter_from) ? "showTab('detail');" : "showTab('summary');"; ?>
    }
    </script>
</head>
<body>
<div class="header">
    <h1>📊 Admin Dashboard — Distribution System</h1>
    <div>
<a href="admin_users.php" class="green">👥 Users</a>
        <a href="items_management.php" class="green">🛒 Items Management</a>
<a href="item_report.php" class="green">📦 Item Report</a>
<a href="demand_report.php" class="green">📋 Demand Report</a>
<a href="salesmen_management.php" class="green">👥 Salesmen</a>
<button onclick="window.print()" style="background:rgba(255,255,255,0.15);color:white;padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.3);cursor:pointer;font-size:12px;margin-right:5px;">🖨️ Print</button>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">

    <!-- Overall Stats -->
    <div class="stats">
        <div class="stat-card">
            <h3>📋 TOTAL ENTRIES</h3>
            <p><?= number_format($stats['total']) ?></p>
        </div>
        <div class="stat-card" style="border-color:#4CAF50">
            <h3>1️⃣ TOTAL 1ST VOUCHER</h3>
            <p>Rs.<?= number_format($stats['fv_total'],0) ?></p>
        </div>
        <div class="stat-card" style="border-color:#9C27B0">
            <h3>2️⃣ TOTAL 2ND VOUCHER</h3>
            <p>Rs.<?= number_format($stats['sv_total'],0) ?></p>
        </div>
        <div class="stat-card" style="border-color:#2196F3">
            <h3>📦 TOTAL DISPATCH</h3>
            <p>Rs.<?= number_format($stats['dispatch'],0) ?></p>
        </div>
        <div class="stat-card" style="border-color:#FF9800">
            <h3>💸 TOTAL EXPENSES</h3>
            <p>Rs.<?= number_format($stats['expenses'],0) ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active" id="tab-summary" onclick="showTab('summary')">🏭 Warehouse Summary</div>
        <div class="tab"        id="tab-detail"  onclick="showTab('detail')">📋 Detail Entries</div>
    </div>

    <!-- TAB 1: Warehouse Summary -->
    <div class="tab-content active" id="content-summary">
        <div class="warehouse-grid">
        <?php
        $border_colors = ['#4CAF50','#2196F3','#FF9800','#9C27B0','#F44336','#00BCD4','#795548','#607D8B'];
        $ci = 0;
        while($wh = mysqli_fetch_assoc($warehouse_summary)):
            $bc = $border_colors[$ci % count($border_colors)]; $ci++;
        ?>
        <div class="wh-card" style="border-top-color:<?= $bc ?>">
            <h3>
                🏭 <?= $wh['city'] ?>
                <span class="wh-badge"><?= $wh['salesmen'] ?> Entries</span>
            </h3>

            <div class="wh-row section"><span>📦 VOUCHER</span></div>
            <div class="wh-row">
                <span class="wh-label">1st Voucher</span>
                <span class="wh-value blue">Rs.<?= number_format($wh['total_fv'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">1st Replace</span>
                <span class="wh-value red">Rs.<?= number_format($wh['total_fv_rep'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">2nd Voucher</span>
                <span class="wh-value blue">Rs.<?= number_format($wh['total_sv'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">2nd Replace</span>
                <span class="wh-value red">Rs.<?= number_format($wh['total_sv_rep'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Net Sales</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_net_sales'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Total Net Sales</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_net'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Total Dispatch</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_dispatch'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Finish Goods</span>
                <span class="wh-value purple">Rs.<?= number_format($wh['total_finish'],0) ?></span>
            </div>

            <div class="wh-row section"><span>💰 EXPENSES</span></div>
            <div class="wh-row">
                <span class="wh-label">Total Expenses</span>
                <span class="wh-value orange">Rs.<?= number_format($wh['total_exp'],0) ?></span>
            </div>

            <div class="wh-row section"><span>💵 CASH & STOCK</span></div>
            <div class="wh-row">
                <span class="wh-label">Cash Received</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_cash_recv'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Cash Send</span>
                <span class="wh-value orange">Rs.<?= number_format($wh['total_cash_send'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">IOU</span>
                <span class="wh-value red">Rs.<?= number_format($wh['total_iou'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Center Expenses</span>
                <span class="wh-value orange">Rs.<?= number_format($wh['total_center_exp'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Adjustment</span>
                <span class="wh-value purple">Rs.<?= number_format($wh['total_adj'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Closing Balance</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_closing'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Cash In Hand</span>
                <span class="wh-value green">Rs.<?= number_format($wh['total_cash_hand'],0) ?></span>
            </div>
            <div class="wh-row">
                <span class="wh-label">Physical Stock</span>
                <span class="wh-value blue">Rs.<?= number_format($wh['total_phys_stock'],0) ?></span>
            </div>

            <!-- Edit entries link -->
            <div style="margin-top:12px; text-align:center;">
                <a href="?city=<?= urlencode($wh['city']) ?>" 
                   onclick="showTab('detail')"
                   class="btn-edit" style="background:#1a1a2e; padding:7px 20px; border-radius:8px; font-size:12px;">
                   📋 Entries Dekhein
                </a>
            </div>
        </div>
        <?php endwhile; ?>
        </div>
    </div>

    <!-- TAB 2: Detail Entries -->
    <div class="tab-content" id="content-detail">
        <div class="card">
            <h2>📋 Detail Entries</h2>

            <!-- Filters -->
            <form method="GET" class="filters">
                <select name="city">
                    <option value="">-- All Cities --</option>
                    <?php mysqli_data_seek($cities_list, 0);
                    while($c = mysqli_fetch_assoc($cities_list)): ?>
                    <option value="<?= $c['city'] ?>" <?= $filter_city==$c['city']?'selected':'' ?>>
                        <?= $c['city'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>
                <input type="text"  name="salesman"  placeholder="🔍 Salesman naam..." value="<?= $filter_salesman ?>">
                <input type="date"  name="date_from" value="<?= $filter_from ?>" title="Date From">
                <input type="date"  name="date_to"   value="<?= $filter_to ?>"   title="Date To">
                <button type="submit">🔍 Filter</button>
                <a href="admin_dashboard.php" class="reset">↺ Reset</a>
            </form>
<a href="export_excel.php?type=distribution" 
   style="background:#217346;color:white;padding:7px 15px;border-radius:8px;text-decoration:none;font-size:12px;margin-right:5px;">
   📊 Excel
</a>

            <!-- Filtered Summary -->
            <?php if($filter_city || $filter_salesman || $filter_from || $filter_to): ?>
            <div class="filter-stats">
                <div class="fstat"><h4>ENTRIES</h4><p><?= $filtered_stats['total'] ?></p></div>
                <div class="fstat" style="border-color:#4CAF50"><h4>1ST VOUCHER</h4><p>Rs.<?= number_format($filtered_stats['fv_total'],0) ?></p></div>
                <div class="fstat" style="border-color:#9C27B0"><h4>2ND VOUCHER</h4><p>Rs.<?= number_format($filtered_stats['sv_total'],0) ?></p></div>
                <div class="fstat" style="border-color:#2196F3"><h4>DISPATCH</h4><p>Rs.<?= number_format($filtered_stats['dispatch'],0) ?></p></div>
                <div class="fstat" style="border-color:#00BCD4"><h4>NET SALES</h4><p>Rs.<?= number_format($filtered_stats['net_sales'],0) ?></p></div>
                <div class="fstat" style="border-color:#FF9800"><h4>EXPENSES</h4><p>Rs.<?= number_format($filtered_stats['expenses'],0) ?></p></div>
            </div>
            <?php endif; ?>

            <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Edit</th><th>#</th><th>City</th><th>Salesman</th><th>Father</th><th>Sector</th>
                    <th>1st Voucher</th><th>1st Rep</th><th>Net Sales🟢</th>
                    <th>2nd Voucher</th><th>2nd Rep</th><th>Total Net🟢</th>
                    <th>Dispatch🟢</th><th>Fuel</th><th>Fuel%🟢</th>
                    <th>TD</th><th>MC</th><th>Mkt Bill</th><th>Free Samp</th>
                    <th>Total MC🟢</th><th>MC%🟢</th><th>Tax</th>
                    <th>Van Paid</th><th>Van Payable</th>
                    <th>Inc Base</th><th>Disp Inc</th>
                    <th>Commission🟢</th><th>Dealer</th><th>Finish Goods</th>
                    <th>Total Exp🟢</th><th>Exp%🟢</th>
                    <th>Cash Recv</th><th>Cash Send</th><th>IOU</th>
                    <th>Center Exp</th><th>Adj</th><th>Closing Bal</th>
                    <th>Cash Hand</th><th>Phys Stock</th><th>Date</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $grand = array_fill_keys([
                    'first_voucher','first_vou_rep','net_sales',
                    'second_voucher','second_vou_rep','total_net_sales',
                    'total_dispatch','fuel','td','mc','market_bill',
                    'free_sampling','total_mc','total_tax','van_rent_paid',
                    'van_rent_payable','incentive_exp_base','dispatch_bach_incentive',
                    'commission','dealer','finish_goods','total_expenses',
                    'cash_received','cash_send','iou','center_expenses',
                    'adjustment','closing_balance','cash_in_hand','physical_stock'
                ], 0);

                $i = 1;
                foreach($rows as $row):
                    foreach(array_keys($grand) as $k) {
                        if(isset($row[$k])) $grand[$k] += $row[$k];
                    }
                ?>
                <tr>
                    <td>
                        <a href="<?= 'http://localhost/distribution/agent_entry.php?edit=' . $row['id'] ?>" 
                           class="btn-edit" target="_blank">✏️ Edit</a>
                    </td>
                    <td><?= $i++ ?></td>
                    <td><span class="city-badge"><?= $row['city'] ?></span></td>
                    <td><strong><?= $row['salesman_name'] ?></strong></td>
                    <td><?= $row['father_name'] ?></td>
                    <td><?= $row['sector_name'] ?></td>
                    <td>Rs.<?= number_format($row['first_voucher'],2) ?></td>
                    <td>Rs.<?= number_format($row['first_vou_rep'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['net_sales'],2) ?></td>
                    <td>Rs.<?= number_format($row['second_voucher'],2) ?></td>
                    <td>Rs.<?= number_format($row['second_vou_rep'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['total_net_sales'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['total_dispatch'],2) ?></td>
                    <td>Rs.<?= number_format($row['fuel'],2) ?></td>
                    <td class="auto-col"><?= number_format($row['fuel_percentage'],2) ?>%</td>
                    <td>Rs.<?= number_format($row['td'],2) ?></td>
                    <td>Rs.<?= number_format($row['mc'],2) ?></td>
                    <td>Rs.<?= number_format($row['market_bill'],2) ?></td>
                    <td>Rs.<?= number_format($row['free_sampling'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['total_mc'],2) ?></td>
                    <td class="auto-col"><?= number_format($row['mc_percentage'],2) ?>%</td>
                    <td>Rs.<?= number_format($row['total_tax'],2) ?></td>
                    <td>Rs.<?= number_format($row['van_rent_paid'],2) ?></td>
                    <td>Rs.<?= number_format($row['van_rent_payable'],2) ?></td>
                    <td>Rs.<?= number_format($row['incentive_exp_base'],2) ?></td>
                    <td>Rs.<?= number_format($row['dispatch_bach_incentive'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['commission'],2) ?></td>
                    <td>Rs.<?= number_format($row['dealer'],2) ?></td>
                    <td>Rs.<?= number_format($row['finish_goods'],2) ?></td>
                    <td class="auto-col">Rs.<?= number_format($row['total_expenses'],2) ?></td>
                    <td class="auto-col"><?= number_format($row['exp_percentage'],2) ?>%</td>
                    <td class="cash-col">Rs.<?= number_format($row['cash_received'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['cash_send'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['iou'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['center_expenses'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['adjustment'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['closing_balance'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['cash_in_hand'],2) ?></td>
                    <td class="cash-col">Rs.<?= number_format($row['physical_stock'],2) ?></td>
                    <td><?= date('d-m-Y', strtotime($row['entry_date'])) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td colspan="6">🔢 GRAND TOTAL</td>
                    <td>Rs.<?= number_format($grand['first_voucher'],2) ?></td>
                    <td>Rs.<?= number_format($grand['first_vou_rep'],2) ?></td>
                    <td>Rs.<?= number_format($grand['net_sales'],2) ?></td>
                    <td>Rs.<?= number_format($grand['second_voucher'],2) ?></td>
                    <td>Rs.<?= number_format($grand['second_vou_rep'],2) ?></td>
                    <td>Rs.<?= number_format($grand['total_net_sales'],2) ?></td>
                    <td>Rs.<?= number_format($grand['total_dispatch'],2) ?></td>
                    <td>Rs.<?= number_format($grand['fuel'],2) ?></td>
                    <td>—</td>
                    <td>Rs.<?= number_format($grand['td'],2) ?></td>
                    <td>Rs.<?= number_format($grand['mc'],2) ?></td>
                    <td>Rs.<?= number_format($grand['market_bill'],2) ?></td>
                    <td>Rs.<?= number_format($grand['free_sampling'],2) ?></td>
                    <td>Rs.<?= number_format($grand['total_mc'],2) ?></td>
                    <td>—</td>
                    <td>Rs.<?= number_format($grand['total_tax'],2) ?></td>
                    <td>Rs.<?= number_format($grand['van_rent_paid'],2) ?></td>
                    <td>Rs.<?= number_format($grand['van_rent_payable'],2) ?></td>
                    <td>Rs.<?= number_format($grand['incentive_exp_base'],2) ?></td>
                    <td>Rs.<?= number_format($grand['dispatch_bach_incentive'],2) ?></td>
                    <td>Rs.<?= number_format($grand['commission'],2) ?></td>
                    <td>Rs.<?= number_format($grand['dealer'],2) ?></td>
                    <td>Rs.<?= number_format($grand['finish_goods'],2) ?></td>
                    <td>Rs.<?= number_format($grand['total_expenses'],2) ?></td>
                    <td>—</td>
                    <td>Rs.<?= number_format($grand['cash_received'],2) ?></td>
                    <td>Rs.<?= number_format($grand['cash_send'],2) ?></td>
                    <td>Rs.<?= number_format($grand['iou'],2) ?></td>
                    <td>Rs.<?= number_format($grand['center_expenses'],2) ?></td>
                    <td>Rs.<?= number_format($grand['adjustment'],2) ?></td>
                    <td>Rs.<?= number_format($grand['closing_balance'],2) ?></td>
                    <td>Rs.<?= number_format($grand['cash_in_hand'],2) ?></td>
                    <td>Rs.<?= number_format($grand['physical_stock'],2) ?></td>
                    <td>—</td>
                </tr>
                </tfoot>
            </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>