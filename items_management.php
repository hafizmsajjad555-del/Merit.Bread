<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$success = $error = "";

$families   = ['Bread','Bun','Rusk','Cake','Burger','Baqar Khani','Shawarma'];
$categories = [
    'first_voucher'  => ['label'=>'📦 1st Voucher', 'color'=>'#1565c0', 'bg'=>'#e3f2fd'],
    'second_voucher' => ['label'=>'📦 2nd Voucher', 'color'=>'#6a1b9a', 'bg'=>'#f3e5f5'],
    'finish_good'    => ['label'=>'✅ Finish Good',  'color'=>'#2e7d32', 'bg'=>'#e8f5e9'],
];

// Add item
if (isset($_POST['add_item'])) {
    $name     = mysqli_real_escape_string($conn, trim($_POST['item_name']));
    $category = $_POST['category'];
    $family   = mysqli_real_escape_string($conn, $_POST['family']);
    $rate     = floatval($_POST['rate']);
    $check    = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM items WHERE item_name='$name'"));
    if ($check > 0) {
        $error = "❌ This item already exists!";
    } else {
        mysqli_query($conn, "INSERT INTO items (item_name,category,family,rate) VALUES ('$name','$category','$family','$rate')");
        $success = "✅ Item added successfully!";
    }
}

// Update item
if (isset($_POST['update_item'])) {
    $id       = intval($_POST['item_id']);
    $name     = mysqli_real_escape_string($conn, trim($_POST['item_name']));
    $category = $_POST['category'];
    $family   = mysqli_real_escape_string($conn, $_POST['family']);
    $rate     = floatval($_POST['rate']);
    $active   = intval($_POST['is_active']);
    mysqli_query($conn, "UPDATE items SET item_name='$name',category='$category',family='$family',rate='$rate',is_active='$active' WHERE id='$id'");
    header("Location: items_management.php?msg=updated"); exit();
}

// Delete item
if (isset($_GET['delete'])) {
    mysqli_query($conn, "DELETE FROM items WHERE id='".intval($_GET['delete'])."'");
    header("Location: items_management.php?msg=deleted"); exit();
}

