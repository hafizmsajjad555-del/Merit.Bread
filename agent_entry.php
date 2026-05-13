<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'agent') {
    header("Location: login.php"); exit();
}

$success = $error = "";
$agent_id = $_SESSION['user_id'];
$city     = $_SESSION['city'];
$edit_data = null;

// MC Settings load karo
$mc_set = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM mc_settings WHERE city='$city'"));
$mc_base_setting = $mc_set ? $mc_set['mc_base']       : 'first_voucher';
$mc_percentage   = $mc_set ? $mc_set['mc_percentage']  : 0;

// Edit load
if (isset($_GET['edit'])) {
    $edit_id   = intval($_GET['edit']);
    $edit_data = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM distribution_sheet WHERE id='$edit_id' AND agent_id='$agent_id'"));
    if (!$edit_data) $error = "❌ Ye entry edit nahi kar sakte!";
}

// Update entry
if (isset($_POST['update_entry'])) {
    $id           = intval($_POST['entry_id']);
    $salesman     = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $father       = mysqli_real_escape_string($conn, $_POST['father_name']);
    $sector       = mysqli_real_escape_string($conn, $_POST['sector_name']);
    $fv           = floatval($_POST['first_voucher']);
    $fvr          = floatval($_POST['first_vou_rep']);
    $sv           = floatval($_POST['second_voucher']);
    $svr          = floatval($_POST['second_vou_rep']);
    $fuel         = floatval($_POST['fuel']);
    $td           = floatval($_POST['td']);
    $mc           = floatval($_POST['mc']);
    $mktbill      = floatval($_POST['market_bill']);
    $freesampl    = floatval($_POST['free_sampling']);
    $totaltax     = floatval($_POST['total_tax']);
    $vanpaid      = floatval($_POST['van_rent_paid']);
    $vanpayable   = floatval($_POST['van_rent_payable']);
    $incbase      = floatval($_POST['incentive_exp_base']);
    $dispinc      = floatval($_POST['dispatch_bach_incentive']);
    $dealer       = floatval($_POST['dealer']);
    $fingoods     = floatval($_POST['finish_goods']);
    $cashrecv     = floatval($_POST['cash_received']);
    $cashsend     = floatval($_POST['cash_send']);
    $iou          = floatval($_POST['iou']);
    $centerexp    = floatval($_POST['center_expenses']);
    $adj          = floatval($_POST['adjustment']);
    $closingbal   = floatval($_POST['closing_balance']);
    $cashinhand   = floatval($_POST['cash_in_hand']);
    $physstock    = floatval($_POST['physical_stock']);

    $net_sales       = $fv - $fvr;
    $total_dispatch  = $fv + $sv;
    $total_net_sales = $net_sales + $sv - $svr;
    $total_mc        = $td + $mc + $mktbill + $freesampl;
    $fuel_pct        = $total_dispatch > 0 ? ($fuel/$total_dispatch*100) : 0;
    $mc_pct          = $total_dispatch > 0 ? ($total_mc/$total_dispatch*100) : 0;
    $commission      = ($total_net_sales - $total_mc) * 5 / 100;
    $total_expenses  = $fuel + $total_mc + $totaltax + $vanpaid + $vanpayable + $incbase + $dispinc + $commission + $dealer + $fvr + $svr;
    $exp_pct         = $total_dispatch > 0 ? ($total_expenses/$total_dispatch*100) : 0;

    $sql = "UPDATE distribution_sheet SET
        salesman_name='$salesman', father_name='$father', sector_name='$sector',
        first_voucher='$fv', first_vou_rep='$fvr', net_sales='$net_sales',
        second_voucher='$sv', second_vou_rep='$svr', total_net_sales='$total_net_sales',
        total_dispatch='$total_dispatch', fuel='$fuel', fuel_percentage='$fuel_pct',
        td='$td', mc='$mc', market_bill='$mktbill', free_sampling='$freesampl',
        total_mc='$total_mc', mc_percentage='$mc_pct', total_tax='$totaltax',
        van_rent_paid='$vanpaid', van_rent_payable='$vanpayable',
        incentive_exp_base='$incbase', dispatch_bach_incentive='$dispinc',
        commission='$commission', dealer='$dealer', finish_goods='$fingoods',
        total_expenses='$total_expenses', exp_percentage='$exp_pct',
        cash_received='$cashrecv', cash_send='$cashsend', iou='$iou',
        center_expenses='$centerexp', adjustment='$adj',
        closing_balance='$closingbal', cash_in_hand='$cashinhand',
        physical_stock='$physstock'
        WHERE id='$id' AND agent_id='$agent_id'";

    if (mysqli_query($conn, $sql)) {
        $success   = "✅ Entry update ho gayi!";
        $edit_data = null;
    } else {
        $error = "❌ Error: " . mysqli_error($conn);
    }
}

