<?php
include 'config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$is_admin = ($_SESSION['role'] == 'admin');
$my_city  = $_SESSION['city'];

$success = $error = "";

// Add salesman
if (isset($_POST['add_salesman'])) {
    $city    = $is_admin ? mysqli_real_escape_string($conn, $_POST['city']) : $my_city;
    $name    = mysqli_real_escape_string($conn, trim($_POST['salesman_name']));
    $father  = mysqli_real_escape_string($conn, trim($_POST['father_name']));
    $sector  = mysqli_real_escape_string($conn, trim($_POST['sector_name']));

    if (empty($name)) {
        $error = "❌ Salesman name zaroor darj karein!";
    } else {
        $check = mysqli_num_rows(mysqli_query($conn,
            "SELECT id FROM salesmen WHERE city='$city' AND salesman_name='$name'"));
        if ($check > 0) {
            $error = "❌ Ye salesman pehle se exist karta hai!";
        } else {
            mysqli_query($conn,
                "INSERT INTO salesmen (city,salesman_name,father_name,sector_name)
                 VALUES ('$city','$name','$father','$sector')");
            $success = "✅ Salesman add ho gaya!";
        }
    }
}

// Update salesman
if (isset($_POST['update_salesman'])) {
    $id     = intval($_POST['salesman_id']);
    $name   = mysqli_real_escape_string($conn, trim($_POST['salesman_name']));
    $father = mysqli_real_escape_string($conn, trim($_POST['father_name']));
    $sector = mysqli_real_escape_string($conn, trim($_POST['sector_name']));
    $active = intval($_POST['is_active']);

    $city_check = $is_admin ? "" : "AND city='$my_city'";
    mysqli_query($conn,
        "UPDATE salesmen SET salesman_name='$name', father_name='$father',
         sector_name='$sector', is_active='$active'
         WHERE id='$id' $city_check");
    $success = "✅ Salesman update ho gaya!";
    header("Location: salesmen_management.php?msg=updated"); exit();
}

// Delete salesman
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $city_check = $is_admin ? "" : "AND city='$my_city'";
    mysqli_query($conn, "DELETE FROM salesmen WHERE id='$id' $city_check");
    header("Location: salesmen_management.php?msg=deleted"); exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'updated') $success = "✅ Updated!";
    if ($_GET['msg'] == 'deleted') $success = "✅ Deleted!";
}

// Cities list for admin
$cities_list = mysqli_query($conn,
    "SELECT DISTINCT city FROM users WHERE role='agent' ORDER BY city");

// Filter city
$filter_city = isset($_GET['city']) ? mysqli_real_escape_string($conn, $_GET['city']) : ($is_admin ? '' : $my_city);

