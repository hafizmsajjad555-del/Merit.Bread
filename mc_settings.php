<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$success = "";

// Save settings
if (isset($_POST['save_settings'])) {
    $cities = $_POST['city'];
    foreach ($cities as $city) {
        $city_e = mysqli_real_escape_string($conn, $city);
        $base   = $_POST['mc_base'][$city] ?? 'first_voucher';
        $pct    = floatval($_POST['mc_percentage'][$city]);
        mysqli_query($conn, "INSERT INTO mc_settings (city, mc_base, mc_percentage)
            VALUES ('$city_e', '$base', '$pct')
            ON DUPLICATE KEY UPDATE mc_base='$base', mc_percentage='$pct'");
    }
    $success = "✅ MC Settings saved successfully!";
}

$warehouses = mysqli_query($conn, "SELECT u.city,
    COALESCE(m.mc_base, 'first_voucher') as mc_base,
    COALESCE(m.mc_percentage, 0) as mc_percentage
    FROM users u
    LEFT JOIN mc_settings m ON u.city=m.city
    WHERE u.role='agent'
    ORDER BY u.city");
?>
<!DOCTYPE html>
<html>
<head>
    <title>MC Settings — Merit Bread DMS</title>
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
        .container { max-width:900px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:18px; border-bottom:2px solid #4CAF50; padding-bottom:10px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#1a1a2e; color:white; padding:12px 15px; text-align:left; font-size:13px; }
        td { padding:12px 15px; border-bottom:1px solid #eee; vertical-align:middle; }
        tr:hover td { background:#f9f9f9; }
        .city-name { font-weight:bold; color:#1a1a2e; font-size:14px; }
        select, input[type='number'] {
            padding:8px 12px; border:2px solid #eee;
            border-radius:8px; font-size:13px; width:100%;
        }
        select:focus, input:focus { border-color:#4CAF50; outline:none; }
        .btn-save { background:linear-gradient(135deg,#4CAF50,#2e7d32); color:white; padding:13px; border:none; border-radius:8px; font-size:15px; cursor:pointer; font-weight:bold; width:100%; margin-top:20px; }
        .formula-preview { font-size:11px; color:#888; margin-top:4px; }
        .badge-fv  { background:#e3f2fd; color:#1565c0; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge-tot { background:#f3e5f5; color:#6a1b9a; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
    </style>
    <script>
    function updateFormula(city) {
        var base = document.getElementById('base_'+city).value;
        var pct  = document.getElementById('pct_'+city).value || '?';
        var preview = document.getElementById('preview_'+city);
        if (base == 'first_voucher') {
            preview.innerHTML = '(1st Vou. Dispatch - 1st Replace - Free Sampling) × ' + pct + '%';
        } else {
            preview.innerHTML = '(Total Net Sales - Free Sampling) × ' + pct + '%';
        }
    }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — MC % Settings</h1>
    <div>
        <a href="admin_dashboard.php" class="green">← Admin Dashboard</a>
        <a href="login.php">🚪 Logout</a>
<a href="mc_settings.php" class="green">⚙️ MC Settings</a>
    </div>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-s'>$success</div>"; ?>
    <div class="card">
        <h2>⚙️ Warehouse Wise MC % Settings</h2>
        <p style="color:#888;font-size:13px;margin-bottom:20px;">
            Har warehouse ke liye MC ka base aur percentage set karein.
            <br>Agent form mein MC automatically calculate hogi.
        </p>
        <form method="POST">
        <table>
            <thead><tr>
                <th>#</th>
                <th>Warehouse / City</th>
                <th>MC Base</th>
                <th>MC Percentage (%)</th>
                <th>Formula Preview</th>
            </tr></thead>
            <tbody>
            <?php $i=1; while($wh = mysqli_fetch_assoc($warehouses)): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td>
                    <span class="city-name">🏭 <?= htmlspecialchars($wh['city']) ?></span>
                    <input type="hidden" name="city[]" value="<?= $wh['city'] ?>">
                </td>
                <td>
                    <select name="mc_base[<?= $wh['city'] ?>]"
                        id="base_<?= $wh['city'] ?>"
                        onchange="updateFormula('<?= $wh['city'] ?>')">
                        <option value="first_voucher" <?= $wh['mc_base']=='first_voucher'?'selected':'' ?>>
                            📦 1st Voucher Net Sales
                        </option>
                        <option value="total_net" <?= $wh['mc_base']=='total_net'?'selected':'' ?>>
                            📊 Total Net Sales
                        </option>
                    </select>
                </td>
                <td>
                    <input type="number"
                        name="mc_percentage[<?= $wh['city'] ?>]"
                        id="pct_<?= $wh['city'] ?>"
                        value="<?= $wh['mc_percentage'] ?>"
                        step="0.01" min="0" max="100"
                        onchange="updateFormula('<?= $wh['city'] ?>')"
                        oninput="updateFormula('<?= $wh['city'] ?>')">
                </td>
                <td>
                    <div id="preview_<?= $wh['city'] ?>" class="formula-preview">
                        <?php if($wh['mc_base']=='first_voucher'): ?>
                        (1st Vou. Dispatch - 1st Replace - Free Sampling) × <?= $wh['mc_percentage'] ?>%
                        <?php else: ?>
                        (Total Net Sales - Free Sampling) × <?= $wh['mc_percentage'] ?>%
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <button type="submit" name="save_settings" class="btn-save">💾 Save MC Settings</button>
        </form>
    </div>
</div>
</body>
</html>