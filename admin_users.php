<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit();
}

$success = $error = "";

// Password change
if (isset($_POST['change_password'])) {
    $user_id  = intval($_POST['user_id']);
    $new_pass = MD5($_POST['new_password']);
    mysqli_query($conn, "UPDATE users SET password='$new_pass' WHERE id='$user_id'");
    $success = "✅ Password change ho gaya!";
}

$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role, city");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Users Management</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; background:#f0f2f5; }
        .header { background:linear-gradient(135deg,#1a1a2e,#16213e); color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
        .header h1 { font-size:18px; }
        .header a { color:#90EE90; text-decoration:none; margin-left:12px; font-size:13px; }
        .container { max-width:900px; margin:25px auto; padding:0 20px; }
        .alert-s { background:#d4edda; color:#155724; padding:12px; border-radius:8px; margin-bottom:15px; text-align:center; font-weight:bold; }
        .card { background:white; border-radius:12px; padding:25px; box-shadow:0 2px 15px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card h2 { color:#1a1a2e; margin-bottom:20px; font-size:18px; border-bottom:2px solid #4CAF50; padding-bottom:10px; }
        table { width:100%; border-collapse:collapse; }
        th { background:#1a1a2e; color:white; padding:10px 12px; text-align:left; font-size:13px; }
        td { padding:10px 12px; border-bottom:1px solid #eee; font-size:13px; vertical-align:middle; }
        tr:hover td { background:#fafafa; }
        .role-admin { background:#fce4ec; color:#c62828; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .role-agent { background:#e3f2fd; color:#1565c0; padding:3px 10px; border-radius:15px; font-size:11px; font-weight:bold; }
        .pass-form { display:none; margin-top:8px; }
        .pass-form input { padding:7px 10px; border:2px solid #eee; border-radius:6px; font-size:12px; width:160px; }
        .pass-form input:focus { border-color:#4CAF50; outline:none; }
        .btn-show { background:#FF9800; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-save { background:#4CAF50; color:white; padding:5px 12px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
        .btn-cls  { background:#eee; color:#666; padding:5px 10px; border:none; border-radius:6px; cursor:pointer; font-size:12px; }
    </style>
    <script>
    function togglePass(id) {
        var f = document.getElementById('pass_'+id);
        f.style.display = f.style.display==='none' ? 'flex' : 'none';
    }
    </script>
</head>
<body>
<div class="header">
    <h1>🍞 Merit Bread DMS — Users Management</h1>
    <a href="admin_dashboard.php">← Admin Dashboard</a>
</div>
<div class="container">
    <?php if($success) echo "<div class='alert-s'>$success</div>"; ?>
    <div class="card">
        <h2>👥 Tamam Users — Password Management</h2>
        <table>
            <thead><tr>
                <th>#</th><th>Username</th><th>City/Warehouse</th>
                <th>Role</th><th>Password Change</th>
            </tr></thead>
            <tbody>
            <?php $i=1; while($row = mysqli_fetch_assoc($users)): ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><strong><?= htmlspecialchars($row['username']) ?></strong></td>
                <td><?= htmlspecialchars($row['city']) ?></td>
                <td>
                    <span class="<?= $row['role']=='admin'?'role-admin':'role-agent' ?>">
                        <?= strtoupper($row['role']) ?>
                    </span>
                </td>
                <td>
                    <button class="btn-show" onclick="togglePass(<?= $row['id'] ?>)">
                        🔐 Change Password
                    </button>
                    <div id="pass_<?= $row['id'] ?>" class="pass-form" style="gap:8px;align-items:center;">
                        <form method="POST" style="display:flex;gap:8px;align-items:center;">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <input type="password" name="new_password"
                                placeholder="Naya password..." required minlength="4">
                            <button type="submit" name="change_password" class="btn-save">💾 Save</button>
                            <button type="button" onclick="togglePass(<?= $row['id'] ?>)" class="btn-cls">✕</button>
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