// Salesmen list
$where = $filter_city ? "WHERE city='$filter_city'" : ($is_admin ? "" : "WHERE city='$my_city'");
$salesmen = mysqli_query($conn,
    "SELECT * FROM salesmen $where ORDER BY city, salesman_name");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Salesmen Management — Merit Bread DMS</title>
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
        .header a.yellow { color:#FFD700; }
        .container { max-width:1000px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .alert-e { background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:18px; border-bottom:2px solid #4CAF50; padding-bottom:10px; }
        .add-grid { display:grid; grid-template-columns:<?= $is_admin?'1fr 1.5fr 1fr 1fr auto':'1.5fr 1fr 1fr auto' ?>; gap:12px; align-items:end; }
        .field label { display:block; font-size:11px; color:#666; margin-bottom:5px; font-weight:bold; text-transform:uppercase; }
        .field input,.field select { width:100%; padding:10px 12px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .field input:focus,.field select:focus { border-color:#4CAF50; outline:none; }
        .btn-add { background:linear-gradient(135deg,#4CAF50,#2e7d32); color:white; padding:11px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:13px; white-space:nowrap; }
        /* Filter */
        .filter-row { display:flex; gap:12px; align-items:center; margin-bottom:20px; flex-wrap:wrap; }
        .filter-row select { padding:9px 13px; border:2px solid #eee; border-radius:8px; font-size:13px; }
        .btn-filter { padding:9px 20px; background:#2196F3; color:white; border:none; border-radius:8px; cursor:pointer; font-weight:bold; }
        .btn-reset  { padding:9px 15px; background:#ff6b6b; color:white; border-radius:8px; text-decoration:none; font-size:13px; }
        /* Stats */
        .stats { display:grid; grid-template-columns:repeat(4,1fr); gap:15px; margin-bottom:20px; }
        .stat-box { background:white; border-radius:10px; padding:15px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border-top:3px solid #4CAF50; }
        .stat-box h4 { font-size:11px; color:#888; margin-bottom:6px; }
        .stat-box p  { font-size:22px; font-weight:bold; color:#1a1a2e; }
        /* Table */
        table { width:100%; border-collapse:collapse; }
        th { background:#1a1a2e; color:white; padding:10px 12px; text-align:left; font-size:12px; }
        td { padding:10px 12px; border-bottom:1px solid #eee; font-size:13px; vertical-align:middle; }
        tr:hover td { background:#fafafa; }
        .city-badge { background:#e3f2fd; color:#1565c0; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge-on  { background:#d4edda; color:#155724; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge-off { background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .btn-edit-sm { background:#FF9800; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-del-sm  { background:#F44336; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; }
        /* Edit form */
        .edit-form { display:none; background:#f8f9fa; padding:15px 18px; border-top:1px solid #eee; }
        .edit-grid { display:grid; grid-template-columns:1.5fr 1fr 1fr 1fr auto; gap:10px; align-items:end; }
        .edit-form .field label { font-size:10px; }
        .edit-form .field input,.edit-form .field select { padding:8px 10px; font-size:12px; }
        .btn-save-sm { background:#4CAF50; color:white; padding:8px 15px; border:none; border-radius:6px; cursor:pointer; font-size:12px; font-weight:bold; }
        .btn-cls-sm  { background:#eee; color:#666; border:none; padding:8px 12px; border-radius:6px; cursor:pointer; font-size:12px; }
    </style>
    <script>
    function toggleEdit(id) {
        var f = document.getElementById('edit-' + id);
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — Salesmen Management</h1>
    <div>
        <?php if($is_admin): ?>
        <a href="admin_dashboard.php" class="green">← Admin Dashboard</a>
        <?php else: ?>
        <a href="agent_entry.php" class="green">← Distribution Sheet</a>
        <?php endif; ?>
        <a href="change_password.php" class="yellow">🔐</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-s'>$success</div>"; ?>
    <?php if($error)   echo "<div class='alert-e'>$error</div>"; ?>

    <!-- Stats -->
    <?php if($is_admin): ?>
    <div class="stats">
        <?php
        $total_s   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM salesmen"))['c'];
        $active_s  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) as c FROM salesmen WHERE is_active=1"))['c'];
        $cities_s  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(DISTINCT city) as c FROM salesmen"))['c'];
        ?>
        <div class="stat-box">
            <h4>TOTAL SALESMEN</h4>
            <p><?= $total_s ?></p>
        </div>
        <div class="stat-box" style="border-color:#4CAF50">
            <h4>ACTIVE</h4>
            <p style="color:#2e7d32"><?= $active_s ?></p>
        </div>
        <div class="stat-box" style="border-color:#2196F3">
            <h4>WAREHOUSES</h4>
            <p style="color:#1565c0"><?= $cities_s ?></p>
        </div>
        <div class="stat-box" style="border-color:#FF9800">
            <h4>INACTIVE</h4>
            <p style="color:#e65100"><?= $total_s - $active_s ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add New Salesman -->
    <div class="card">
        <h2>➕ Naya Salesman Add Karein</h2>
        <form method="POST">
            <div class="add-grid">
                <?php if($is_admin): ?>
                <div class="field">
                    <label>Warehouse / City *</label>
                    <select name="city" required>
                        <option value="">-- City Select --</option>
                        <?php mysqli_data_seek($cities_list,0);
                        while($c = mysqli_fetch_assoc($cities_list)): ?>
                        <option value="<?= $c['city'] ?>"><?= $c['city'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="field">
                    <label>Salesman Name *</label>
                    <input type="text" name="salesman_name"
                        placeholder="Full name darj karein" required>
                </div>
                <div class="field">
                    <label>Father Name</label>
                    <input type="text" name="father_name" placeholder="Father ka naam">
                </div>
                <div class="field">
                    <label>Sector</label>
                    <input type="text" name="sector_name" placeholder="Sector / Area">
                </div>
                <button type="submit" name="add_salesman" class="btn-add">➕ Add</button>
            </div>
        </form>
    </div>

    <!-- Filter -->
    <div class="card" style="padding:15px 25px;">
        <form method="GET" class="filter-row">
            <?php if($is_admin): ?>
            <select name="city">
                <option value="">-- All Cities --</option>
                <?php mysqli_data_seek($cities_list,0);
                while($c = mysqli_fetch_assoc($cities_list)): ?>
                <option value="<?= $c['city'] ?>" <?= $filter_city==$c['city']?'selected':'' ?>>
                    <?= $c['city'] ?>
                </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn-filter">🔍 Filter</button>
            <a href="salesmen_management.php" class="btn-reset">↺ Reset</a>
            <?php endif; ?>
            <span style="color:#888;font-size:13px;">
                <?= $filter_city ? "Showing: <strong>$filter_city</strong>" : "Showing: <strong>All Cities</strong>" ?>
            </span>
        </form>
    </div>

    <!-- Salesmen List -->
    <div class="card">
        <h2>📋 Salesmen List</h2>
        <table>
            <thead><tr>
                <th>#</th>
                <?php if($is_admin): ?><th>City</th><?php endif; ?>
                <th>Salesman Name</th>
                <th>Father Name</th>
                <th>Sector</th>
                <th>Status</th>
                <th>Action</th>
            </tr></thead>
            <tbody>
            <?php $i=1; while($row = mysqli_fetch_assoc($salesmen)): ?>
            <tr>
                <td><?= $i++ ?></td>
                <?php if($is_admin): ?>
                <td><span class="city-badge"><?= htmlspecialchars($row['city']) ?></span></td>
                <?php endif; ?>
                <td><strong><?= htmlspecialchars($row['salesman_name']) ?></strong></td>
                <td><?= htmlspecialchars($row['father_name']) ?></td>
                <td><?= htmlspecialchars($row['sector_name']) ?></td>
                <td>
                    <span class="<?= $row['is_active'] ? 'badge-on' : 'badge-off' ?>">
                        <?= $row['is_active'] ? '✅ Active' : '❌ Inactive' ?>
                    </span>
                </td>
                <td>
                    <button class="btn-edit-sm" onclick="toggleEdit(<?= $row['id'] ?>)">✏️ Edit</button>
                    <a href="?delete=<?= $row['id'] ?>"
                       class="btn-del-sm"
                       onclick="return confirm('<?= htmlspecialchars($row['salesman_name']) ?> ko delete karna chahte ho?')">
                       🗑️
                    </a>
                </td>
            </tr>
            <!-- Edit Row -->
            <tr>
                <td colspan="<?= $is_admin ? 7 : 6 ?>" style="padding:0;">
                    <div class="edit-form" id="edit-<?= $row['id'] ?>">
                        <form method="POST">
                            <input type="hidden" name="salesman_id" value="<?= $row['id'] ?>">
                            <div class="edit-grid">
                                <div class="field">
                                    <label>Salesman Name</label>
                                    <input type="text" name="salesman_name"
                                        value="<?= htmlspecialchars($row['salesman_name']) ?>" required>
                                </div>
                                <div class="field">
                                    <label>Father Name</label>
                                    <input type="text" name="father_name"
                                        value="<?= htmlspecialchars($row['father_name']) ?>">
                                </div>
                                <div class="field">
                                    <label>Sector</label>
                                    <input type="text" name="sector_name"
                                        value="<?= htmlspecialchars($row['sector_name']) ?>">
                                </div>
                                <div class="field">
                                    <label>Status</label>
                                    <select name="is_active">
                                        <option value="1" <?= $row['is_active']?'selected':'' ?>>✅ Active</option>
                                        <option value="0" <?= !$row['is_active']?'selected':'' ?>>❌ Inactive</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" name="update_salesman" class="btn-save-sm">💾 Save</button>
                                    <button type="button" onclick="toggleEdit(<?= $row['id'] ?>)" class="btn-cls-sm">✕</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>