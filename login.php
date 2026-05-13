<?php
include 'config.php';
$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = MD5($_POST['password']);
    $sql    = "SELECT * FROM users WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $sql);
    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['city']     = $user['city'];
        $_SESSION['role']     = $user['role'];
        if ($user['role'] == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: agent_entry.php");
        }
        exit();
    } else {
        $error = "Galat username ya password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Merit Bread — Distribution Management System</title>
    <style>
@media print {
    .header, .tabs, .filters, .btn-submit, 
    .btn-edit, form { display: none !important; }
    .card { box-shadow: none !important; }
    body { background: white !important; }
}
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
        }
        .login-wrap {
            display: flex; flex-direction: column; align-items: center;
        }
        .company-header {
            text-align: center; margin-bottom: 30px;
        }
        .company-header h1 {
            color: white; font-size: 32px; font-weight: 800;
            letter-spacing: 2px; margin-bottom: 5px;
        }
        .company-header h1 span { color: #4CAF50; }
        .company-header p {
            color: rgba(255,255,255,0.6); font-size: 13px;
            letter-spacing: 3px; text-transform: uppercase;
        }
        .login-box {
            background: white; padding: 40px 45px;
            border-radius: 20px; width: 400px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.4);
        }
        .login-box h2 {
            text-align: center; color: #1a1a2e;
            margin-bottom: 5px; font-size: 20px;
        }
        .login-box p {
            text-align: center; color: #888;
            margin-bottom: 30px; font-size: 13px;
        }
        .field { margin-bottom: 18px; }
        .field label {
            display: block; font-size: 12px;
            color: #666; margin-bottom: 6px; font-weight: 600;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .field input {
            width: 100%; padding: 13px 15px;
            border: 2px solid #eee; border-radius: 10px;
            font-size: 14px; transition: border 0.3s;
            color: #1a1a2e;
        }
        .field input:focus { border-color: #4CAF50; outline: none; }
        .btn-login {
            width: 100%; padding: 14px;
            background: linear-gradient(135deg, #4CAF50, #2e7d32);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 700; cursor: pointer;
            letter-spacing: 1px; margin-top: 5px;
            transition: opacity 0.3s;
        }
        .btn-login:hover { opacity: 0.9; }
        .error {
            background: #ffe0e0; color: #c0392b;
            padding: 12px; border-radius: 10px;
            margin-bottom: 20px; text-align: center;
            font-size: 13px; font-weight: 600;
        }
        .divider {
            display: flex; align-items: center; margin: 20px 0;
        }
        .divider hr { flex:1; border: none; border-top: 1px solid #eee; }
        .divider span { padding: 0 12px; color: #aaa; font-size: 12px; }
        .footer-text {
            text-align: center; margin-top: 20px;
            color: rgba(255,255,255,0.4); font-size: 12px;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <!-- Company Header -->
    <div class="company-header">
        <h1>🍞 Merit <span>Bread</span></h1>
        <p>Distribution Management System</p>
    </div>

    <!-- Login Box -->
    <div class="login-box">
        <h2>🔐 Secure Login</h2>
        <p>Login with your warehouse credentials</p>

        <?php if($error): ?>
        <div class="error">❌ <?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="field">
                <label>👤 Username</label>
                <input type="text" name="username" placeholder="Enter your username" required autofocus>
            </div>
            <div class="field">
                <label>🔒 Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">LOGIN WITH PASSWORD →</button>
        </form>

        <div class="divider">
            <hr><span>Merit Bread DMS v1.0</span><hr>
        </div>
        <p style="text-align:center; color:#aaa; font-size:12px;">
            Unauthorized access is strictly prohibited
        </p>
    </div>

    <div class="footer-text">
        © 2026 Merit Bread — Distribution Management System
    </div>
</div>
</body>
</html>