// New entry save
if (isset($_POST['save_entry'])) {
    $salesman     = mysqli_real_escape_string($conn, $_POST['salesman_name']);
    $father       = mysqli_real_escape_string($conn, $_POST['father_name']);
    $sector       = mysqli_real_escape_string($conn, $_POST['sector_name']);
    $fv           = floatval($_POST['first_voucher']);
    $fvr          = floatval($_POST['first_vou_rep']);
    $sv           = floatval($_POST['second_voucher']);
    $svr          = floatval($_POST['second_vou_rep']);
    $fuel         = floatval($_POST['fuel']);
    $td           = floatval($_POST['td']);
    $mc           = floatval($_POST['mc']);
    $mktbill      = floatval($_POST['market_bill']);
    $freesampl    = floatval($_POST['free_sampling']);
    $totaltax     = floatval($_POST['total_tax']);
    $vanpaid      = floatval($_POST['van_rent_paid']);
    $vanpayable   = floatval($_POST['van_rent_payable']);
    $incbase      = floatval($_POST['incentive_exp_base']);
    $dispinc      = floatval($_POST['dispatch_bach_incentive']);
    $dealer       = floatval($_POST['dealer']);
    $fingoods     = floatval($_POST['finish_goods']);
    $cashrecv     = floatval($_POST['cash_received']);
    $cashsend     = floatval($_POST['cash_send']);
    $iou          = floatval($_POST['iou']);
    $centerexp    = floatval($_POST['center_expenses']);
    $adj          = floatval($_POST['adjustment']);
    $closingbal   = floatval($_POST['closing_balance']);
    $cashinhand   = floatval($_POST['cash_in_hand']);
    $physstock    = floatval($_POST['physical_stock']);

    $net_sales       = $fv - $fvr;
    $total_dispatch  = $fv + $sv;
    $total_net_sales = $net_sales + $sv - $svr;
    $total_mc        = $td + $mc + $mktbill + $freesampl;
    $fuel_pct        = $total_dispatch > 0 ? ($fuel/$total_dispatch*100) : 0;
    $mc_pct          = $total_dispatch > 0 ? ($total_mc/$total_dispatch*100) : 0;
    $commission      = ($total_net_sales - $total_mc) * 5 / 100;
    $total_expenses  = $fuel + $total_mc + $totaltax + $vanpaid + $vanpayable + $incbase + $dispinc + $commission + $dealer + $fvr + $svr;
    $exp_pct         = $total_dispatch > 0 ? ($total_expenses/$total_dispatch*100) : 0;

    $sql = "INSERT INTO distribution_sheet
        (agent_id,city,salesman_name,father_name,sector_name,
        first_voucher,first_vou_rep,net_sales,second_voucher,second_vou_rep,
        total_net_sales,total_dispatch,fuel,fuel_percentage,td,mc,market_bill,
        free_sampling,total_mc,mc_percentage,total_tax,van_rent_paid,van_rent_payable,
        incentive_exp_base,dispatch_bach_incentive,commission,dealer,finish_goods,
        total_expenses,exp_percentage,cash_received,cash_send,iou,center_expenses,
        adjustment,closing_balance,cash_in_hand,physical_stock)
        VALUES
        ('$agent_id','$city','$salesman','$father','$sector',
        '$fv','$fvr','$net_sales','$sv','$svr',
        '$total_net_sales','$total_dispatch','$fuel','$fuel_pct','$td','$mc','$mktbill',
        '$freesampl','$total_mc','$mc_pct','$totaltax','$vanpaid','$vanpayable',
        '$incbase','$dispinc','$commission','$dealer','$fingoods',
        '$total_expenses','$exp_pct','$cashrecv','$cashsend','$iou','$centerexp',
        '$adj','$closingbal','$cashinhand','$physstock')";

    if (mysqli_query($conn, $sql)) {
        $success = "✅ Entry save ho gayi!";
    } else {
        $error = "❌ Error: " . mysqli_error($conn);
    }
}