if (isset($_GET['msg'])) {
    if ($_GET['msg']=='updated') $success = "✅ Item updated successfully!";
    if ($_GET['msg']=='deleted') $success = "✅ Item deleted successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Items Management — Merit Bread DMS</title>
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
        .container { max-width:1200px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .alert-e { background:#f8d7da; color:#721c24; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; }

        /* Stats */
        .stats-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:10px; margin-bottom:25px; }
        .stat-box { background:white; border-radius:10px; padding:15px 10px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); border-top:3px solid #4CAF50; }
        .stat-box h4 { font-size:11px; color:#888; margin-bottom:6px; }
        .stat-box p  { font-size:22px; font-weight:bold; color:#1a1a2e; }

        /* Add Item Card */
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:18px; border-bottom:2px solid #4CAF50; padding-bottom:10px; }
        .add-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:12px; align-items:end; }
        .field label { display:block; font-size:11px; color:#666; margin-bottom:5px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; }
        .field input,.field select { width:100%; padding:10px 12px; border:2px solid #eee; border-radius:8px; font-size:13px; color:#1a1a2e; transition:border 0.2s; }
        .field input:focus,.field select:focus { border-color:#4CAF50; outline:none; }
        .btn-add { background:linear-gradient(135deg,#4CAF50,#2e7d32); color:white; padding:11px 20px; border:none; border-radius:8px; cursor:pointer; font-weight:bold; font-size:13px; white-space:nowrap; }
        .btn-add:hover { opacity:0.9; }

        /* Tabs */
        .tab-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
        .ftab { padding:8px 16px; border-radius:8px; cursor:pointer; font-weight:bold; font-size:12px; border:2px solid #eee; background:white; color:#666; white-space:nowrap; transition:all 0.2s; }
        .ftab:hover { border-color:#4CAF50; }
        .ftab.active { background:#1a1a2e; color:white; border-color:#1a1a2e; }
        .ftab-content { display:none; }
        .ftab-content.active { display:block; }

        /* Category sections */
        .cat-section { margin-bottom:15px; border-radius:10px; overflow:hidden; border:1px solid #eee; }
        .cat-header { padding:10px 18px; font-weight:bold; font-size:13px; display:flex; justify-content:space-between; align-items:center; color:white; }
        .cat-count { font-size:11px; padding:2px 10px; border-radius:15px; background:rgba(255,255,255,0.25); }

        /* Items table */
        .items-tbl { width:100%; border-collapse:collapse; }
        .items-tbl th { background:#f8f9fa; color:#555; padding:9px 12px; text-align:left; font-size:11px; text-transform:uppercase; border-bottom:2px solid #eee; }
        .items-tbl td { padding:9px 12px; border-bottom:1px solid #f5f5f5; font-size:13px; vertical-align:middle; }
        .items-tbl tr:last-child td { border-bottom:none; }
        .items-tbl tr:hover td { background:#fafafa; }
        .badge-on  { background:#d4edda; color:#155724; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .badge-off { background:#f8d7da; color:#721c24; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .cat-pill  { padding:2px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .btn-edit-sm { background:#2196F3; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-del-sm  { background:#F44336; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; }
        .btn-save-sm { background:#4CAF50; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }

        /* Edit form */
        .edit-row td { padding:0 !important; }
        .edit-form { display:none; background:#f8f9fa; padding:15px 18px; border-top:1px solid #eee; }
        .edit-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 1fr auto; gap:10px; align-items:end; }
        .edit-form .field label { font-size:10px; }
        .edit-form .field input,
        .edit-form .field select { padding:8px 10px; font-size:12px; }

        .empty-msg { text-align:center; padding:20px; color:#aaa; font-size:13px; font-style:italic; }
    </style>
    <script>
    function showFTab(tab) {
        document.querySelectorAll('.ftab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.ftab-content').forEach(t => t.classList.remove('active'));
        document.getElementById('ftab-' + tab).classList.add('active');
        document.getElementById('fcontent-' + tab).classList.add('active');
    }
    function toggleEdit(id) {
        var f = document.getElementById('edit-' + id);
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
    window.onload = function() { showFTab('Bread'); }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — Items Management</h1>
    <div>
        <a href="admin_dashboard.php" class="green">← Admin Dashboard</a>
        <a href="change_password.php" class="yellow">🔐 Password</a>
        <a href="login.php">🚪 Logout</a>
    </div>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-s'>$success</div>"; ?>
    <?php if($error)   echo "<div class='alert-e'>$error</div>"; ?>

    <!-- Family Stats -->
    <div class="stats-grid">
        <?php
        $fam_colors = [
            'Bread'=>'#1565c0','Bun'=>'#e65100','Rusk'=>'#6a1b9a',
            'Cake'=>'#2e7d32','Burger'=>'#c62828',
            'Baqar Khani'=>'#00695c','Shawarma'=>'#f57f17'
        ];
        foreach($families as $fam):
            $cnt = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COUNT(*) as c FROM items WHERE family='$fam' AND is_active=1"));
            $clr = $fam_colors[$fam] ?? '#4CAF50';
        ?>
        <div class="stat-box" style="border-top-color:<?= $clr ?>">
            <h4><?= $fam ?></h4>
            <p style="color:<?= $clr ?>"><?= $cnt['c'] ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Add New Item -->
    <div class="card">
        <h2>➕ Add New Item</h2>
        <form method="POST">
            <div class="add-grid">
                <div class="field">
                    <label>Item Name *</label>
                    <input type="text" name="item_name" placeholder="e.g. White Bread 400g" required>
                </div>
                <div class="field">
                    <label>Family *</label>
                    <select name="family" required>
                        <?php foreach($families as $fam): ?>
                        <option value="<?= $fam ?>"><?= $fam ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Voucher Category *</label>
                    <select name="category" required>
                        <?php foreach($categories as $key => $cat): ?>
                        <option value="<?= $key ?>"><?= $cat['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Rate (Rs.) *</label>
                    <input type="number" name="rate" step="0.01" min="0" placeholder="0.00" required>
                </div>
                <button type="submit" name="add_item" class="btn-add">➕ Add Item</button>
            </div>
        </form>
    </div>

    <!-- Items List — Family + Category wise -->
    <div class="card">
        <h2>📋 Items List</h2>

        <!-- Family Tabs -->
        <div class="tab-row">
            <?php foreach($families as $fam): ?>
            <div class="ftab" id="ftab-<?= $fam ?>" onclick="showFTab('<?= $fam ?>')">
                <?= $fam ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Family Content -->
        <?php foreach($families as $fam):
            $fclr = $fam_colors[$fam] ?? '#4CAF50';
        ?>
        <div class="ftab-content" id="fcontent-<?= $fam ?>">
            <?php foreach($categories as $cat_key => $cat):
                $items = mysqli_query($conn,
                    "SELECT * FROM items WHERE family='$fam' AND category='$cat_key' ORDER BY item_name");
                $total = mysqli_num_rows($items);
                if ($total == 0) continue;
            ?>
            <div class="cat-section">
                <div class="cat-header" style="background:<?= $cat['color'] ?>">
                    <span><?= $cat['label'] ?> — <?= $fam ?></span>
                    <span class="cat-count"><?= $total ?> items</span>
                </div>
                <table class="items-tbl">
                    <thead><tr>
                        <th width="5%">#</th>
                        <th width="40%">Item Name</th>
                        <th width="15%">Rate</th>
                        <th width="15%">Status</th>
                        <th width="25%">Action</th>
                    </tr></thead>
                    <tbody>
                    <?php $i=1; while($row = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><strong><?= htmlspecialchars($row['item_name']) ?></strong></td>
                        <td style="color:<?= $cat['color'] ?>;font-weight:bold;">
                            Rs.<?= number_format($row['rate'],2) ?>
                        </td>
                        <td>
                            <span class="<?= $row['is_active'] ? 'badge-on' : 'badge-off' ?>">
                                <?= $row['is_active'] ? '✅ Active' : '❌ Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-edit-sm" onclick="toggleEdit(<?= $row['id'] ?>)">✏️ Edit</button>
                            &nbsp;
                            <a href="?delete=<?= $row['id'] ?>" class="btn-del-sm"
                               onclick="return confirm('Delete \'<?= htmlspecialchars($row['item_name']) ?>\'?')">
                               🗑️
                            </a>
                        </td>
                    </tr>
                    <tr class="edit-row">
                        <td colspan="5">
                            <div class="edit-form" id="edit-<?= $row['id'] ?>">
                                <form method="POST">
                                    <input type="hidden" name="item_id" value="<?= $row['id'] ?>">
                                    <div class="edit-grid">
                                        <div class="field">
                                            <label>Item Name</label>
                                            <input type="text" name="item_name"
                                                value="<?= htmlspecialchars($row['item_name']) ?>" required>
                                        </div>
                                        <div class="field">
                                            <label>Family</label>
                                            <select name="family">
                                                <?php foreach($families as $f): ?>
                                                <option value="<?= $f ?>" <?= $row['family']==$f?'selected':'' ?>><?= $f ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Category</label>
                                            <select name="category">
                                                <?php foreach($categories as $k => $c): ?>
                                                <option value="<?= $k ?>" <?= $row['category']==$k?'selected':'' ?>>
                                                    <?= $c['label'] ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="field">
                                            <label>Rate (Rs.)</label>
                                            <input type="number" name="rate"
                                                value="<?= $row['rate'] ?>" step="0.01" min="0" required>
                                        </div>
                                        <div class="field">
                                            <label>Status</label>
                                            <select name="is_active">
                                                <option value="1" <?= $row['is_active']?'selected':'' ?>>✅ Active</option>
                                                <option value="0" <?= !$row['is_active']?'selected':'' ?>>❌ Inactive</option>
                                            </select>
                                        </div>
                                        <div>
                                            <button type="submit" name="update_item" class="btn-save-sm">💾 Save</button>
                                            <br><br>
                                            <button type="button" onclick="toggleEdit(<?= $row['id'] ?>)"
                                                style="background:#eee;border:none;padding:5px 10px;border-radius:6px;cursor:pointer;font-size:12px;">
                                                ✕
                                            </button>
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
            <?php endforeach; ?>

            <?php
            // Agar is family mein koi item nahi
            $total_fam = mysqli_fetch_assoc(mysqli_query($conn,
                "SELECT COUNT(*) as c FROM items WHERE family='$fam'"));
            if ($total_fam['c'] == 0):
            ?>
            <div class="empty-msg">No items in <?= $fam ?> family — Add from above</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>