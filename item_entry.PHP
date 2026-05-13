<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}
$agent_id = $_SESSION['user_id'];
$city     = $_SESSION['city'];
$success  = $error = "";

// Salesmen list
$salesmen_q = mysqli_query($conn,
    "SELECT * FROM salesmen WHERE city='$city' AND is_active=1 ORDER BY salesman_name");
$salesmen_arr = [];
while($s = mysqli_fetch_assoc($salesmen_q)) $salesmen_arr[] = $s;

// Favourites load
$favs_q = mysqli_query($conn,
    "SELECT item_id FROM favourite_items WHERE user_id='$agent_id'");
$favs = [];
while($f = mysqli_fetch_assoc($favs_q)) $favs[] = $f['item_id'];

// Items by category — favourites pehle
function getItems($conn, $category, $agent_id) {
    $q = mysqli_query($conn,
        "SELECT i.*,
         CASE WHEN fi.item_id IS NOT NULL THEN 1 ELSE 0 END as is_fav
         FROM items i
         LEFT JOIN favourite_items fi ON fi.item_id=i.id AND fi.user_id='$agent_id'
         WHERE i.category='$category' AND i.is_active=1
         ORDER BY is_fav DESC, i.sort_order, i.item_name");
    $arr = [];
    while($r = mysqli_fetch_assoc($q)) $arr[] = $r;
    return $arr;
}

$items_fv = getItems($conn, 'first_voucher',  $agent_id);
$items_sv = getItems($conn, 'second_voucher', $agent_id);
$items_fg = getItems($conn, 'finish_good',    $agent_id);

// All items for receiving & demand — favourites pehle
$all_items_q = mysqli_query($conn,
    "SELECT i.*,
     CASE WHEN fi.item_id IS NOT NULL THEN 1 ELSE 0 END as is_fav
     FROM items i
     LEFT JOIN favourite_items fi ON fi.item_id=i.id AND fi.user_id='$agent_id'
     WHERE i.is_active=1
     ORDER BY i.category, is_fav DESC, i.sort_order, i.item_name");
$all_items = [];
while($r = mysqli_fetch_assoc($all_items_q)) $all_items[] = $r;

// Save dispatch
function saveDispatch($conn, $table, $agent_id, $city, $salesman, $date, $item_ids, $qtys) {
    $saved = 0;
    foreach ($item_ids as $k => $item_id) {
        $qty = intval($qtys[$k]);
        if ($qty <= 0) continue;
        $item_id = intval($item_id);
        $rate_r  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT rate FROM items WHERE id='$item_id'"));
        $rate   = $rate_r['rate'];
        $amount = $qty * $rate;
        if ($table == 'finish_good') {
            mysqli_query($conn,
                "INSERT INTO finish_good
                 (agent_id,city,salesman_name,item_id,rate,quantity,amount,finish_date)
                 VALUES ('$agent_id','$city','$salesman','$item_id','$rate','$qty','$amount','$date')");
        } else {
            mysqli_query($conn,
                "INSERT INTO $table
                 (agent_id,city,salesman_name,item_id,rate,quantity,amount,dispatch_date)
                 VALUES ('$agent_id','$city','$salesman','$item_id','$rate','$qty','$amount','$date')");
        }
        $saved++;
    }
    return $saved;
}

// Save replace
function saveReplace($conn, $table, $agent_id, $city, $salesman, $date, $item_ids, $qtys1, $qtys2) {
    $saved = 0;
    foreach ($item_ids as $k => $item_id) {
        $q1 = intval($qtys1[$k]);
        $q2 = intval($qtys2[$k]);
        if ($q1 + $q2 <= 0) continue;
        $item_id = intval($item_id);
        $rate_r  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT rate FROM items WHERE id='$item_id'"));
        $rate   = $rate_r['rate'];
        $total  = $q1 + $q2;
        $amount = $total * $rate;
        mysqli_query($conn,
            "INSERT INTO $table
             (agent_id,city,salesman_name,item_id,rate,qty_1st,qty_2nd,total_qty,amount,replace_date)
             VALUES ('$agent_id','$city','$salesman','$item_id','$rate','$q1','$q2','$total','$amount','$date')");
        $saved++;
    }
    return $saved;
}

// POST handlers
if (isset($_POST['save_receiving'])) {
    $date = $_POST['receiving_date'];
    $saved = 0;
    foreach ($_POST['item_ids'] as $k => $item_id) {
        $qty = intval($_POST['quantities'][$k]);
        if ($qty <= 0) continue;
        $item_id = intval($item_id);
        $rate_r  = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT rate FROM items WHERE id='$item_id'"));
        $rate  = $rate_r['rate'];
        $total = $qty * $rate;
        mysqli_query($conn,
            "INSERT INTO factory_receiving
             (agent_id,city,item_id,quantity,rate,total_amount,receiving_date)
             VALUES ('$agent_id','$city','$item_id','$qty','$rate','$total','$date')");
        $saved++;
    }
    $success = $saved > 0 ? "✅ $saved items ki receiving save ho gayi!" : "❌ Koi qty enter nahi ki!";
}
if (isset($_POST['save_dispatch_first'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveDispatch($conn,'dispatch_first',$agent_id,$city,$s,
        $_POST['dispatch_date'],$_POST['item_ids'],$_POST['quantities']);
    $success = "✅ 1st Voucher Dispatch saved ($n items)!";
}
if (isset($_POST['save_replace_first'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveReplace($conn,'replace_first',$agent_id,$city,$s,
        $_POST['replace_date'],$_POST['item_ids'],$_POST['qty_1st'],$_POST['qty_2nd']);
    $success = "✅ 1st Voucher Replace saved ($n items)!";
}
if (isset($_POST['save_dispatch_second'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveDispatch($conn,'dispatch_second',$agent_id,$city,$s,
        $_POST['dispatch_date'],$_POST['item_ids'],$_POST['quantities']);
    $success = "✅ 2nd Voucher Dispatch saved ($n items)!";
}
if (isset($_POST['save_replace_second'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveReplace($conn,'replace_second',$agent_id,$city,$s,
        $_POST['replace_date'],$_POST['item_ids'],$_POST['qty_1st'],$_POST['qty_2nd']);
    $success = "✅ 2nd Voucher Replace saved ($n items)!";
}
if (isset($_POST['save_finish_good'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveDispatch($conn,'finish_good',$agent_id,$city,$s,
        $_POST['dispatch_date'],$_POST['item_ids'],$_POST['quantities']);
    $success = "✅ Finish Good saved ($n items)!";
}
if (isset($_POST['save_replace_finish'])) {
    $s = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $n = saveReplace($conn,'replace_finish',$agent_id,$city,$s,
        $_POST['replace_date'],$_POST['item_ids'],$_POST['qty_1st'],$_POST['qty_2nd']);
    $success = "✅ Finish Good Replace saved ($n items)!";
}
if (isset($_POST['save_demand'])) {
    $date = $_POST['demand_date'];
    $saved = 0;
    mysqli_query($conn,
        "DELETE FROM warehouse_demand
         WHERE city='$city' AND demand_date='$date' AND agent_id='$agent_id'");
    foreach ($_POST['item_ids'] as $k => $item_id) {
        $qty = intval($_POST['quantities'][$k]);
        if ($qty <= 0) continue;
        $item_id = intval($item_id);
        mysqli_query($conn,
            "INSERT INTO warehouse_demand (agent_id,city,item_id,quantity,demand_date)
             VALUES ('$agent_id','$city','$item_id','$qty','$date')");
        $saved++;
    }
    $success = $saved > 0 ? "✅ $saved items ki demand save ho gayi!" : "❌ Koi qty enter nahi ki!";
}

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'receiving';

// Previous demand
$prev_demand_date = isset($_GET['demand_date']) ? $_GET['demand_date'] : date('Y-m-d');
$prev_demand_q    = mysqli_query($conn,
    "SELECT item_id, quantity FROM warehouse_demand
     WHERE city='$city' AND agent_id='$agent_id' AND demand_date='$prev_demand_date'");
$prev_demand = [];
while($r = mysqli_fetch_assoc($prev_demand_q)) $prev_demand[$r['item_id']] = $r['quantity'];

// Salesman dropdown HTML
function salDropdown($salesmen_arr) {
    if (!empty($salesmen_arr)) {
        $html = "<select name='salesman_name' required
            style='width:100%;padding:10px 12px;border:2px solid #eee;border-radius:8px;font-size:13px;color:#1a1a2e;background:white;'>
            <option value=''>-- Select Salesman --</option>";
        foreach($salesmen_arr as $s) {
            $html .= "<option value='".htmlspecialchars($s['salesman_name'])."'>".
                      htmlspecialchars($s['salesman_name'])."</option>";
        }
        $html .= "</select>";
    } else {
        $html = "<input type='text' name='salesman_name' required
            placeholder='Enter Salesman Name'
            style='width:100%;padding:10px 12px;border:2px solid #eee;border-radius:8px;font-size:13px;'>
            <small style='color:#ff6b6b;margin-top:3px;display:block;'>
            ⚠️ <a href='salesmen_management.php'>Salesmen Add Karein</a></small>";
    }
    return $html;
}

// Dispatch table
function makeDispatchTable($items, $prefix, $btn_name, $btn_label, $btn_class, $salesmen_arr, $date_field='dispatch_date') {
    if (empty($items)) {
        echo "<div style='text-align:center;padding:30px;color:#aaa;'>⚠️ Koi item nahi!</div>";
        return;
    }
    echo "<div class='top-fields'>
        <div class='field'><label>Salesman Name *</label>".salDropdown($salesmen_arr)."</div>
        <div class='field'><label>Date *</label>
            <input type='date' name='$date_field' required value='".date('Y-m-d')."'></div>
    </div>
    <div style='overflow-x:auto'>
    <table class='items-tbl'>
        <thead><tr>
            <th width='4%'>#</th>
            <th width='3%'>⭐</th>
            <th>Item Name</th>
            <th width='15%'>Rate (Rs.)</th>
            <th width='15%'>Quantity</th>
            <th width='15%'>Amount 🟢</th>
        </tr></thead><tbody>";

    foreach ($items as $i => $item) {
        $qid    = "q_{$prefix}_{$i}";
        $aid    = "a_{$prefix}_{$i}";
        $is_fav = $item['is_fav'] ?? 0;
        $fav_style = $is_fav
            ? "background:#fffde7;"
            : "";

        echo "<tr style='$fav_style'>
            <td style='color:#888;'>".($i+1)."</td>
            <td>
                <button type='button'
                    onclick='toggleFav(this,{$item['id']})'
                    style='background:none;border:none;font-size:18px;cursor:pointer;padding:0;line-height:1;'>
                    ".($is_fav ? '⭐' : '☆')."
                </button>
            </td>
            <td><strong>".htmlspecialchars($item['item_name'])."</strong>
                <input type='hidden' name='item_ids[]' value='".$item['id']."'>
            </td>
            <td style='color:#1565c0;font-weight:bold;'>Rs.".number_format($item['rate'],2)."</td>
            <td><input type='number' id='$qid' name='quantities[]' value='0' min='0'
                oninput=\"calcAmt('$qid',{$item['rate']},'$aid','$prefix')\"></td>
            <td><input type='number' id='$aid' class='auto' readonly value='0.00'></td>
        </tr>";
    }

    echo "</tbody>
        <tfoot><tr class='tfoot-row'>
            <td colspan='5'><strong>Grand Total</strong></td>
            <td><strong id='gt_$prefix'>Rs. 0.00</strong></td>
        </tr></tfoot>
    </table></div>
    <button type='submit' name='$btn_name' class='btn-submit $btn_class'>💾 $btn_label</button>";
}

// Replace table
function makeReplaceTable($items, $prefix, $btn_name, $btn_label, $btn_class, $salesmen_arr) {
    if (empty($items)) {
        echo "<div style='text-align:center;padding:30px;color:#aaa;'>⚠️ Koi item nahi!</div>";
        return;
    }
    echo "<div class='top-fields'>
        <div class='field'><label>Salesman Name *</label>".salDropdown($salesmen_arr)."</div>
        <div class='field'><label>Date *</label>
            <input type='date' name='replace_date' required value='".date('Y-m-d')."'></div>
    </div>
    <div style='overflow-x:auto'>
    <table class='items-tbl'>
        <thead><tr>
            <th width='4%'>#</th>
            <th width='3%'>⭐</th>
            <th>Item Name</th>
            <th width='12%'>Rate</th>
            <th width='14%'>1st Replace Qty</th>
            <th width='14%'>2nd Replace Qty</th>
            <th width='14%'>Amount 🟢</th>
        </tr></thead><tbody>";

    foreach ($items as $i => $item) {
        $q1id   = "r1_{$prefix}_{$i}";
        $q2id   = "r2_{$prefix}_{$i}";
        $aid    = "ra_{$prefix}_{$i}";
        $is_fav = $item['is_fav'] ?? 0;
        $fav_style = $is_fav ? "background:#fffde7;" : "";

        echo "<tr style='$fav_style'>
            <td style='color:#888;'>".($i+1)."</td>
            <td>
                <button type='button'
                    onclick='toggleFav(this,{$item['id']})'
                    style='background:none;border:none;font-size:18px;cursor:pointer;padding:0;line-height:1;'>
                    ".($is_fav ? '⭐' : '☆')."
                </button>
            </td>
            <td><strong>".htmlspecialchars($item['item_name'])."</strong>
                <input type='hidden' name='item_ids[]' value='".$item['id']."'>
            </td>
            <td style='color:#1565c0;font-weight:bold;'>Rs.".number_format($item['rate'],2)."</td>
            <td><input type='number' id='$q1id' name='qty_1st[]' value='0' min='0'
                oninput=\"calcReplace('$q1id','$q2id',{$item['rate']},'$aid','$prefix')\"></td>
            <td><input type='number' id='$q2id' name='qty_2nd[]' value='0' min='0'
                oninput=\"calcReplace('$q1id','$q2id',{$item['rate']},'$aid','$prefix')\"></td>
            <td><input type='number' id='$aid' class='auto' readonly value='0.00'></td>
        </tr>";
    }

    echo "</tbody>
        <tfoot><tr class='tfoot-row'>
            <td colspan='6'><strong>Grand Total</strong></td>
            <td><strong id='gt_$prefix'>Rs. 0.00</strong></td>
        </tr></tfoot>
    </table></div>
    <button type='submit' name='$btn_name' class='btn-submit $btn_class'>💾 $btn_label</button>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Item Wise Entry — Merit Bread</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f0f2f5; }
        .header {
            background:linear-gradient(135deg,#1a1a2e,#16213e);
            color:white; padding:15px 30px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .header h1 { font-size:18px; }
        .header a { color:#ff6b6b; text-decoration:none; margin-left:10px; font-size:12px; }
        .header a.green  { color:#90EE90; }
        .header a.yellow { color:#FFD700; }
        .container { max-width:1100px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .alert-e { background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { margin-bottom:15px; font-size:16px; padding-bottom:10px; border-bottom:3px solid; }
        /* Tabs */
        .tabs { display:flex; gap:8px; margin-bottom:20px; flex-wrap:wrap; }
        .tab { padding:10px 18px; border-radius:8px; font-weight:bold; font-size:12px; text-decoration:none; color:white; opacity:0.6; transition:opacity 0.2s; white-space:nowrap; }
        .tab:hover { opacity:0.85; }
        .tab.active { opacity:1; box-shadow:0 3px 10px rgba(0,0,0,0.2); }
        /* Fav legend */
        .fav-legend { display:flex; align-items:center; gap:8px; margin-bottom:12px; font-size:12px; color:#888; }
        .fav-legend span { background:#fffde7; border:1px solid #FFE082; padding:3px 10px; border-radius:8px; }
        /* Form */
        .top-fields { display:grid; grid-template-columns:repeat(2,1fr); gap:15px; margin-bottom:20px; }
        .field label { display:block; font-size:11px; color:#666; margin-bottom:5px; font-weight:bold; text-transform:uppercase; }
        .field input,.field select { width:100%; padding:10px 12px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .field input:focus,.field select:focus { border-color:#4CAF50; outline:none; }
        /* Items table */
        .items-tbl { width:100%; border-collapse:collapse; }
        .items-tbl th { background:#1a1a2e; color:white; padding:10px 12px; text-align:left; font-size:12px; }
        .items-tbl td { padding:8px 12px; border-bottom:1px solid #f0f0f0; font-size:13px; vertical-align:middle; }
        .items-tbl tr:hover td { background:#f0f7ff !important; }
        .items-tbl input[type='number'] { width:100%; padding:7px 10px; border:2px solid #eee; border-radius:6px; font-size:13px; text-align:right; }
        .items-tbl input[type='number']:focus { border-color:#4CAF50; outline:none; }
        .items-tbl input.auto { background:#e8f5e9; color:#2e7d32; font-weight:bold; border-color:#c8e6c9; }
        .tfoot-row td { background:#e8f5e9; font-weight:bold; border-top:2px solid #4CAF50; padding:10px 12px; }
        /* Buttons */
        .btn-submit { padding:13px; border:none; border-radius:8px; font-size:14px; cursor:pointer; font-weight:bold; width:100%; color:white; margin-top:12px; }
        .btn-recv   { background:linear-gradient(135deg,#546e7a,#263238); }
        .btn-d1     { background:linear-gradient(135deg,#1976D2,#0D47A1); }
        .btn-r1     { background:linear-gradient(135deg,#F57C00,#E65100); }
        .btn-d2     { background:linear-gradient(135deg,#7B1FA2,#4A148C); }
        .btn-r2     { background:linear-gradient(135deg,#D32F2F,#B71C1C); }
        .btn-fg     { background:linear-gradient(135deg,#388E3C,#1B5E20); }
        .btn-rfg    { background:linear-gradient(135deg,#00897B,#004D40); }
        .btn-demand { background:linear-gradient(135deg,#F57F17,#E65100); }
        /* Demand */
        .demand-info { background:#fff8e1; border:1px solid #FFE082; border-radius:8px; padding:12px 15px; margin-bottom:15px; font-size:13px; color:#856404; }
        .date-filter { display:flex; gap:10px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
        .date-filter input { padding:9px 13px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .date-filter button { padding:9px 18px; border-radius:8px; font-size:13px; cursor:pointer; font-weight:bold; border:none; color:white; background:#2196F3; }
        /* Category divider */
        .cat-div { background:#f0f4ff; padding:6px 12px; font-weight:bold; font-size:11px; color:#1565c0; border-left:4px solid #1565c0; margin:8px 0 4px; border-radius:0 6px 6px 0; text-transform:uppercase; }
        .cat-div.sv { border-color:#6a1b9a; color:#6a1b9a; background:#f8f0ff; }
        .cat-div.fg { border-color:#2e7d32; color:#2e7d32; background:#f0fff4; }
        @media print {
            .header,.tabs,.btn-submit,.top-fields,.date-filter,.fav-legend { display:none !important; }
            .card { box-shadow:none !important; }
            body { background:white !important; }
            @page { size:A4 landscape; margin:8mm; }
        }
    </style>
    <script>
    // ===== AMOUNT CALCULATE =====
    function calcAmt(qtyId, rate, amtId, prefix) {
        var qty = parseInt(document.getElementById(qtyId).value) || 0;
        document.getElementById(amtId).value = (qty * rate).toFixed(2);
        updateGT(prefix);
    }

    function calcReplace(q1Id, q2Id, rate, amtId, prefix) {
        var q1 = parseInt(document.getElementById(q1Id).value) || 0;
        var q2 = parseInt(document.getElementById(q2Id).value) || 0;
        document.getElementById(amtId).value = ((q1+q2)*rate).toFixed(2);
        updateGT(prefix);
    }

    function updateGT(prefix) {
        var form = document.getElementById('form_'+prefix);
        if (!form) return;
        var total = 0;
        form.querySelectorAll('input.auto').forEach(function(a) {
            total += parseFloat(a.value) || 0;
        });
        var gt = document.getElementById('gt_'+prefix);
        if (gt) gt.innerText = 'Rs. ' + total.toFixed(2);
    }

    // ===== DEMAND TOTAL =====
    function updateDemandTotal() {
        var total = 0;
        document.querySelectorAll('.demand-qty').forEach(function(inp) {
            total += parseInt(inp.value) || 0;
        });
        var gt = document.getElementById('demand_total');
        if (gt) gt.innerText = total + ' pcs';
    }

    // ===== FAVOURITE TOGGLE =====
    function toggleFav(btn, item_id) {
        fetch('toggle_fav.php?item_id=' + item_id)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.status === 'added') {
                btn.innerText = '⭐';
                btn.closest('tr').style.background = '#fffde7';
            } else {
                btn.innerText = '☆';
                btn.closest('tr').style.background = '';
            }
        })
        .catch(function(e) { console.log(e); });
    }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS &nbsp;|&nbsp; Item Wise Entry &nbsp;|&nbsp; <?= htmlspecialchars($city) ?></h1>
    <div>
        <button onclick="window.print()" style="background:rgba(255,255,255,0.15);color:white;padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.3);cursor:pointer;font-size:12px;">🖨️ Print</button>
        <a href="item_report.php"          class="green">📊 Report</a>
        <a href="agent_entry.php"          class="green">📋 Distribution</a>
        <a href="salesmen_management.php"  class="green">👥 Salesmen</a>
        <a href="change_password.php"      class="yellow">🔐</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-s'>$success</div>"; ?>
    <?php if($error)   echo "<div class='alert-e'>$error</div>"; ?>

    <!-- Tabs -->
    <div class="tabs">
        <?php
        $tabs = [
            'receiving' => ['🏭 Factory Receiving',    '#263238'],
            'd1'        => ['📦 1st Voucher Dispatch', '#0D47A1'],
            'r1'        => ['🔄 1st Voucher Replace',  '#E65100'],
            'd2'        => ['📦 2nd Voucher Dispatch', '#4A148C'],
            'r2'        => ['🔄 2nd Voucher Replace',  '#B71C1C'],
            'fg'        => ['✅ Finish Good',           '#1B5E20'],
            'rfg'       => ['🔄 Finish Good Replace',  '#004D40'],
            'demand'    => ['📋 Demand',               '#F57F17'],
        ];
        foreach($tabs as $key => $tab): ?>
        <a href="?tab=<?= $key ?>"
           class="tab <?= $active_tab==$key?'active':'' ?>"
           style="background:<?= $tab[1] ?>">
           <?= $tab[0] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ========== FACTORY RECEIVING ========== -->
    <?php if($active_tab == 'receiving'): ?>
    <div class="card">
        <h2 style="color:#263238;border-color:#263238;">🏭 Factory Se Receiving Enter Karein</h2>
        <div class="fav-legend">
            <span>⭐ Yellow = Favourite Item</span>
            <span>☆ Star dabao = Favourite/Unfavourite karein</span>
        </div>
        <form method="POST" id="form_recv">
            <div class="top-fields">
                <div class="field">
                    <label>Receiving Date *</label>
                    <input type="date" name="receiving_date" required value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <?php
            $cat_labels = ['first_voucher'=>'📦 1st Voucher','second_voucher'=>'📦 2nd Voucher','finish_good'=>'✅ Finish Good'];
            $cat_colors = ['first_voucher'=>'#0D47A1','second_voucher'=>'#4A148C','finish_good'=>'#1B5E20'];
            ?>
            <div style="overflow-x:auto">
            <table class="items-tbl">
                <thead><tr>
                    <th width="4%">#</th>
                    <th width="3%">⭐</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th width="12%">Rate</th>
                    <th width="14%">Quantity</th>
                    <th width="14%">Amount 🟢</th>
                </tr></thead>
                <tbody>
                <?php $idx=0; foreach($all_items as $item):
                    $qid    = "recv_q_$idx";
                    $aid    = "recv_a_$idx";
                    $is_fav = $item['is_fav'] ?? 0;
                    $clr    = $cat_colors[$item['category']] ?? '#666';
                    $cnm    = $cat_labels[$item['category']] ?? '';
                ?>
                <tr style="<?= $is_fav?'background:#fffde7;':'' ?>">
                    <td style="color:#888;"><?= $idx+1 ?></td>
                    <td>
                        <button type="button"
                            onclick="toggleFav(this,<?= $item['id'] ?>)"
                            style="background:none;border:none;font-size:18px;cursor:pointer;padding:0;">
                            <?= $is_fav ? '⭐' : '☆' ?>
                        </button>
                    </td>
                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong>
                        <input type="hidden" name="item_ids[]" value="<?= $item['id'] ?>">
                    </td>
                    <td>
                        <span style="background:<?= $clr ?>22;color:<?= $clr ?>;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:bold;">
                            <?= $cnm ?>
                        </span>
                    </td>
                    <td style="color:#1565c0;font-weight:bold;">Rs.<?= number_format($item['rate'],2) ?></td>
                    <td><input type="number" id="<?= $qid ?>" name="quantities[]" value="0" min="0"
                        oninput="calcAmt('<?= $qid ?>',<?= $item['rate'] ?>,'<?= $aid ?>','recv')"></td>
                    <td><input type="number" id="<?= $aid ?>" class="auto" readonly value="0.00"></td>
                </tr>
                <?php $idx++; endforeach; ?>
                </tbody>
                <tfoot><tr class="tfoot-row">
                    <td colspan="6">Grand Total</td>
                    <td><strong id="gt_recv">Rs. 0.00</strong></td>
                </tr></tfoot>
            </table>
            </div>
            <button type="submit" name="save_receiving" class="btn-submit btn-recv">
                💾 Receiving Save Karein
            </button>
        </form>
    </div>

    <!-- ========== 1st VOUCHER DISPATCH ========== -->
    <?php elseif($active_tab == 'd1'): ?>
    <div class="card">
        <h2 style="color:#0D47A1;border-color:#0D47A1;">📦 1st Voucher Dispatch</h2>
        <div class="fav-legend">
            <span>⭐ Favourite items upar dikhenge</span>
        </div>
        <form method="POST" id="form_d1">
            <?php makeDispatchTable($items_fv,'d1','save_dispatch_first',
                '1st Voucher Dispatch Save','btn-d1',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== 1st VOUCHER REPLACE ========== -->
    <?php elseif($active_tab == 'r1'): ?>
    <div class="card">
        <h2 style="color:#E65100;border-color:#E65100;">🔄 1st Voucher Replace</h2>
        <div class="fav-legend">
            <span>⭐ Favourite items upar dikhenge</span>
        </div>
        <form method="POST" id="form_r1">
            <?php makeReplaceTable($items_fv,'r1','save_replace_first',
                '1st Voucher Replace Save','btn-r1',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== 2nd VOUCHER DISPATCH ========== -->
    <?php elseif($active_tab == 'd2'): ?>
    <div class="card">
        <h2 style="color:#4A148C;border-color:#4A148C;">📦 2nd Voucher Dispatch</h2>
        <div class="fav-legend">
            <span>⭐ Favourite items upar dikhenge</span>
        </div>
        <form method="POST" id="form_d2">
            <?php makeDispatchTable($items_sv,'d2','save_dispatch_second',
                '2nd Voucher Dispatch Save','btn-d2',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== 2nd VOUCHER REPLACE ========== -->
    <?php elseif($active_tab == 'r2'): ?>
    <div class="card">
        <h2 style="color:#B71C1C;border-color:#B71C1C;">🔄 2nd Voucher Replace</h2>
        <div class="fav-legend">
            <span>⭐ Favourite items upar dikhenge</span>
        </div>
        <form method="POST" id="form_r2">
            <?php makeReplaceTable($items_sv,'r2','save_replace_second',
                '2nd Voucher Replace Save','btn-r2',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== FINISH GOOD ========== -->
    <?php elseif($active_tab == 'fg'): ?>
    <div class="card">
        <h2 style="color:#1B5E20;border-color:#1B5E20;">✅ Finish Good Entry</h2>
        <form method="POST" id="form_fg">
            <?php makeDispatchTable($items_fg,'fg','save_finish_good',
                'Finish Good Save','btn-fg',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== FINISH GOOD REPLACE ========== -->
    <?php elseif($active_tab == 'rfg'): ?>
    <div class="card">
        <h2 style="color:#004D40;border-color:#004D40;">🔄 Finish Good Replace</h2>
        <form method="POST" id="form_rfg">
            <?php makeReplaceTable($items_fg,'rfg','save_replace_finish',
                'Finish Good Replace Save','btn-rfg',$salesmen_arr); ?>
        </form>
    </div>

    <!-- ========== DEMAND ========== -->
    <?php elseif($active_tab == 'demand'): ?>
    <div class="card">
        <h2 style="color:#E65100;border-color:#F57F17;">📋 Demand Entry — <?= htmlspecialchars($city) ?></h2>

        <div class="demand-info">
            💡 Kal ki demand enter karein — Admin ko automatically dikh jayegi.
            Same date ki demand dobara submit karne se update ho jayegi.
        </div>

        <div class="date-filter">
            <span style="font-size:13px;color:#666;font-weight:bold;">Previous Demand:</span>
            <form method="GET" style="display:flex;gap:8px;">
                <input type="hidden" name="tab" value="demand">
                <input type="date" name="demand_date" value="<?= $prev_demand_date ?>">
                <button type="submit">📅 Load</button>
            </form>
        </div>

        <form method="POST">
            <div class="top-fields">
                <div class="field">
                    <label>Demand Date *</label>
                    <input type="date" name="demand_date" required
                        value="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="field" style="display:flex;align-items:flex-end;">
                    <div style="background:#fff8e1;border:1px solid #FFE082;border-radius:8px;padding:10px 15px;width:100%;">
                        <div style="font-size:11px;color:#856404;font-weight:bold;margin-bottom:4px;">TOTAL DEMAND</div>
                        <div id="demand_total" style="font-size:20px;font-weight:bold;color:#e65100;">0 pcs</div>
                    </div>
                </div>
            </div>

            <div style="overflow-x:auto">
            <table class="items-tbl">
                <thead><tr>
                    <th width="4%">#</th>
                    <th width="3%">⭐</th>
                    <th>Item Name</th>
                    <th width="15%">Category</th>
                    <th width="18%">Demand Qty</th>
                    <?php if(!empty($prev_demand)): ?>
                    <th width="12%">Previous</th>
                    <?php endif; ?>
                </tr></thead>
                <tbody>
                <?php
                $cat_labels2 = ['first_voucher'=>'📦 1st Voucher','second_voucher'=>'📦 2nd Voucher','finish_good'=>'✅ Finish Good'];
                $cat_colors2 = ['first_voucher'=>'#0D47A1','second_voucher'=>'#4A148C','finish_good'=>'#1B5E20'];
                $idx = 0;
                foreach($all_items as $item):
                    $clr      = $cat_colors2[$item['category']] ?? '#666';
                    $cnm      = $cat_labels2[$item['category']] ?? '';
                    $prev_qty = $prev_demand[$item['id']] ?? 0;
                    $is_fav   = $item['is_fav'] ?? 0;
                ?>
                <tr style="<?= $is_fav?'background:#fffde7;':'' ?>">
                    <td style="color:#888;"><?= $idx+1 ?></td>
                    <td>
                        <button type="button"
                            onclick="toggleFav(this,<?= $item['id'] ?>)"
                            style="background:none;border:none;font-size:18px;cursor:pointer;padding:0;">
                            <?= $is_fav ? '⭐' : '☆' ?>
                        </button>
                    </td>
                    <td><strong><?= htmlspecialchars($item['item_name']) ?></strong>
                        <input type="hidden" name="item_ids[]" value="<?= $item['id'] ?>">
                    </td>
                    <td>
                        <span style="background:<?= $clr ?>22;color:<?= $clr ?>;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:bold;">
                            <?= $cnm ?>
                        </span>
                    </td>
                    <td>
                        <input type="number"
                            name="quantities[]"
                            class="demand-qty"
                            value="<?= $prev_qty ?>"
                            min="0"
                            oninput="updateDemandTotal()"
                            style="width:100%;padding:7px 10px;border:2px solid #FFE082;border-radius:6px;font-size:14px;font-weight:bold;text-align:right;background:#fffde7;">
                    </td>
                    <?php if(!empty($prev_demand)): ?>
                    <td style="color:#888;text-align:right;font-size:13px;">
                        <?= $prev_qty > 0
                            ? "<span style='color:#e65100;font-weight:bold;'>$prev_qty</span>"
                            : "—" ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php $idx++; endforeach; ?>
                </tbody>
                <tfoot><tr class="tfoot-row">
                    <td colspan="<?= !empty($prev_demand)?5:4 ?>">Total Demand</td>
                    <td><strong id="demand_total_foot">—</strong></td>
                </tr></tfoot>
            </table>
            </div>
            <button type="submit" name="save_demand" class="btn-submit btn-demand">
                📋 Demand Submit Karein
            </button>
        </form>
    </div>

    <!-- Recent demands -->
    <?php
    $recent_q = mysqli_query($conn,
        "SELECT demand_date, COUNT(*) as items,
         SUM(quantity) as total_qty, status
         FROM warehouse_demand
         WHERE city='$city' AND agent_id='$agent_id'
         GROUP BY demand_date, status
         ORDER BY demand_date DESC LIMIT 10");
    if (mysqli_num_rows($recent_q) > 0):
    ?>
    <div class="card">
        <h2 style="color:#1a1a2e;border-color:#4CAF50;">📅 Recent Demands</h2>
        <table class="items-tbl">
            <thead><tr>
                <th>#</th><th>Date</th><th>Items</th>
                <th>Total Qty</th><th>Status</th><th>Edit</th>
            </tr></thead>
            <tbody>
            <?php $i=1; while($row = mysqli_fetch_assoc($recent_q)):
                $s_colors = ['pending'=>'#856404','received'=>'#155724','partial'=>'#1565c0'];
                $s_bgs    = ['pending'=>'#fff3cd','received'=>'#d4edda','partial'=>'#e3f2fd'];
                $sc = $s_colors[$row['status']] ?? '#666';
                $sb = $s_bgs[$row['status']]    ?? '#eee';
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= date('d-m-Y',strtotime($row['demand_date'])) ?></td>
                <td><?= $row['items'] ?> items</td>
                <td><strong><?= number_format($row['total_qty']) ?> pcs</strong></td>
                <td>
                    <span style="background:<?= $sb ?>;color:<?= $sc ?>;padding:3px 10px;border-radius:15px;font-size:11px;font-weight:bold;">
                        <?= strtoupper($row['status']) ?>
                    </span>
                </td>
                <td>
                    <a href="?tab=demand&demand_date=<?= $row['demand_date'] ?>"
                        style="background:#2196F3;color:white;padding:4px 10px;border-radius:5px;text-decoration:none;font-size:11px;">
                        📝 Edit
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
</body>
</html>