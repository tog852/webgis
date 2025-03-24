<?php
require_once 'db_login_register.php';
require_once 'check_auth.php';

// Yêu cầu quyền admin để truy cập trang
requireAdmin();

// Lấy thông tin người dùng hiện tại
$username = getCurrentUsername();
$email = getCurrentEmail();
$role = getCurrentRole();

// Xác định trang hiện tại để highlight menu tương ứng
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản Trị</title>
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #205375;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-info span {
            margin-right: 15px;
        }
        .logout-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        .menu {
            background-color: #153b56;
            padding: 10px 20px;
        }
        .menu a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            padding: 10px 0;
            display: inline-block;
        }
        .menu a:hover {
            color: #17a2b8;
        }
        .menu a.active {
            border-bottom: 3px solid #17a2b8;
            font-weight: bold;
        }
        /* Các CSS chung khác */
        .section-title {
            text-align: center;
            margin-bottom: 30px;
        }
        .section-title h2 {
            color: #205375;
            font-size: 30px;
        }
        .section-title p {
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background-color: #205375;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn:hover {
            background-color: #153b56;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Trang Quản Trị</h1>
        <div class="user-info">
            <span>Xin chào, <?= htmlspecialchars($username) ?></span>
            <span>(<?= htmlspecialchars($email) ?>)</span>
            <a href="log_out.php" class="logout-btn">Đăng xuất</a>
        </div>
    </div>
    
    <div class="menu">
        <a href="admin_dashboard.php" <?= $current_page == 'admin_dashboard.php' ? 'class="active"' : '' ?>>Dashboard</a>
        <a href="admin_users.php" <?= $current_page == 'admin_users.php' ? 'class="active"' : '' ?>>Quản lý người dùng</a>
        <a href="create_admin_user.php" <?= $current_page == 'create_admin_user.php' ? 'class="active"' : '' ?>>Tạo tài khoản admin</a>
    </div>

    <div class="container"> 