$ed      = $edit_data;
$entries = mysqli_query($conn,
    "SELECT * FROM distribution_sheet
     WHERE agent_id='$agent_id'
     ORDER BY entry_date DESC LIMIT 50");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Distribution Sheet — <?= htmlspecialchars($city) ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .header {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: white; padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .header h1 { font-size: 18px; }
        .header a  { color: #ff6b6b; text-decoration: none; margin-left:10px; font-size:13px; }
        .header a.green  { color: #90EE90; }
        .header a.yellow { color: #FFD700; }
        .container { max-width: 1100px; margin: 25px auto; padding: 0 20px; }
        .card { background: white; border-radius: 12px; padding: 25px; box-shadow: 0 2px 15px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .card h2 { color: #1a1a2e; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .card.editing { border: 2px solid #FF9800; }
        .card.editing h2 { color: #e65100; border-color: #FF9800; }
        .section-title { background: #f8f9fa; padding: 8px 15px; border-left: 4px solid #4CAF50; margin: 20px 0 15px; font-weight: bold; color: #1a1a2e; border-radius: 0 8px 8px 0; font-size: 13px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 15px; }
        .field label { display: block; font-size: 12px; color: #666; margin-bottom: 5px; font-weight: bold; text-transform: uppercase; }
        .field input { width: 100%; padding: 10px 12px; border: 2px solid #eee; border-radius: 8px; font-size: 13px; }
        .field input:focus { border-color: #4CAF50; outline: none; }
        .field input.auto { background: #e8f5e9; color: #2e7d32; font-weight: bold; }
        .field input.auto-cb { background: #fff3e0; color: #e65100; font-weight: bold; }
        .btn-submit { background: linear-gradient(135deg,#4CAF50,#2e7d32); color:white; padding:13px; border:none; border-radius:8px; font-size:15px; cursor:pointer; font-weight:bold; width:100%; margin-top:15px; }
        .btn-update { background: linear-gradient(135deg,#FF9800,#e65100); color:white; padding:13px; border:none; border-radius:8px; font-size:15px; cursor:pointer; font-weight:bold; width:100%; margin-top:15px; }
        .btn-cancel { display:block; text-align:center; margin-top:10px; padding:10px; background:#eee; border-radius:8px; text-decoration:none; color:#666; font-weight:bold; }
        .btn-excel  { background:#217346; color:white; padding:7px 15px; border-radius:8px; text-decoration:none; font-size:12px; display:inline-block; }
        .alert-success { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .alert-error   { background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; }
        .edit-banner   { background:#fff3cd; border:2px solid #FF9800; padding:12px; border-radius:8px; margin-bottom:15px; color:#856404; font-weight:bold; text-align:center; }
        .autofill-banner { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; display:none; }
        .mc-info { font-size:11px; color:#4CAF50; margin-top:3px; }
        .table-wrap { overflow-x: auto; }
        table { width:100%; border-collapse:collapse; font-size:11px; min-width:1400px; }
        th { background:#1a1a2e; color:white; padding:9px 7px; text-align:left; white-space:nowrap; }
        td { padding:7px; border-bottom:1px solid #eee; white-space:nowrap; }
        tr:hover td { background:#f9f9f9; }
        tfoot td { background:#e8f5e9; font-weight:bold; border-top:2px solid #4CAF50; }
        .auto-col { background:#e8f5e9; color:#2e7d32; font-weight:bold; }
        .cash-col { background:#fff3e0; color:#e65100; }
        .btn-edit { background:#FF9800; color:white; padding:4px 10px; border:none; border-radius:5px; cursor:pointer; font-size:11px; text-decoration:none; display:inline-block; }
        .search-box { padding:10px 15px; border:2px solid #eee; border-radius:8px; width:280px; font-size:13px; margin-bottom:15px; }
        .search-box:focus { border-color:#4CAF50; outline:none; }

        @media print {
            @page { size: A4 landscape; margin: 8mm; }
            body { background: white !important; font-size: 9px; }
            .header, .btn-submit, .btn-update, .btn-cancel,
            .btn-edit, .btn-excel, .search-box,
            .autofill-banner, .edit-banner,
            .card:first-of-type { display: none !important; }
            .card { box-shadow: none !important; padding: 5px !important; margin:0 !important; }
            table { font-size: 8px !important; min-width: unset !important; }
            th, td { padding: 3px 4px !important; }
            .container { padding: 0 !important; margin: 0 !important; max-width: 100% !important; }
        }
    </style>
    <script>
    var mc_base_setting = "<?= $mc_base_setting ?>";
    var mc_percentage   = <?= $mc_percentage ?>;
function fillSalesmanInfo(select) {
    var option = select.options[select.selectedIndex];
    document.getElementById('father_name_field').value = option.getAttribute('data-father') || '';
    document.getElementById('sector_name_field').value = option.getAttribute('data-sector') || '';

    // Auto fill from item wise bhi trigger karo
    var date = document.getElementById('entry_date_field').value;
    if (select.value && date) {
        autoFillFromItemWise(select.value, date);
    }
    calculate();
}

function autoFillFromItemWise(name, date) {
    fetch('get_salesman_data.php?salesman=' + encodeURIComponent(name) +
          '&date=' + date + '&city=<?= urlencode($city) ?>')
    .then(r => r.json())
    .then(data => {
        if (data.found) {
            document.getElementById('first_voucher').value  = data.first_voucher;
            document.getElementById('first_vou_rep').value  = data.first_vou_rep;
            document.getElementById('second_voucher').value = data.second_voucher;
            document.getElementById('second_vou_rep').value = data.second_vou_rep;
            document.getElementById('finish_goods').value   = data.finish_goods;
            calculate();
            var b = document.getElementById('autofill-banner');
            b.style.display = 'block';
            b.innerHTML = '✅ ' + name + ' ka Item Wise data auto fill ho gaya!';
        }
    }).catch(function(){});
}

    function calculate() {
        var fv   = parseFloat(document.getElementById('first_voucher').value)   || 0;
        var fvr  = parseFloat(document.getElementById('first_vou_rep').value)   || 0;
        var sv   = parseFloat(document.getElementById('second_voucher').value)  || 0;
        var svr  = parseFloat(document.getElementById('second_vou_rep').value)  || 0;
        var fuel = parseFloat(document.getElementById('fuel').value)            || 0;
        var td   = parseFloat(document.getElementById('td').value)              || 0;
        var mb   = parseFloat(document.getElementById('market_bill').value)     || 0;
        var fs   = parseFloat(document.getElementById('free_sampling').value)   || 0;
        var tax  = parseFloat(document.getElementById('total_tax').value)       || 0;
        var vp   = parseFloat(document.getElementById('van_rent_paid').value)   || 0;
        var vpy  = parseFloat(document.getElementById('van_rent_payable').value)|| 0;
        var ib   = parseFloat(document.getElementById('incentive_exp_base').value)||0;
        var di   = parseFloat(document.getElementById('dispatch_bach_incentive').value)||0;
        var dl   = parseFloat(document.getElementById('dealer').value)          || 0;
        var cr   = parseFloat(document.getElementById('cash_received').value)   || 0;
        var fg   = parseFloat(document.getElementById('finish_goods').value)    || 0;

        var net_sales       = fv - fvr;
        var total_dispatch  = fv + sv;
        var total_net_sales = net_sales + sv - svr;
        var fuel_pct        = total_dispatch > 0 ? (fuel/total_dispatch*100) : 0;

        // MC AUTO calculate
        var mc_field = document.getElementById('mc');
        var mc = 0;
        if (mc_field.getAttribute('data-auto') !== 'false') {
            var mc_base_val = (mc_base_setting == 'first_voucher') ? net_sales : total_net_sales;
            mc = (mc_base_val - fs) * mc_percentage / 100;
            if (mc < 0) mc = 0;
            mc_field.value = mc.toFixed(2);
        } else {
            mc = parseFloat(mc_field.value) || 0;
        }

        var total_mc       = td + mc + mb + fs;
        var mc_pct         = total_dispatch > 0 ? (total_mc/total_dispatch*100) : 0;
        var commission     = (total_net_sales - total_mc) * 5 / 100;
        var total_expenses = fuel + total_mc + tax + vp + vpy + ib + di + commission + dl + fvr + svr;
        var exp_pct        = total_dispatch > 0 ? (total_expenses/total_dispatch*100) : 0;

        // Closing Balance AUTO
        var closing_balance = total_net_sales + fg - fuel - mc - td - fs - mb - tax - vp - cr;

        document.getElementById('net_sales').value        = net_sales.toFixed(2);
        document.getElementById('total_dispatch').value   = total_dispatch.toFixed(2);
        document.getElementById('total_net_sales').value  = total_net_sales.toFixed(2);
        document.getElementById('total_mc').value         = total_mc.toFixed(2);
        document.getElementById('fuel_percentage').value  = fuel_pct.toFixed(2);
        document.getElementById('mc_percentage').value    = mc_pct.toFixed(2);
        document.getElementById('commission').value       = commission.toFixed(2);
        document.getElementById('total_expenses').value   = total_expenses.toFixed(2);
        document.getElementById('exp_percentage').value   = exp_pct.toFixed(2);
        document.getElementById('closing_balance').value  = closing_balance.toFixed(2);
    }

    // Auto fill from item wise
    var autoTimer = null;
    function autoFillSalesman() {
        var name = document.getElementById('salesman_name_field').value.trim();
        var date = document.getElementById('entry_date_field').value;
        if (name.length < 2 || !date) return;
        clearTimeout(autoTimer);
        autoTimer = setTimeout(function() {
            fetch('get_salesman_data.php?salesman=' + encodeURIComponent(name) + '&date=' + date + '&city=<?= urlencode($city) ?>')
            .then(r => r.json())
            .then(data => {
                if (data.found) {
                    document.getElementById('first_voucher').value  = data.first_voucher;
                    document.getElementById('first_vou_rep').value  = data.first_vou_rep;
                    document.getElementById('second_voucher').value = data.second_voucher;
                    document.getElementById('second_vou_rep').value = data.second_vou_rep;
                    document.getElementById('finish_goods').value   = data.finish_goods;
                    calculate();
                    var b = document.getElementById('autofill-banner');
                    b.style.display = 'block';
                    b.innerHTML = '✅ ' + name + ' ka data auto fill ho gaya!';
                }
            }).catch(function(){});
        }, 800);
    }

    function searchTable() {
        var input = document.getElementById('searchInput').value.toLowerCase();
        document.querySelectorAll('tbody tr').forEach(function(row) {
            row.style.display = row.innerText.toLowerCase().includes(input) ? '' : 'none';
        });
    }

    function validateForm() {
        var salesman = document.getElementById('salesman_name_field').value.trim();
        var fv = parseFloat(document.getElementById('first_voucher').value) || 0;
        var sv = parseFloat(document.getElementById('second_voucher').value) || 0;
        if (salesman.length < 2) {
            alert('❌ Salesman Name kam az kam 2 characters ka hona chahiye!');
            return false;
        }
        if (fv <= 0 && sv <= 0) {
            alert('❌ 1st ya 2nd Voucher mein se koi ek amount zaroor darj karein!');
            return false;
        }
        return true;
    }

    window.onload = function() {
        // MC field listener
        var mc_field = document.getElementById('mc');
        mc_field.setAttribute('data-auto', 'true');
        mc_field.addEventListener('input', function() {
            this.setAttribute('data-auto', 'false');
            calculate();
        });

        // MC Reset button
        var mc_btn = document.getElementById('mc_reset_btn');
        if (mc_btn) {
            mc_btn.addEventListener('click', function() {
                document.getElementById('mc').setAttribute('data-auto', 'true');
                calculate();
            });
        }
        calculate();
    }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS &nbsp;|&nbsp; <?= htmlspecialchars($city) ?></h1>
    <div>
        <button onclick="window.print()" style="background:rgba(255,255,255,0.15);color:white;padding:7px 14px;border-radius:8px;border:1px solid rgba(255,255,255,0.3);cursor:pointer;font-size:12px;">🖨️ Print</button>
        <a href="export_excel.php?type=distribution&city=<?= urlencode($city) ?>" class="btn-excel">📊 Excel</a>
        <a href="item_entry.php" class="green">📦 Item Wise</a>
        <a href="item_report.php" class="green">📊 Report</a>
<a href="salesmen_management.php" class="green">👥 Salesmen</a>
        <a href="change_password.php" class="yellow">🔐 Password</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-success'>$success</div>"; ?>
    <?php if($error)   echo "<div class='alert-error'>$error</div>"; ?>
    <?php if($edit_data) echo "<div class='edit-banner'>✏️ Edit Mode — Salesman: <strong>{$edit_data['salesman_name']}</strong></div>"; ?>
    <div id="autofill-banner" class="autofill-banner"></div>

    <!-- Entry Form -->
    <div class="card <?= $edit_data ? 'editing' : '' ?>">
        <h2><?= $edit_data ? '✏️ Entry Edit Karein' : '➕ New Entry' ?></h2>
        <form method="POST" onsubmit="return validateForm()">
            <?php if($edit_data): ?>
            <input type="hidden" name="entry_id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>

            <div class="section-title">👤 Salesman Info</div>
<div class="grid-3">
    <div class="field">
        <label>Entry Date *</label>
        <input type="date" 
            id="entry_date_field" 
            name="entry_date_field"
            value="<?= date('Y-m-d') ?>" 
            onchange="fillSalesmanInfo(document.getElementById('salesman_select'))">
    </div>
                <?php
// Is city ke active salesmen
$salesmen_list = mysqli_query($conn,
    "SELECT * FROM salesmen WHERE city='$city' AND is_active=1 ORDER BY salesman_name");
$salesmen_arr = [];
while($s = mysqli_fetch_assoc($salesmen_list)) $salesmen_arr[] = $s;
?>
<div class="field">
    <label>Salesman Name *</label>
    <?php if(!empty($salesmen_arr)): ?>
    <select id="salesman_select" name="salesman_name" required
        onchange="fillSalesmanInfo(this)"
        style="width:100%;padding:10px 12px;border:2px solid #eee;border-radius:8px;font-size:13px;">
        <option value="">-- Salesman Select Karein --</option>
        <?php foreach($salesmen_arr as $s): ?>
        <option value="<?= htmlspecialchars($s['salesman_name']) ?>"
            data-father="<?= htmlspecialchars($s['father_name']) ?>"
            data-sector="<?= htmlspecialchars($s['sector_name']) ?>"
            <?= ($ed && $ed['salesman_name']==$s['salesman_name']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['salesman_name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php else: ?>
    <input type="text" id="salesman_name_field" name="salesman_name"
        required placeholder="Enter Salesman Name"
        value="<?= $ed ? htmlspecialchars($ed['salesman_name']) : '' ?>">
    <small style="color:#ff6b6b;">⚠️ Koi salesman nahi — <a href="salesmen_management.php">Add Karein</a></small>
    <?php endif; ?>
</div>
<div class="field">
    <label>Father Name</label>
    <input type="text" id="father_name_field" name="father_name" readonly
        style="background:#f8f9fa;"
        value="<?= $ed ? htmlspecialchars($ed['father_name']) : '' ?>">
</div>
<div class="field">
    <label>Sector Name</label>
    <input type="text" id="sector_name_field" name="sector_name" readonly
        style="background:#f8f9fa;"
        value="<?= $ed ? htmlspecialchars($ed['sector_name']) : '' ?>">
</div>
            </div>

            <div class="section-title">🧾 Voucher Details</div>
            <div class="grid-3">
                <div class="field"><label>First Voucher</label>
                    <input type="number" id="first_voucher" name="first_voucher" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['first_voucher'] : '0' ?>"></div>
                <div class="field"><label>First Vou. Rep</label>
                    <input type="number" id="first_vou_rep" name="first_vou_rep" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['first_vou_rep'] : '0' ?>"></div>
                <div class="field"><label>Net Sales 🟢 AUTO</label>
                    <input type="number" id="net_sales" name="net_sales" class="auto" readonly
                        value="<?= $ed ? $ed['net_sales'] : '0' ?>"></div>
                <div class="field"><label>2nd Voucher</label>
                    <input type="number" id="second_voucher" name="second_voucher" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['second_voucher'] : '0' ?>"></div>
                <div class="field"><label>2nd Voucher Replace</label>
                    <input type="number" id="second_vou_rep" name="second_vou_rep" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['second_vou_rep'] : '0' ?>"></div>
                <div class="field"><label>Total Net Sales 🟢 AUTO</label>
                    <input type="number" id="total_net_sales" name="total_net_sales" class="auto" readonly
                        value="<?= $ed ? $ed['total_net_sales'] : '0' ?>"></div>
                <div class="field"><label>Total Dispatch 🟢 AUTO</label>
                    <input type="number" id="total_dispatch" name="total_dispatch" class="auto" readonly
                        value="<?= $ed ? $ed['total_dispatch'] : '0' ?>"></div>
                <div class="field"><label>Finish Goods</label>
                    <input type="number" id="finish_goods" name="finish_goods" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['finish_goods'] : '0' ?>"></div>
            </div>

            <div class="section-title">⛽ Fuel</div>
            <div class="grid-3">
                <div class="field"><label>Fuel</label>
                    <input type="number" id="fuel" name="fuel" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['fuel'] : '0' ?>"></div>
                <div class="field"><label>Fuel % 🟢 AUTO</label>
                    <input type="number" id="fuel_percentage" name="fuel_percentage" class="auto" readonly
                        value="<?= $ed ? $ed['fuel_percentage'] : '0' ?>"></div>
            </div>

            <div class="section-title">📊 MC Details</div>
            <div class="grid-3">
                <div class="field"><label>TD</label>
                    <input type="number" id="td" name="td" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['td'] : '0' ?>"></div>
                <div class="field">
                    <label>MC &nbsp;
                        <button type="button" id="mc_reset_btn"
                            style="background:#4CAF50;color:white;border:none;padding:2px 8px;border-radius:5px;font-size:10px;cursor:pointer;">
                            🔄 Auto
                        </button>
                    </label>
                    <input type="number" id="mc" name="mc" step="0.01"
                        value="<?= $ed ? $ed['mc'] : '0' ?>">
                    <div class="mc-info">
                        Auto: (<?= $mc_base_setting=='first_voucher'?'1st Vou. Net':'Total Net' ?> - Free Sampling) × <?= $mc_percentage ?>%
                    </div>
                </div>
                <div class="field"><label>Market Bill</label>
                    <input type="number" id="market_bill" name="market_bill" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['market_bill'] : '0' ?>"></div>
                <div class="field"><label>Free Sampling</label>
                    <input type="number" id="free_sampling" name="free_sampling" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['free_sampling'] : '0' ?>"></div>
                <div class="field"><label>Total MC 🟢 AUTO</label>
                    <input type="number" id="total_mc" name="total_mc" class="auto" readonly
                        value="<?= $ed ? $ed['total_mc'] : '0' ?>"></div>
                <div class="field"><label>MC % 🟢 AUTO</label>
                    <input type="number" id="mc_percentage" name="mc_percentage" class="auto" readonly
                        value="<?= $ed ? $ed['mc_percentage'] : '0' ?>"></div>
            </div>

            <div class="section-title">💰 Expenses</div>
            <div class="grid-3">
                <div class="field"><label>Total Tax</label>
                    <input type="number" id="total_tax" name="total_tax" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['total_tax'] : '0' ?>"></div>
                <div class="field"><label>Van Rent Paid</label>
                    <input type="number" id="van_rent_paid" name="van_rent_paid" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['van_rent_paid'] : '0' ?>"></div>
                <div class="field"><label>Van Rent Payable</label>
                    <input type="number" id="van_rent_payable" name="van_rent_payable" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['van_rent_payable'] : '0' ?>"></div>
                <div class="field"><label>Incentive Exp Base</label>
                    <input type="number" id="incentive_exp_base" name="incentive_exp_base" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['incentive_exp_base'] : '0' ?>"></div>
                <div class="field"><label>Dispatch Bach Incentive</label>
                    <input type="number" id="dispatch_bach_incentive" name="dispatch_bach_incentive" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['dispatch_bach_incentive'] : '0' ?>"></div>
                <div class="field"><label>Commission 🟢 AUTO</label>
                    <input type="number" id="commission" name="commission" class="auto" readonly
                        value="<?= $ed ? $ed['commission'] : '0' ?>"></div>
                <div class="field"><label>Dealer</label>
                    <input type="number" id="dealer" name="dealer" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['dealer'] : '0' ?>"></div>
                <div class="field"><label>Total Expenses 🟢 AUTO</label>
                    <input type="number" id="total_expenses" name="total_expenses" class="auto" readonly
                        value="<?= $ed ? $ed['total_expenses'] : '0' ?>"></div>
                <div class="field"><label>Exp % 🟢 AUTO</label>
                    <input type="number" id="exp_percentage" name="exp_percentage" class="auto" readonly
                        value="<?= $ed ? $ed['exp_percentage'] : '0' ?>"></div>
            </div>

            <div class="section-title">💵 Cash & Stock</div>
            <div class="grid-3">
                <div class="field"><label>Cash Received</label>
                    <input type="number" id="cash_received" name="cash_received" step="0.01" oninput="calculate()"
                        value="<?= $ed ? $ed['cash_received'] : '0' ?>"></div>
                <div class="field"><label>Cash Send</label>
                    <input type="number" name="cash_send" step="0.01"
                        value="<?= $ed ? $ed['cash_send'] : '0' ?>"></div>
                <div class="field"><label>IOU</label>
                    <input type="number" name="iou" step="0.01"
                        value="<?= $ed ? $ed['iou'] : '0' ?>"></div>
                <div class="field"><label>Center Expenses</label>
                    <input type="number" name="center_expenses" step="0.01"
                        value="<?= $ed ? $ed['center_expenses'] : '0' ?>"></div>
                <div class="field"><label>Adjustment</label>
                    <input type="number" name="adjustment" step="0.01"
                        value="<?= $ed ? $ed['adjustment'] : '0' ?>"></div>
                <div class="field"><label>Closing Balance 🟢 AUTO</label>
                    <input type="number" id="closing_balance" name="closing_balance"
                        class="auto-cb" readonly
                        value="<?= $ed ? $ed['closing_balance'] : '0' ?>"></div>
                <div class="field"><label>Cash In Hand</label>
                    <input type="number" name="cash_in_hand" step="0.01"
                        value="<?= $ed ? $ed['cash_in_hand'] : '0' ?>"></div>
                <div class="field"><label>Physical Stock</label>
                    <input type="number" name="physical_stock" step="0.01"
                        value="<?= $ed ? $ed['physical_stock'] : '0' ?>"></div>
            </div>

            <?php if($edit_data): ?>
                <button type="submit" name="update_entry" class="btn-update">💾 Entry Update Karein</button>
                <a href="agent_entry.php" class="btn-cancel">✕ Cancel</a>
            <?php else: ?>
                <button type="submit" name="save_entry" class="btn-submit">💾 Entry Save Karein</button>
            <?php endif; ?>
        </form>
    </div>
<?php
// Summary calculate karo
$summary_q = mysqli_query($conn,
    "SELECT salesman_name,
     COUNT(*) as entries,
     SUM(first_voucher) as fv,
     SUM(first_vou_rep) as fvr,
     SUM(net_sales) as net_sales,
     SUM(second_voucher) as sv,
     SUM(second_vou_rep) as svr,
     SUM(total_net_sales) as total_net,
     SUM(total_dispatch) as dispatch,
     SUM(total_expenses) as expenses,
     SUM(closing_balance) as closing,
     SUM(cash_received) as cash_recv
     FROM distribution_sheet
     WHERE agent_id='$agent_id'
     GROUP BY salesman_name
     ORDER BY total_dispatch DESC");

$overall = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) as entries,
     SUM(first_voucher) as fv,
     SUM(second_voucher) as sv,
     SUM(total_dispatch) as dispatch,
     SUM(total_net_sales) as total_net,
     SUM(total_expenses) as expenses,
     SUM(closing_balance) as closing,
     SUM(cash_received) as cash_recv
     FROM distribution_sheet WHERE agent_id='$agent_id'"));
?>

<!-- Overall Summary Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:15px;margin-bottom:20px;">
    <div style="background:linear-gradient(135deg,#1565c0,#0D47A1);color:white;border-radius:12px;padding:18px;">
        <div style="font-size:11px;opacity:0.8;margin-bottom:6px;">📦 TOTAL 1ST VOUCHER</div>
        <div style="font-size:20px;font-weight:bold;">Rs.<?= number_format($overall['fv'],0) ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#4A148C,#311B92);color:white;border-radius:12px;padding:18px;">
        <div style="font-size:11px;opacity:0.8;margin-bottom:6px;">📦 TOTAL 2ND VOUCHER</div>
        <div style="font-size:20px;font-weight:bold;">Rs.<?= number_format($overall['sv'],0) ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#1B5E20,#33691E);color:white;border-radius:12px;padding:18px;">
        <div style="font-size:11px;opacity:0.8;margin-bottom:6px;">🚀 TOTAL DISPATCH</div>
        <div style="font-size:20px;font-weight:bold;">Rs.<?= number_format($overall['dispatch'],0) ?></div>
    </div>
    <div style="background:linear-gradient(135deg,#F57F17,#E65100);color:white;border-radius:12px;padding:18px;">
        <div style="font-size:11px;opacity:0.8;margin-bottom:6px;">💰 CLOSING BALANCE</div>
        <div style="font-size:20px;font-weight:bold;">Rs.<?= number_format($overall['closing'],0) ?></div>
    </div>
</div>

<!-- Salesman Wise Summary -->
<div class="card" style="margin-bottom:20px;">
    <h2>👤 Salesman Wise Summary</h2>
    <div style="overflow-x:auto;">
    <table style="width:100%;border-collapse:collapse;font-size:12px;">
        <thead><tr>
            <th style="background:#1a1a2e;color:white;padding:10px 12px;text-align:left;">#</th>
            <th style="background:#1a1a2e;color:white;padding:10px 12px;text-align:left;">Salesman</th>
            <th style="background:#1565c0;color:white;padding:10px 12px;">1st Voucher</th>
            <th style="background:#e65100;color:white;padding:10px 12px;">1st Replace</th>
            <th style="background:#1565c0;color:white;padding:10px 12px;">Net 1st</th>
            <th style="background:#4A148C;color:white;padding:10px 12px;">2nd Voucher</th>
            <th style="background:#B71C1C;color:white;padding:10px 12px;">2nd Replace</th>
            <th style="background:#4A148C;color:white;padding:10px 12px;">Net 2nd</th>
            <th style="background:#1B5E20;color:white;padding:10px 12px;">Total Dispatch</th>
            <th style="background:#263238;color:white;padding:10px 12px;">Total Expenses</th>
            <th style="background:#F57F17;color:white;padding:10px 12px;">Closing Balance</th>
            <th style="background:#1a1a2e;color:white;padding:10px 12px;">Cash Received</th>
        </tr></thead>
        <tbody>
        <?php
        $i=1;
        $gt_fv=0;$gt_fvr=0;$gt_sv=0;$gt_svr=0;
        $gt_disp=0;$gt_exp=0;$gt_cls=0;$gt_cr=0;
        while($row = mysqli_fetch_assoc($summary_q)):
            $net1 = $row['net_sales'];
            $net2 = $row['total_net'] - $net1;
            $gt_fv+=$row['fv'];$gt_fvr+=$row['fvr'];
            $gt_sv+=$row['sv'];$gt_svr+=$row['svr'];
            $gt_disp+=$row['dispatch'];$gt_exp+=$row['expenses'];
            $gt_cls+=$row['closing'];$gt_cr+=$row['cash_recv'];
        ?>
        <tr style="border-bottom:1px solid #eee;">
            <td style="padding:8px 12px;"><?= $i++ ?></td>
            <td style="padding:8px 12px;"><strong><?= htmlspecialchars($row['salesman_name']) ?></strong></td>
            <td style="padding:8px 12px;text-align:center;color:#1565c0;font-weight:bold;">Rs.<?= number_format($row['fv'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;color:#e65100;font-weight:bold;">Rs.<?= number_format($row['fvr'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;background:#e3f2fd;color:#1565c0;font-weight:bold;">Rs.<?= number_format($net1,0) ?></td>
            <td style="padding:8px 12px;text-align:center;color:#4A148C;font-weight:bold;">Rs.<?= number_format($row['sv'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;color:#B71C1C;font-weight:bold;">Rs.<?= number_format($row['svr'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;background:#f3e5f5;color:#4A148C;font-weight:bold;">Rs.<?= number_format($net2,0) ?></td>
            <td style="padding:8px 12px;text-align:center;background:#e8f5e9;color:#1B5E20;font-weight:bold;">Rs.<?= number_format($row['dispatch'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;color:#e65100;font-weight:bold;">Rs.<?= number_format($row['expenses'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;background:#fff8e1;color:#e65100;font-weight:bold;">Rs.<?= number_format($row['closing'],0) ?></td>
            <td style="padding:8px 12px;text-align:center;color:#1B5E20;font-weight:bold;">Rs.<?= number_format($row['cash_recv'],0) ?></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
        <tfoot><tr style="background:#e8f5e9;font-weight:bold;border-top:2px solid #4CAF50;">
            <td colspan="2" style="padding:9px 12px;">🔢 GRAND TOTAL</td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_fv,0) ?></td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_fvr,0) ?></td>
            <td style="padding:9px 12px;text-align:center;background:#e3f2fd;">Rs.<?= number_format($gt_fv-$gt_fvr,0) ?></td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_sv,0) ?></td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_svr,0) ?></td>
            <td style="padding:9px 12px;text-align:center;background:#f3e5f5;">Rs.<?= number_format($gt_sv-$gt_svr,0) ?></td>
            <td style="padding:9px 12px;text-align:center;background:#e8f5e9;">Rs.<?= number_format($gt_disp,0) ?></td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_exp,0) ?></td>
            <td style="padding:9px 12px;text-align:center;background:#fff8e1;">Rs.<?= number_format($gt_cls,0) ?></td>
            <td style="padding:9px 12px;text-align:center;">Rs.<?= number_format($gt_cr,0) ?></td>
        </tr></tfoot>
    </table>
    </div>
</div>

    <!-- Entries Table -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <h2 style="margin:0;border:none;padding:0;">📋 Meri Entries</h2>
            <div style="display:flex;gap:10px;align-items:center;">
                <input type="text" id="searchInput" class="search-box"
                    placeholder="🔍 Search karein..." onkeyup="searchTable()">
                <a href="export_excel.php?type=distribution&city=<?= urlencode($city) ?>"
                    class="btn-excel">📊 Excel</a>
                <button onclick="window.print()"
                    style="background:#1a1a2e;color:white;padding:9px 16px;border-radius:8px;border:none;cursor:pointer;font-size:12px;">
                    🖨️ Print
                </button>
            </div>
        </div>
        <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Edit</th><th>#</th><th>Salesman</th><th>Father</th><th>Sector</th>
                <th>1st Voucher</th><th>1st Rep</th><th>Net Sales🟢</th>
                <th>2nd Voucher</th><th>2nd Rep</th><th>Total Net🟢</th>
                <th>Dispatch🟢</th><th>Finish Goods</th><th>Fuel</th><th>Fuel%🟢</th>
                <th>TD</th><th>MC</th><th>Mkt Bill</th><th>Free Samp</th>
                <th>Total MC🟢</th><th>MC%🟢</th><th>Tax</th>
                <th>Van Paid</th><th>Van Payable</th>
                <th>Inc Base</th><th>Disp Inc</th>
                <th>Commission🟢</th><th>Dealer</th>
                <th>Total Exp🟢</th><th>Exp%🟢</th>
                <th>Cash Recv</th><th>Cash Send</th><th>IOU</th>
                <th>Center Exp</th><th>Adj</th><th>Closing Bal🟢</th>
                <th>Cash Hand</th><th>Phys Stock</th><th>Date</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $i=1;
            $grand = array_fill_keys([
                'first_voucher','first_vou_rep','net_sales','second_voucher',
                'second_vou_rep','total_net_sales','total_dispatch','finish_goods',
                'fuel','total_mc','total_tax','van_rent_paid','commission',
                'dealer','total_expenses','cash_received','cash_send',
                'iou','center_expenses','closing_balance'
            ], 0);
            $rows = [];
            while($row = mysqli_fetch_assoc($entries)) $rows[] = $row;
            foreach($rows as $row):
                foreach(array_keys($grand) as $k) {
                    if(isset($row[$k])) $grand[$k] += $row[$k];
                }
            ?>
            <tr>
                <td><a href="?edit=<?= $row['id'] ?>" class="btn-edit">✏️</a></td>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($row['salesman_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['father_name']) ?></td>
                <td><?= htmlspecialchars($row['sector_name']) ?></td>
                <td>Rs.<?= number_format($row['first_voucher'],2) ?></td>
                <td>Rs.<?= number_format($row['first_vou_rep'],2) ?></td>
                <td class="auto-col">Rs.<?= number_format($row['net_sales'],2) ?></td>
                <td>Rs.<?= number_format($row['second_voucher'],2) ?></td>
                <td>Rs.<?= number_format($row['second_vou_rep'],2) ?></td>
                <td class="auto-col">Rs.<?= number_format($row['total_net_sales'],2) ?></td>
                <td class="auto-col">Rs.<?= number_format($row['total_dispatch'],2) ?></td>
                <td>Rs.<?= number_format($row['finish_goods'],2) ?></td>
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
                <td colspan="5">🔢 GRAND TOTAL</td>
                <td>Rs.<?= number_format($grand['first_voucher'],2) ?></td>
                <td>Rs.<?= number_format($grand['first_vou_rep'],2) ?></td>
                <td>Rs.<?= number_format($grand['net_sales'],2) ?></td>
                <td>Rs.<?= number_format($grand['second_voucher'],2) ?></td>
                <td>Rs.<?= number_format($grand['second_vou_rep'],2) ?></td>
                <td>Rs.<?= number_format($grand['total_net_sales'],2) ?></td>
                <td>Rs.<?= number_format($grand['total_dispatch'],2) ?></td>
                <td>Rs.<?= number_format($grand['finish_goods'],2) ?></td>
                <td>Rs.<?= number_format($grand['fuel'],2) ?></td>
                <td>—</td><td>—</td><td>—</td><td>—</td><td>—</td>
                <td>Rs.<?= number_format($grand['total_mc'],2) ?></td>
                <td>—</td>
                <td>Rs.<?= number_format($grand['total_tax'],2) ?></td>
                <td>Rs.<?= number_format($grand['van_rent_paid'],2) ?></td>
                <td>—</td><td>—</td><td>—</td>
                <td>Rs.<?= number_format($grand['commission'],2) ?></td>
                <td>Rs.<?= number_format($grand['dealer'],2) ?></td>
                <td>Rs.<?= number_format($grand['total_expenses'],2) ?></td>
                <td>—</td>
                <td>Rs.<?= number_format($grand['cash_received'],2) ?></td>
                <td>Rs.<?= number_format($grand['cash_send'],2) ?></td>
                <td>Rs.<?= number_format($grand['iou'],2) ?></td>
                <td>Rs.<?= number_format($grand['center_expenses'],2) ?></td>
                <td>—</td>
                <td>Rs.<?= number_format($grand['closing_balance'],2) ?></td>
                <td>—</td><td>—</td><td>—</td>
            </tr>
            </tfoot>
        </table>
        </div>
    </div>
</div>
</body>
</html>