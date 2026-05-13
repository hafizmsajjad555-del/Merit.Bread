<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d', strtotime('+1 day'));
$filter_city = isset($_GET['city']) ? mysqli_real_escape_string($conn, $_GET['city']) : '';

$cities_list = mysqli_query($conn, "SELECT DISTINCT city FROM users WHERE role='agent' ORDER BY city");

// Update status
if (isset($_POST['update_status'])) {
    $demand_date = $_POST['demand_date'];
    $city_upd    = mysqli_real_escape_string($conn, $_POST['city_upd']);
    $status      = $_POST['status'];
    mysqli_query($conn, "UPDATE warehouse_demand SET status='$status'
        WHERE demand_date='$demand_date' AND city='$city_upd'");
    $success = "✅ Status updated!";
}

// All items
$all_items_q = mysqli_query($conn,
    "SELECT * FROM items WHERE is_active=1 ORDER BY category, sort_order, item_name");
$all_items = [];
while($r = mysqli_fetch_assoc($all_items_q)) $all_items[] = $r;

// Warehouses with demands for this date
$warehouses_q = mysqli_query($conn,
    "SELECT DISTINCT city FROM warehouse_demand
     WHERE demand_date='$filter_date'
     " . ($filter_city ? "AND city='$filter_city'" : "") . "
     ORDER BY city");
$warehouses = [];
while($w = mysqli_fetch_assoc($warehouses_q)) $warehouses[] = $w['city'];

$cat_labels = ['first_voucher'=>'1st Voucher','second_voucher'=>'2nd Voucher','finish_good'=>'Finish Good'];
$cat_colors = ['first_voucher'=>'#0D47A1','second_voucher'=>'#4A148C','finish_good'=>'#1B5E20'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Demand Report — Merit Bread DMS</title>
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
        .header a.green { color:#90EE90; }
        .container { max-width:1400px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .filter-card { background:white; border-radius:12px; padding:20px 25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .filter-row { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .filter-row select,.filter-row input { padding:9px 13px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .btn-f { padding:9px 20px; background:#4CAF50; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold; }
        .btn-r { padding:9px 15px; background:#ff6b6b; color:white; border-radius:8px; text-decoration:none; font-size:13px; }
        .btn-p { padding:9px 15px; background:#1a1a2e; color:white; border:none; border-radius:8px; cursor:pointer; font-size:13px; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:16px; padding-bottom:10px; border-bottom:2px solid #F57F17; display:flex; justify-content:space-between; align-items:center; }
        /* Summary table */
        .sum-tbl { width:100%; border-collapse:collapse; font-size:12px; }
        .sum-tbl th { background:#1a1a2e; color:white; padding:10px 12px; text-align:center; white-space:nowrap; }
        .sum-tbl th.item-col { text-align:left; }
        .sum-tbl td { padding:8px 12px; border-bottom:1px solid #eee; text-align:center; }
        .sum-tbl td.item-col { text-align:left; font-weight:bold; }
        .sum-tbl tr:hover td { background:#f9f9f9; }
        .sum-tbl tfoot td { background:#fff8e1; font-weight:bold; border-top:2px solid #F57F17; }
        .qty-cell { font-size:13px; font-weight:bold; color:#1a1a2e; }
        .qty-zero { color:#ccc; }
        .total-cell { background:#fff8e1; color:#e65100; font-weight:bold; font-size:13px; }
        /* Status */
        .status-pending  { background:#fff3cd; color:#856404; }
        .status-received { background:#d4edda; color:#155724; }
        .status-partial  { background:#e3f2fd; color:#1565c0; }
        .status-badge { padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        /* Warehouse cards */
        .wh-header { background:#1a1a2e; color:white; padding:10px 15px; border-radius:8px 8px 0 0; display:flex; justify-content:space-between; align-items:center; }
        .wh-header h3 { font-size:14px; }
        select.status-select { padding:5px 10px; border-radius:6px; border:none; font-size:12px; cursor:pointer; }
        @media print {
            .header,.filter-card,.btn-f,.btn-r,.btn-p { display:none !important; }
            .card { box-shadow:none !important; }
            body { background:white !important; }
            @page { size:A4 landscape; margin:8mm; }
        }
    </style>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — Demand Report</h1>
    <div>
        <button onclick="window.print()" style="background:rgba(255,255,255,0.15);color:white;padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.3);cursor:pointer;font-size:12px;">🖨️ Print</button>
        <a href="admin_dashboard.php" class="green">← Admin Dashboard</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if(isset($success)) echo "<div class='alert-s'>$success</div>"; ?>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" class="filter-row">
            <div>
                <label style="font-size:11px;color:#666;font-weight:bold;display:block;margin-bottom:4px;text-transform:uppercase;">Date</label>
                <input type="date" name="date" value="<?= $filter_date ?>">
            </div>
            <div>
                <label style="font-size:11px;color:#666;font-weight:bold;display:block;margin-bottom:4px;text-transform:uppercase;">City</label>
                <select name="city">
                    <option value="">-- All Cities --</option>
                    <?php while($c = mysqli_fetch_assoc($cities_list)): ?>
                    <option value="<?= $c['city'] ?>" <?= $filter_city==$c['city']?'selected':'' ?>>
                        <?= $c['city'] ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div style="margin-top:18px;display:flex;gap:8px;">
                <button type="submit" class="btn-f">🔍 Filter</button>
                <a href="demand_report.php" class="btn-r">↺</a>
                <button type="button" onclick="window.print()" class="btn-p">🖨️</button>
            </div>
        </form>
    </div>

    <?php if(empty($warehouses)): ?>
    <div class="card" style="text-align:center;padding:40px;">
        <div style="font-size:50px;margin-bottom:15px;">📋</div>
        <h3 style="color:#888;"><?= date('d-m-Y',strtotime($filter_date)) ?> ki koi demand nahi mili!</h3>
        <p style="color:#aaa;margin-top:8px;">Warehouses ne abhi demand submit nahi ki.</p>
    </div>

    <?php else: ?>

    <!-- Combined Summary -->
    <div class="card">
        <h2>
            📊 Combined Summary — <?= date('d-m-Y',strtotime($filter_date)) ?>
            <span style="font-size:13px;color:#888;font-weight:normal;"><?= count($warehouses) ?> Warehouses</span>
        </h2>
        <div style="overflow-x:auto">
        <table class="sum-tbl">
            <thead>
            <tr>
                <th class="item-col" rowspan="2">#</th>
                <th class="item-col" rowspan="2">Item Name</th>
                <th rowspan="2">Category</th>
                <?php foreach($warehouses as $wh): ?>
                <th><?= $wh ?></th>
                <?php endforeach; ?>
                <th style="background:#F57F17;">TOTAL</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $grand_totals = array_fill_keys(array_merge($warehouses, ['total']), 0);
            $idx = 1;
            foreach($all_items as $item):
                // Check if any warehouse has demand for this item
                $has_demand = false;
                $item_demands = [];
                foreach($warehouses as $wh) {
                    $r = mysqli_fetch_assoc(mysqli_query($conn,
                        "SELECT COALESCE(SUM(quantity),0) as qty FROM warehouse_demand
                         WHERE item_id='{$item['id']}' AND city='$wh' AND demand_date='$filter_date'"));
                    $qty = intval($r['qty']);
                    $item_demands[$wh] = $qty;
                    if ($qty > 0) $has_demand = true;
                }
                if (!$has_demand) continue;

                $item_total = array_sum($item_demands);
                $clr = $cat_colors[$item['category']] ?? '#666';
                $cnm = $cat_labels[$item['category']] ?? '';
            ?>
            <tr>
                <td class="item-col"><?= $idx++ ?></td>
                <td class="item-col"><?= htmlspecialchars($item['item_name']) ?></td>
                <td>
                    <span style="background:<?= $clr ?>22;color:<?= $clr ?>;padding:2px 6px;border-radius:8px;font-size:10px;font-weight:bold;">
                        <?= $cnm ?>
                    </span>
                </td>
                <?php foreach($warehouses as $wh):
                    $qty = $item_demands[$wh];
                    $grand_totals[$wh] += $qty;
                ?>
                <td class="<?= $qty>0?'qty-cell':'qty-zero' ?>">
                    <?= $qty > 0 ? number_format($qty) : '—' ?>
                </td>
                <?php endforeach; ?>
                <td class="total-cell">
                    <?= number_format($item_total) ?>
                </td>
            </tr>
            <?php $grand_totals['total'] += $item_total; endforeach; ?>
            </tbody>
            <tfoot><tr>
                <td colspan="3"><strong>🔢 GRAND TOTAL</strong></td>
                <?php foreach($warehouses as $wh): ?>
                <td><strong><?= number_format($grand_totals[$wh]) ?></strong></td>
                <?php endforeach; ?>
                <td><strong style="color:#e65100;"><?= number_format($grand_totals['total']) ?></strong></td>
            </tr></tfoot>
        </table>
        </div>
    </div>

    <!-- Warehouse wise detail -->
    <?php foreach($warehouses as $wh):
        $wh_e = mysqli_real_escape_string($conn, $wh);
        $wh_demands = mysqli_query($conn,
            "SELECT wd.*, i.item_name, i.category FROM warehouse_demand wd
             JOIN items i ON wd.item_id=i.id
             WHERE wd.city='$wh_e' AND wd.demand_date='$filter_date'
             ORDER BY i.category, i.sort_order");

        $wh_status = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT status FROM warehouse_demand
             WHERE city='$wh_e' AND demand_date='$filter_date' LIMIT 1"));
        $cur_status = $wh_status ? $wh_status['status'] : 'pending';
        $total_qty  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT SUM(quantity) as total FROM warehouse_demand
             WHERE city='$wh_e' AND demand_date='$filter_date'"))['total'];
    ?>
    <div class="card" style="padding:0;overflow:hidden;">
        <div class="wh-header">
            <h3>🏭 <?= htmlspecialchars($wh) ?> &nbsp;|&nbsp;
                Total: <span style="color:#F57F17;"><?= number_format($total_qty) ?> pcs</span>
            </h3>
            <form method="POST" style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="demand_date" value="<?= $filter_date ?>">
                <input type="hidden" name="city_upd"    value="<?= $wh ?>">
                <select name="status" class="status-select">
                    <option value="pending"  <?= $cur_status=='pending' ?'selected':'' ?>>⏳ Pending</option>
                    <option value="received" <?= $cur_status=='received'?'selected':'' ?>>✅ Received</option>
                    <option value="partial"  <?= $cur_status=='partial' ?'selected':'' ?>>⚡ Partial</option>
                </select>
                <button type="submit" name="update_status"
                    style="background:#4CAF50;color:white;padding:5px 12px;border:none;border-radius:6px;cursor:pointer;font-size:12px;">
                    Update
                </button>
            </form>
        </div>
        <div style="padding:15px;overflow-x:auto;">
        <table class="sum-tbl">
            <thead><tr>
                <th class="item-col">#</th>
                <th class="item-col">Item Name</th>
                <th>Category</th>
                <th>Quantity</th>
            </tr></thead>
            <tbody>
            <?php $j=1; while($row = mysqli_fetch_assoc($wh_demands)):
                $clr = $cat_colors[$row['category']] ?? '#666';
                $cnm = $cat_labels[$row['category']] ?? '';
            ?>
            <tr>
                <td class="item-col"><?= $j++ ?></td>
                <td class="item-col"><?= htmlspecialchars($row['item_name']) ?></td>
                <td>
                    <span style="background:<?= $clr ?>22;color:<?= $clr ?>;padding:2px 6px;border-radius:8px;font-size:10px;font-weight:bold;">
                        <?= $cnm ?>
                    </span>
                </td>
                <td class="qty-cell"><?= number_format($row['quantity']) ?> pcs</td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>